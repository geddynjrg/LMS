<?php

namespace App\Jobs;

use App\Models\PhaseApplication;
use App\Services\WorkflowNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendDisbursementNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const TYPE_DISBURSED = 'disbursed';
    public const TYPE_NOT_DISBURSED = 'not_disbursed';

    public function __construct(
        public int $phaseApplicationId,
        public string $type,
    ) {
    }

    public function handle(WorkflowNotificationService $notifications): void
    {
        $application = PhaseApplication::find($this->phaseApplicationId);
        if (!$application) {
            return;
        }

        match ($this->type) {
            self::TYPE_DISBURSED => $notifications->notifyGroupMembersDisbursed($application),
            self::TYPE_NOT_DISBURSED => $notifications->notifyGroupMembersDisbursementFailed($application),
            default => null,
        };
    }
}
