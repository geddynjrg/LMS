<?php

namespace App\Jobs;

use App\Models\LoanPhase;
use App\Services\WorkflowNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendTrainingScheduleNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $loanPhaseId)
    {
    }

    public function handle(WorkflowNotificationService $notifications): void
    {
        $phase = LoanPhase::query()
            ->with([
                'phaseApplications' => fn ($query) => $query
                    ->where('status', 'in_training')
                    ->with(['tempApplication.groupMembers']),
            ])
            ->find($this->loanPhaseId);

        if (!$phase || !$phase->training_start_date) {
            return;
        }

        foreach ($phase->phaseApplications as $application) {
            $notifications->notifyGroupMembersTrainingScheduled($application, $phase);
        }
    }
}
