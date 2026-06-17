<?php

namespace App\Support;

use App\Models\LoanPhase;
use App\Models\SecretariatApproval;
use App\Services\Loan\SecretariatApprovalService;

class PhaseWorkflowStatus
{
    public const PROCESSING_STATUSES = ['submitted_to_hq', 'approved', 'in_training'];

    public const COMPLETED_STATUSES = ['disbursed', 'rejected'];

    /**
     * @return list<string>
     */
    public static function processingStatuses(): array
    {
        return self::PROCESSING_STATUSES;
    }

    /**
     * @return list<string>
     */
    public static function completedStatuses(): array
    {
        return self::COMPLETED_STATUSES;
    }

    public static function isProcessingStatus(string $status): bool
    {
        return in_array($status, self::PROCESSING_STATUSES, true);
    }

    public static function isCompletedStatus(string $status): bool
    {
        return in_array($status, self::COMPLETED_STATUSES, true);
    }

    public static function tabForStatus(string $status): string
    {
        if ($status === 'draft') {
            return 'pending';
        }

        if (self::isProcessingStatus($status)) {
            return 'processing';
        }

        if (self::isCompletedStatus($status)) {
            return 'completed';
        }

        return 'completed';
    }

    /**
     * @return array{label: string, value: string, class: string}
     */
    public static function forPhase(LoanPhase $phase): array
    {
        $phase->loadMissing('secretariatApprovals');

        $hasTrainingAuth = app(SecretariatApprovalService::class)
            ->hasApproval($phase, SecretariatApproval::TYPE_TRAINING_AUTHORIZATION);

        return match ($phase->status) {
            'submitted_to_hq' => [
                'label' => 'At HQ Review',
                'value' => 'at_hq_review',
                'class' => 'bg-blue-50 border-blue-200 text-blue-700',
            ],
            'approved' => $hasTrainingAuth
                ? [
                    'label' => 'Schedule Training',
                    'value' => 'schedule_training',
                    'class' => 'bg-indigo-50 border-indigo-200 text-indigo-700',
                ]
                : [
                    'label' => 'Awaiting Head of Secretariat Approval',
                    'value' => 'awaiting_secretariat',
                    'class' => 'bg-amber-50 border-amber-200 text-amber-700',
                ],
            'in_training' => [
                'label' => 'Training',
                'value' => 'training',
                'class' => 'bg-purple-50 border-purple-200 text-purple-700',
            ],
            'disbursed' => [
                'label' => 'Disbursed',
                'value' => 'disbursed',
                'class' => 'bg-green-50 border-green-200 text-green-700',
            ],
            'rejected' => [
                'label' => 'Rejected',
                'value' => 'rejected',
                'class' => 'bg-red-50 border-red-200 text-red-700',
            ],
            'draft' => [
                'label' => 'Draft',
                'value' => 'draft',
                'class' => 'bg-gray-50 border-gray-200 text-gray-700',
            ],
            default => [
                'label' => ucwords(str_replace('_', ' ', $phase->status)),
                'value' => $phase->status,
                'class' => 'bg-gray-50 border-gray-200 text-gray-700',
            ],
        };
    }
}
