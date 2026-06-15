<?php

namespace App\Livewire\Concerns;

use Livewire\Attributes\Computed;

trait ManagesApplicationReviewWorkflow
{
    #[Computed]
    public function workflowSteps(): array
    {
        $hasPhase = ! is_null($this->application->phaseApplication);
        $verifyDone = in_array($this->application->link_status, ['linked', 'attempted_no_match'], true);
        $isPending = $hasPhase && $this->application->phaseApplication->status === 'pending';
        $decisionDone = $hasPhase && ! $isPending;

        return [
            [
                'key' => 'assign',
                'label' => 'Assign phase',
                'status' => $hasPhase ? 'done' : 'active',
            ],
            [
                'key' => 'verify',
                'label' => 'Verify identity',
                'status' => ! $hasPhase ? 'locked' : ($verifyDone ? 'done' : 'active'),
            ],
            [
                'key' => 'review',
                'label' => 'Review details',
                'status' => $hasPhase ? ($decisionDone ? 'done' : 'active') : 'locked',
            ],
            [
                'key' => 'decide',
                'label' => 'Approve / reject',
                'status' => ! $hasPhase || ! $verifyDone ? 'locked' : ($decisionDone ? 'done' : 'active'),
            ],
        ];
    }

    #[Computed]
    public function nextAction(): array
    {
        if (! auth()->user()?->can('review application')) {
            return [
                'message' => 'Read-only view — you do not have permission to review this application.',
                'tone' => 'muted',
                'icon' => 'fa-eye',
            ];
        }

        if (is_null($this->application->phaseApplication)) {
            return [
                'message' => 'Assign this application to a funding phase to begin review.',
                'tone' => 'warning',
                'icon' => 'fa-layer-group',
                'primary' => null,
            ];
        }

        if ($this->application->link_status === 'not_attempted') {
            return [
                'message' => 'Review possible duplicate groups and link the correct master record, or confirm as a new group.',
                'tone' => 'warning',
                'icon' => 'fa-fingerprint',
                'primary' => 'findMatches',
                'primaryLabel' => 'Review matches',
            ];
        }

        if ($this->application->phaseApplication->status !== 'pending') {
            return [
                'message' => 'Decision recorded. See the action panel or audit trail for details.',
                'tone' => 'success',
                'icon' => 'fa-check-circle',
                'primary' => null,
            ];
        }

        if ($this->memberConflictCount > 0) {
            return [
                'message' => 'Verification complete. Check the Members tab for duplicate member alerts before approving.',
                'tone' => 'info',
                'icon' => 'fa-users',
                'primary' => 'approve',
                'primaryLabel' => 'Approve application',
            ];
        }

        if ($this->application->link_status === 'linked') {
            $name = $this->application->phaseApplication->linkedGroup?->name ?? 'master group';

            return [
                'message' => "Linked to {$name} (Endeleza). You can approve or reject this application.",
                'tone' => 'success',
                'icon' => 'fa-link',
                'primary' => 'approve',
                'primaryLabel' => 'Approve application',
            ];
        }

        return [
            'message' => 'Verified as a new group (Wezesha). You can approve or reject this application.',
            'tone' => 'success',
            'icon' => 'fa-plus-circle',
            'primary' => 'approve',
            'primaryLabel' => 'Approve application',
        ];
    }

    #[Computed]
    public function approveBlockedReason(): ?string
    {
        if ($this->application->phaseApplication?->status !== 'pending') {
            return null;
        }

        if ($this->application->link_status === 'not_attempted') {
            return 'Complete identity verification before approving.';
        }

        return null;
    }

    #[Computed]
    public function verificationBanner(): ?array
    {
        if (is_null($this->application->phaseApplication)) {
            return null;
        }

        if ($this->application->phaseApplication->group_id) {
            return [
                'tone' => 'success',
                'title' => 'Linked to existing group',
                'body' => ($this->application->phaseApplication->linkedGroup?->name ?? 'Master group') . ' · Endeleza',
                'icon' => 'fa-link',
            ];
        }

        if ($this->application->link_status === 'attempted_no_match') {
            $auto = config('applications.auto_system_verification');

            return [
                'tone' => 'info',
                'title' => 'New group verified',
                'body' => 'No master record match — will register as Wezesha on approval.' . ($auto ? ' (Automated check)' : ''),
                'icon' => 'fa-plus-circle',
            ];
        }

        if ($this->application->link_status === 'not_attempted') {
            return [
                'tone' => 'warning',
                'title' => 'Manual verification required',
                'body' => 'Possible duplicate groups found or check not completed yet.',
                'icon' => 'fa-exclamation-triangle',
            ];
        }

        return null;
    }

    #[Computed]
    public function reviewTabOptions(): array
    {
        $conflicts = $this->memberConflictCount;

        return [
            ['key' => 'all-details', 'label' => 'Application', 'icon' => 'fa-file-alt', 'badge' => null],
            ['key' => 'members-details', 'label' => 'Members', 'icon' => 'fa-users', 'badge' => $conflicts > 0 ? $conflicts : $this->application->groupMembers->count()],
            ['key' => 'loan-history', 'label' => 'Loan history', 'icon' => 'fa-hand-holding-usd', 'badge' => null],
            ['key' => 'documents', 'label' => 'Documents', 'icon' => 'fa-folder-open', 'badge' => null],
            ['key' => 'audit-trail', 'label' => 'Activity', 'icon' => 'fa-history', 'badge' => null],
        ];
    }
}
