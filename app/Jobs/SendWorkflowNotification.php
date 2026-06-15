<?php

namespace App\Jobs;

use App\Models\LoanPhase;
use App\Services\WorkflowNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWorkflowNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const TYPE_SECRETARIAT_PHASE_READY = 'secretariat_phase_ready';
    public const TYPE_TRAINING_SCHEDULE_REQUIRED = 'training_schedule_required';

    public function __construct(
        public int $phaseId,
        public string $type,
    ) {
    }

    public function handle(WorkflowNotificationService $notifications): void
    {
        $phase = LoanPhase::find($this->phaseId);
        if (!$phase) {
            return;
        }

        match ($this->type) {
            self::TYPE_SECRETARIAT_PHASE_READY => $notifications->notifyHeadOfSecretariatPhaseReady($phase),
            self::TYPE_TRAINING_SCHEDULE_REQUIRED => $notifications->notifyConstituencyOfficersTrainingAuthorized($phase),
            default => null,
        };
    }
}
