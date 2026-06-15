<?php

namespace App\Jobs;

use App\Models\PhaseApplication;
use App\Services\WorkflowNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendTrainingCompletionNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $phaseApplicationId)
    {
    }

    public function handle(WorkflowNotificationService $notifications): void
    {
        $application = PhaseApplication::find($this->phaseApplicationId);
        if (!$application || !$application->is_trained) {
            return;
        }

        $notifications->notifyGroupMembersTrainingCompleted($application);
        $notifications->notifyHeadOfSecretariatTrainingCompleted($application);
    }
}
