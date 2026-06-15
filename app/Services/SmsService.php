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
            if ((string) ($payload['status'] ?? '') === '1000') {
                Log::info('SMS sent successfully', ['phone' => $mobile]);

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
