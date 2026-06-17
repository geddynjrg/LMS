<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function isConfigured(): bool
    {
        $config = config('sms.ebiscloud');

        return !empty($config['api_key'])
            && !empty($config['api_url'])
            && !empty($config['shortcode']);
    }

    public function send(string $phoneNumber, string $message): bool
    {
        if (!$this->isConfigured()) {
            Log::warning('SMS not sent: EBIS Cloud SMS is not configured');

            return false;
        }

        $mobile = $this->normalizePhoneNumber($phoneNumber);
        if (!$mobile) {
            Log::warning('SMS not sent: invalid phone number', ['phone' => $phoneNumber]);

            return false;
        }

        $config = config('sms.ebiscloud');

        try {
            $response = Http::timeout(30)
                ->acceptJson()
                ->post($config['api_url'], [
                    'api_key' => $config['api_key'],
                    'mobile' => $mobile,
                    'response_type' => 'json',
                    'shortcode' => trim($config['shortcode'], '"'),
                    'message' => $message,
                    'service_id' => $config['service_id'],
                ]);

            if (!$response->successful()) {
                Log::error('SMS API HTTP error', [
                    'phone' => $mobile,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            $payload = $response->json();
            if ($this->isSuccessfulResponse($payload)) {
                Log::info('SMS sent successfully', ['phone' => $mobile, 'response' => $payload]);

                return true;
            }

            Log::error('SMS API rejected message', [
                'phone' => $mobile,
                'response' => $payload,
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error('SMS send failed', [
                'phone' => $mobile,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * EBIS Cloud may return {"status":"1000"} or [{"status_code":"1000","status_desc":"Success",...}].
     */
    protected function isSuccessfulResponse(mixed $payload): bool
    {
        if (!is_array($payload)) {
            return false;
        }

        if (isset($payload['status']) && (string) $payload['status'] === '1000') {
            return true;
        }

        if (isset($payload['status_code']) && (string) $payload['status_code'] === '1000') {
            return true;
        }

        if (isset($payload[0]) && is_array($payload[0])) {
            $first = $payload[0];
            if ((string) ($first['status_code'] ?? '') === '1000') {
                return true;
            }
            if ((string) ($first['status'] ?? '') === '1000') {
                return true;
            }
            if (strtolower((string) ($first['status_desc'] ?? '')) === 'success') {
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
