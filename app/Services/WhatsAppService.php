<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp Cloud API channel stub for Core LMS.
 * Primary alerts remain on SmsService (EBIS). Enable with WHATSAPP_ENABLED=true.
 */
class WhatsAppService
{
    public function isEnabled(): bool
    {
        return (bool) config('whatsapp.enabled', env('WHATSAPP_ENABLED', false))
            && filled(config('whatsapp.access_token', env('WHATSAPP_ACCESS_TOKEN')))
            && filled(config('whatsapp.phone_number_id', env('WHATSAPP_PHONE_NUMBER_ID')));
    }

    /**
     * @return array{ok:bool, stub?:bool, error?:string}
     */
    public function sendText(string $phoneNumber, string $message): array
    {
        if (! $this->isEnabled()) {
            Log::info('WhatsApp stubbed (disabled)', ['phone' => $phoneNumber]);

            return [
                'ok' => false,
                'stub' => true,
                'error' => 'WhatsApp not enabled. Configure WHATSAPP_* env vars.',
            ];
        }

        $mobile = preg_replace('/\D/', '', $phoneNumber) ?? '';
        if (str_starts_with($mobile, '0')) {
            $mobile = '254'.substr($mobile, 1);
        }

        $version = config('whatsapp.graph_api_version', env('WHATSAPP_GRAPH_API_VERSION', 'v19.0'));
        $phoneId = config('whatsapp.phone_number_id', env('WHATSAPP_PHONE_NUMBER_ID'));
        $token = config('whatsapp.access_token', env('WHATSAPP_ACCESS_TOKEN'));

        try {
            $res = Http::timeout(30)
                ->withToken($token)
                ->post("https://graph.facebook.com/{$version}/{$phoneId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => $mobile,
                    'type' => 'text',
                    'text' => ['body' => $message],
                ]);

            return [
                'ok' => $res->successful(),
                'error' => $res->successful() ? null : ('HTTP '.$res->status()),
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}
