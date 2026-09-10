<?php

namespace App\Jobs;

use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SendBulkCustomSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 300;

    /**
     * @param  list<string>  $phoneNumbers
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public array $phoneNumbers,
        public string $message,
        public array $context = [],
    ) {
        $this->onQueue('sms');
    }

    public function handle(SmsService $sms): void
    {
        $pause = (int) config('sms.bulk_pause_microseconds', 300000);
        $sent = 0;
        $failed = 0;
        $creditsExhausted = false;

        if ($sms->creditsAreExhausted()) {
            $failed = count($this->phoneNumbers);
            $creditsExhausted = true;
            $this->storeResult($sent, $failed, $creditsExhausted);
            Log::warning('Bulk custom SMS skipped: EBIS credits exhausted', $this->context);

            return;
        }

        foreach (array_values($this->phoneNumbers) as $index => $phone) {
            if ($sms->creditsAreExhausted()) {
                $failed += count($this->phoneNumbers) - $index;
                $creditsExhausted = true;
                break;
            }

            if ($index > 0 && $pause > 0) {
                usleep($pause);
            }

            $result = $sms->sendDetailed((string) $phone, $this->message);
            if ($result['ok']) {
                $sent++;
                continue;
            }

            $failed++;
            if ($result['credits_exhausted']) {
                $remaining = count($this->phoneNumbers) - $index - 1;
                $failed += $remaining;
                $creditsExhausted = true;
                break;
            }
        }

        $this->storeResult($sent, $failed, $creditsExhausted);

        Log::info('Bulk custom SMS job finished', array_merge($this->context, [
            'queued' => count($this->phoneNumbers),
            'sms_sent' => $sent,
            'sms_failed' => $failed,
            'credits_exhausted' => $creditsExhausted,
        ]));
    }

    protected function storeResult(int $sent, int $failed, bool $creditsExhausted): void
    {
        $key = $this->context['result_key'] ?? null;
        if (! $key) {
            return;
        }

        $lock = Cache::lock($key.':lock', 15);
        $lock->block(10, function () use ($key, $sent, $failed, $creditsExhausted) {
            $current = Cache::get($key, [
                'status' => 'running',
                'queued' => 0,
                'sms_sent' => 0,
                'sms_failed' => 0,
                'batches_done' => 0,
                'batches' => 1,
                'credits_exhausted' => false,
                'error' => null,
            ]);

            $current['sms_sent'] = (int) ($current['sms_sent'] ?? 0) + $sent;
            $current['sms_failed'] = (int) ($current['sms_failed'] ?? 0) + $failed;
            $current['batches_done'] = (int) ($current['batches_done'] ?? 0) + 1;
            $current['credits_exhausted'] = $current['credits_exhausted'] || $creditsExhausted;
            if ($creditsExhausted) {
                $current['error'] = 'EBIS bulk SMS credits are exhausted. Top up the EBIS account, then retry.';
            }

            $batches = max(1, (int) ($current['batches'] ?? 1));
            if ($current['batches_done'] >= $batches || $creditsExhausted) {
                $current['status'] = 'done';
            } else {
                $current['status'] = 'running';
            }

            Cache::put($key, $current, now()->addHour());
        });
    }
}
