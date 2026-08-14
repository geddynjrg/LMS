<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EventHashClient
{
    public const SOURCE = 'core_lms';

    /**
     * Hash a non-PII event payload and persist the receipt.
     *
     * @param  array<string, mixed>  $payload
     * @return array{receipt_id:?string,event_hash:?string}|null
     */
    public function record(
        string $entityType,
        string|int $entityId,
        string $phaseAction,
        array $payload,
        ?string $prevHash = null
    ): ?array {
        $url = rtrim((string) config('services.event_hasher.url', env('EVENT_HASHER_URL', 'http://uwezo-event-hasher:8090')), '/');
        $token = (string) config('services.event_hasher.token', env('EVENT_HASHER_TOKEN', ''));

        if ($url === '' || $token === '') {
            Log::warning('[EventHash] Skipped: EVENT_HASHER_URL/TOKEN not configured');

            return null;
        }

        $safePayload = $this->stripSensitive($payload);
        $canonical = $this->canonicalize($safePayload);
        $localHash = hash('sha256', json_encode($canonical, JSON_UNESCAPED_SLASHES));

        try {
            $response = Http::timeout(5)
                ->withToken($token)
                ->acceptJson()
                ->post($url.'/v1/hash', [
                    'event_hash' => $localHash,
                    'meta' => [
                        'source_system' => self::SOURCE,
                        'entity_type' => $entityType,
                        'phase_action' => $phaseAction,
                    ],
                ]);

            if (! $response->successful()) {
                Log::warning('[EventHash] Hasher HTTP failure', [
                    'status' => $response->status(),
                ]);

                return null;
            }

            $data = $response->json();
            $receiptId = $data['receipt_id'] ?? (string) Str::uuid();
            $eventHash = strtolower((string) ($data['event_hash'] ?? $localHash));

            DB::table('event_hash_receipts')->insert([
                'receipt_id' => $receiptId,
                'source_system' => self::SOURCE,
                'entity_type' => $entityType,
                'entity_id' => (string) $entityId,
                'phase_action' => $phaseAction,
                'event_hash' => $eventHash,
                'prev_hash' => $prevHash,
                'created_at' => now(),
            ]);

            return [
                'receipt_id' => $receiptId,
                'event_hash' => $eventHash,
            ];
        } catch (\Throwable $e) {
            Log::warning('[EventHash] Failed to record receipt', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function previousHash(string $entityType, string|int $entityId): ?string
    {
        return DB::table('event_hash_receipts')
            ->where('source_system', self::SOURCE)
            ->where('entity_type', $entityType)
            ->where('entity_id', (string) $entityId)
            ->orderByDesc('id')
            ->value('event_hash');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function stripSensitive(array $payload): array
    {
        $blocked = [
            'national_id', 'id_no', 'idNo', 'phone', 'mobile', 'bank', 'account',
            'iban', 'kra', 'pin', 'email', 'name', 'full_name', 'password', 'otp',
        ];

        $clean = [];
        foreach ($payload as $key => $value) {
            $lk = strtolower((string) $key);
            foreach ($blocked as $needle) {
                if (str_contains($lk, $needle)) {
                    continue 2;
                }
            }
            if (is_array($value)) {
                $clean[$key] = $this->stripSensitive($value);
            } else {
                $clean[$key] = $value;
            }
        }

        return $clean;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function canonicalize(array $payload): array
    {
        ksort($payload);
        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $payload[$key] = $this->canonicalize($value);
            }
        }

        return $payload;
    }
}
