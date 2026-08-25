<?php

namespace App\Jobs;

use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendBulkCustomSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 600;

    /**
     * @param  list<string>  $phoneNumbers
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public array $phoneNumbers,
        public string $message,
        public array $context = [],
    ) {
        $this->onQueue('default');
    }

    public function handle(SmsService $sms): void
    {
        $pause = (int) config('sms.bulk_pause_microseconds', 300000);
        $sent = 0;
        $failed = 0;

        foreach (array_values($this->phoneNumbers) as $index => $phone) {
            if ($index > 0 && $pause > 0) {
                usleep($pause);
            }

            if ($sms->send((string) $phone, $this->message)) {
                $sent++;
            } else {
                $failed++;
            }
        }

        Log::info('Bulk custom SMS job finished', array_merge($this->context, [
            'queued' => count($this->phoneNumbers),
            'sms_sent' => $sent,
            'sms_failed' => $failed,
        ]));
    }
}
