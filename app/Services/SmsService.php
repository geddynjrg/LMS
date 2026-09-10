<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public const CREDITS_EXHAUSTED_CACHE_KEY = 'sms.ebis.credits_exhausted';

    public function isConfigured(): bool
    {
        $config = config('sms.ebiscloud');

        return !empty($config['api_key'])
            && !empty($config['api_url'])
            && !empty($config['shortcode']);
    }

    public function creditsAreExhausted(): bool
    {
        return (bool) Cache::get(self::CREDITS_EXHAUSTED_CACHE_KEY);
    }

    public function send(string $phoneNumber, string $message): bool
    {
        return $this->sendDetailed($phoneNumber, $message)['ok'];
    }

    /**
     * @return array{ok: bool, credits_exhausted: bool, error: ?string}
     */
    public function sendDetailed(string $phoneNumber, string $message): array
    {
        $failed = ['ok' => false, 'credits_exhausted' => false, 'error' => null];

        if ($this->creditsAreExhausted()) {
            return ['ok' => false, 'credits_exhausted' => true, 'error' => 'EBIS bulk SMS credits are exhausted.'];
        }

        if (!$this->isConfigured()) {
            Log::warning('SMS not sent: EBIS Cloud SMS is not configured');

            return $failed + ['error' => 'SMS is not configured.'];
        }

        $mobile = $this->normalizePhoneNumber($phoneNumber);
        if (!$mobile) {
            Log::warning('SMS not sent: invalid phone number', ['phone' => $phoneNumber]);

            return $failed + ['error' => 'Invalid phone number.'];
        }

        $config = config('sms.ebiscloud');

        try {
            $attempts = max(1, (int) config('sms.send_attempts', 3));
            $retryPause = (int) config('sms.retry_pause_microseconds', 250000);
            $lastError = null;

            for ($attempt = 1; $attempt <= $attempts; $attempt++) {
                try {
                    $response = Http::timeout(15)
                        ->acceptJson()
                        ->post($config['api_url'], [
                            'api_key' => $config['api_key'],
                            'mobile' => $mobile,
                            'response_type' => 'json',
                            'shortcode' => trim($config['shortcode'], '"'),
                            'message' => $message,
                            'service_id' => $config['service_id'],
                        ]);

                    $body = $response->body();
                    $payload = $response->json();

                    if ($this->isCreditsExhausted($payload, $body)) {
                        Cache::put(self::CREDITS_EXHAUSTED_CACHE_KEY, true, now()->addMinutes(10));
                        Log::error('SMS send stopped: EBIS bulk credits exhausted', [
                            'phone' => $mobile,
                            'response' => $payload ?: $body,
                        ]);

                        return [
                            'ok' => false,
                            'credits_exhausted' => true,
                            'error' => 'EBIS bulk SMS credits are exhausted. Top up the EBIS account, then retry.',
                        ];
                    }

                    if ($response->successful()) {
                        if ($this->isSuccessfulResponse($payload)) {
                            Log::info('SMS sent successfully', [
                                'phone' => $mobile,
                                'response' => $payload,
                                'attempt' => $attempt,
                            ]);

                            return ['ok' => true, 'credits_exhausted' => false, 'error' => null];
                        }

                        $lastError = 'rejected: '.json_encode($payload);
                    } else {
                        $lastError = 'http '.$response->status().': '.$body;
                    }
                } catch (\Throwable $e) {
                    $lastError = $e->getMessage();
                }

                if ($attempt < $attempts && $retryPause > 0) {
                    usleep($retryPause);
                }
            }

            Log::error('SMS send failed after retries', [
                'phone' => $mobile,
                'error' => $lastError,
                'attempts' => $attempts,
            ]);

            return $failed + ['error' => $lastError];
        } catch (\Throwable $e) {
            Log::error('SMS send failed', [
                'phone' => $mobile,
                'error' => $e->getMessage(),
            ]);

            return $failed + ['error' => $e->getMessage()];
        }
    }

    protected function isCreditsExhausted(mixed $payload, string $body): bool
    {
        $haystack = strtolower($body.' '.json_encode($payload));

        if (str_contains($haystack, 'low bulk credits') || str_contains($haystack, '"1004"')) {
            return true;
        }

        if (! is_array($payload)) {
            return false;
        }

        $code = (string) ($payload['status_code'] ?? $payload['status'] ?? '');
        if ($code === '1004') {
            return true;
        }

        if (isset($payload[0]) && is_array($payload[0])) {
            $code = (string) ($payload[0]['status_code'] ?? $payload[0]['status'] ?? '');
            if ($code === '1004') {
                return true;
            }
        }

        if (isset($payload['details']) && is_array($payload['details'])) {
            $code = (string) ($payload['details']['status_code'] ?? '');
            if ($code === '1004') {
                return true;
            }
        }

        return false;
    }

    /**
     * EBIS Cloud returns several success shapes, including:
     * {"status":"1000"}, [{"status_code":"1000",...}], and
     * {"success":true,"summary":{"successful":1,"failed":0},"details":[{"status_code":"1000",...}]}.
     */
    protected function isSuccessfulResponse(mixed $payload): bool
    {
        if (!is_array($payload)) {
            return false;
        }

        $successFlag = $payload['success'] ?? null;
        if ($successFlag === true || $successFlag === 1 || $successFlag === 'true' || $successFlag === '1') {
            $failed = (int) data_get($payload, 'summary.failed', 0);
            $successful = (int) data_get($payload, 'summary.successful', 1);
            if ($failed === 0 && $successful > 0) {
                return true;
            }
        }

        $rows = [];
        if (isset($payload['details']) && is_array($payload['details'])) {
            $rows = $payload['details'];
        } elseif (isset($payload[0]) && is_array($payload[0])) {
            $rows = $payload;
        } else {
            $rows = [$payload];
        }

        $okCodes = ['1000', '1001'];
        $okWords = ['success', 'queued', 'accepted', 'ok'];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $code = (string) ($row['status_code'] ?? $row['status'] ?? '');
            $desc = strtolower((string) ($row['status_desc'] ?? $row['message'] ?? $row['status'] ?? ''));
            if (in_array($code, $okCodes, true) || in_array($desc, $okWords, true)) {
                return true;
            }
        }

        return false;
    }

    protected function normalizePhoneNumber(string $phoneNumber): ?string
    {
        $digits = preg_replace('/\D+/', '', $phoneNumber);

        if (!$digits) {
            return null;
        }

        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            return '254' . substr($digits, 1);
        }

        if (str_starts_with($digits, '254')) {
            return $digits;
        }

        if (strlen($digits) === 9) {
            return '254' . $digits;
        }

        return strlen($digits) >= 10 ? $digits : null;
    }
}
