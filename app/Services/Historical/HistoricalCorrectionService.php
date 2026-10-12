<?php

namespace App\Services\Historical;

use App\Models\GroupLoan;
use App\Models\HistoricalCorrectionRequest;
use App\Models\User;
use App\Services\WorkflowNotificationService;
use App\Support\HqAccess;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class HistoricalCorrectionService
{
    public function __construct(
        protected WorkflowNotificationService $notifications,
    ) {}

    public function hqCanCorrectHistorical(User $user, GroupLoan $groupLoan): bool
    {
        $groupLoan->loadMissing('loanPhase');

        return HqAccess::userCanAccessRoutes($user)
            && $groupLoan->loanPhase
            && $groupLoan->loanPhase->isHistoricalUpload();
    }

    /**
     * @param  list<string>  $fields
     */
    public function requestGroupLoanCorrection(
        User $hqUser,
        GroupLoan $groupLoan,
        array $fields,
        ?string $message = null,
    ): HistoricalCorrectionRequest {
        if (! $this->hqCanCorrectHistorical($hqUser, $groupLoan)) {
            throw ValidationException::withMessages([
                'correction' => 'Only HQ can request corrections on historical group loans.',
            ]);
        }

        $allowed = array_keys(HistoricalCorrectionRequest::FIELD_LABELS);
        $fields = array_values(array_intersect($fields, $allowed));
        $fields = array_values(array_filter($fields, fn ($f) => $f !== 'allocation_profile'));

        if ($fields === []) {
            throw ValidationException::withMessages([
                'fields' => 'Select at least one field for the field officer to correct.',
            ]);
        }

        $groupLoan->loadMissing(['group', 'loanPhase.constituency.county']);
        $phase = $groupLoan->loanPhase;

        $request = HistoricalCorrectionRequest::create([
            'type' => HistoricalCorrectionRequest::TYPE_GROUP_LOAN,
            'constituency_code' => $phase->constituency_code,
            'group_loan_id' => $groupLoan->id,
            'requested_by' => $hqUser->id,
            'fields' => $fields,
            'message' => $message ? trim($message) : null,
            'status' => HistoricalCorrectionRequest::STATUS_PENDING,
        ]);

        $labels = $request->fieldLabels();
        $groupName = $groupLoan->group?->name ?? 'Group loan';
        $phaseName = $phase->phase ?? 'historical phase';
        $url = $this->groupLoanEditUrl($groupLoan);

        $this->notifications->notifyConstituencyOfficersHistoricalCorrection(
            $phase->constituency_code,
            "Correction needed: {$groupName}",
            [
                "Headquarters has asked you to correct historical loan data for {$groupName} ({$phaseName}).",
                'Please update: '.implode('; ', $labels).'.',
                $request->message ? 'HQ note: '.$request->message : 'Please use the group loans edit screen to make the correction.',
                'This login prompt will clear after you save the corrected values.',
            ],
            $url,
            'Open group loans',
            "Uwezo Fund: HQ requests historical data correction for {$groupName}. Update: ".implode(', ', $labels).'.',
        );

        return $request;
    }

    public function requestAllocationProfileCorrection(
        User $hqUser,
        string $constituencyCode,
        ?string $message = null,
    ): HistoricalCorrectionRequest {
        if (! HqAccess::userCanAccessRoutes($hqUser)) {
            throw ValidationException::withMessages([
                'correction' => 'Only HQ can request allocation profile corrections.',
            ]);
        }

        $request = HistoricalCorrectionRequest::create([
            'type' => HistoricalCorrectionRequest::TYPE_ALLOCATION_PROFILE,
            'constituency_code' => $constituencyCode,
            'group_loan_id' => null,
            'requested_by' => $hqUser->id,
            'fields' => ['allocation_profile'],
            'message' => $message ? trim($message) : null,
            'status' => HistoricalCorrectionRequest::STATUS_PENDING,
        ]);

        $url = $this->allocationProfileUrl($constituencyCode);

        $this->notifications->notifyConstituencyOfficersHistoricalCorrection(
            $constituencyCode,
            'Validate constituency allocation profile',
            [
                'Headquarters has asked you to validate the constituency allocation profile.',
                'The profile is read-only at constituency level. Review the figures and confirm accuracy, or reply to HQ if values need changing.',
                $request->message ? 'HQ note: '.$request->message : null,
                'Open the allocation profile page, then mark this prompt as reviewed after validation.',
            ],
            $url,
            'Open allocation profile',
            'Uwezo Fund: HQ asks you to validate the constituency allocation profile in LMS.',
        );

        return $request;
    }

    /**
     * @return Collection<int, HistoricalCorrectionRequest>
     */
    public function pendingForOfficer(User $user): Collection
    {
        $codes = $user->constituencies()->pluck('constituency_code')->filter()->values();
        $current = $user->currentConstituency?->constituency_code;
        if ($current) {
            $codes->push($current);
        }
        $codes = $codes->unique()->values();

        if ($codes->isEmpty()) {
            return collect();
        }

        return HistoricalCorrectionRequest::query()
            ->with(['groupLoan.group', 'groupLoan.loanPhase', 'constituency.county', 'requester'])
            ->where('status', HistoricalCorrectionRequest::STATUS_PENDING)
            ->whereIn('constituency_code', $codes)
            ->orderByDesc('id')
            ->get();
    }

    public function markCompleted(HistoricalCorrectionRequest $request, User $user): void
    {
        if (! $request->isPending()) {
            return;
        }

        $request->update([
            'status' => HistoricalCorrectionRequest::STATUS_COMPLETED,
            'resolved_by' => $user->id,
            'resolved_at' => now(),
        ]);
    }

    /**
     * After a field officer saves group loan fields, complete matching pending requests.
     *
     * @param  list<string>  $updatedFields
     */
    public function resolveGroupLoanRequests(GroupLoan $groupLoan, User $user, array $updatedFields): void
    {
        $updatedFields = array_values(array_unique($updatedFields));
        if ($updatedFields === []) {
            return;
        }

        $pending = HistoricalCorrectionRequest::query()
            ->where('status', HistoricalCorrectionRequest::STATUS_PENDING)
            ->where('type', HistoricalCorrectionRequest::TYPE_GROUP_LOAN)
            ->where('group_loan_id', $groupLoan->id)
            ->get();

        foreach ($pending as $request) {
            $needed = collect($request->fields ?? []);
            if ($needed->diff($updatedFields)->isEmpty()) {
                $this->markCompleted($request, $user);
            }
        }
    }

    protected function groupLoanEditUrl(GroupLoan $groupLoan): ?string
    {
        $groupLoan->loadMissing(['loanPhase.constituency.county']);
        $phase = $groupLoan->loanPhase;
        $countySlug = $phase?->constituency?->county?->slug;
        $constituencySlug = $phase?->constituency?->slug;

        if (! $countySlug || ! $constituencySlug) {
            return null;
        }

        return route('const.loans.grouploans', [$countySlug, $constituencySlug]);
    }

    protected function allocationProfileUrl(string $constituencyCode): ?string
    {
        $constituency = \App\Models\Constituency::query()
            ->with('county')
            ->where('constituency_code', $constituencyCode)
            ->first();

        if (! $constituency?->slug || ! $constituency->county?->slug) {
            return null;
        }

        return route('const.allocations.index', [
            $constituency->county->slug,
            $constituency->slug,
        ]);
    }
}
