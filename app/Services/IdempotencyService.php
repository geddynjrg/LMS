<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class IdempotencyService
{
    /**
     * Attempt to acquire a unique lock for an action.
     */
    public function acquire(string $key, array $payload = []): bool
    {
        try {
            DB::table('idempotency_keys')->insert([
                'key'             => $key,
                'status'          => 'processing',
                'user_id'         => auth()->id(),
                'ip_address'      => request()->ip(),
                'user_agent'      => request()->userAgent(),
                'request_payload' => json_encode($payload),
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            return true;
        } catch (QueryException $e) {
            return false;
        }
    }

    /**
     * Mark a key as successful to prevent re-runs.
     */
    public function complete(string $key): void
    {
        DB::table('idempotency_keys')
            ->where('key', $key)
            ->update([
                'status' => 'completed',
                'updated_at' => now()
            ]);
    }

    /**
     * Remove a key.
     */
    public function release(string $key): void
    {
        DB::table('idempotency_keys')->where('key', $key)->delete();
    }
}
