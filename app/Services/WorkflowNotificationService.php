<?php

namespace App\Services;

use App\Mail\ConstituencyOfficerNotificationMail;
use App\Mail\SecretariatReviewRequiredMail;
use App\Mail\TrainingCompletedSecretariatMail;
use App\Mail\TrainingScheduleRequiredMail;
use App\Models\LoanPhase;
use App\Models\PhaseApplication;
use App\Models\TempApplication;
use App\Models\Constituency;
use App\Support\SystemRoles;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class WorkflowNotificationService
{
    public function __construct(protected SmsService $smsService)
    {
    }

    public function notifyConstituencyOfficersNewApplication(TempApplication $application, bool $queue = true): void
    {
        $application->loadMissing(['ward.constituency.county']);

        $constituencyCode = $application->constituency_code
            ?: $application->ward?->constituency?->constituency_code;

        if (!$constituencyCode) {
            Log::warning('Cannot notify constituency officers: missing constituency code', [
                'application_id' => $application->application_id,
            ]);

            return;
        }

        $constituencyName = $application->constituency_name
            ?: $application->ward?->constituency?->constituency_name
            ?: $constituencyCode;

        $dashboardUrl = $this->buildConstituencyDashboardUrl($constituencyCode);
        $amount = number_format((float) ($application->loan_amount ?? 0));

        $this->notifyConstituencyOfficersByCode(
            $constituencyCode,
            'New loan application received',
            "New loan application {$application->application_id}",
            [
                "A new loan application has been received from the Uwezo loan portal.",
                "Application: {$application->application_id}",
                "Group: {$application->group_name}",
                "Constituency: {$constituencyName}",
                "Amount requested: KES {$amount}",
                'Please review the application on the constituency dashboard.',
            ],
            $dashboardUrl,
            'Review application',
            "Uwezo Fund: New application {$application->application_id} for {$application->group_name} (KES {$amount}) received in {$constituencyName}. Review in LMS.",
            $queue,
        );
    }

    public function notifyConstituencyOfficersPhaseReturned(LoanPhase $phase, string $reason, bool $queue = true): void
    {
        $phase->loadMissing(['constituency.county']);
        $constituencyName = $phase->constituency?->constituency_name ?? $phase->constituency_code;
        $phaseUrl = $this->buildConstituencyPhaseUrl($phase);

        $this->notifyConstituencyOfficersByCode(
            $phase->constituency_code,
            'Phase returned for amendment',
            "{$phase->phase} returned by HQ for amendment",
            [
                "Headquarters has returned {$phase->phase} in {$constituencyName} for amendment before resubmission.",
                'Reason: ' . $reason,
                'Please update the batch and resubmit to HQ when ready.',
            ],
            $phaseUrl,
            'Open phase',
            "Uwezo Fund: {$phase->phase} ({$constituencyName}) was returned by HQ for amendment. Reason: {$reason}",
            $queue,
        );
    }

    public function notifyConstituencyOfficersHqApplicationAction(
        PhaseApplication $phaseApplication,
        string $actionLabel,
        string $details,
        bool $queue = true,
    ): void {
        $phaseApplication->loadMissing([
            'tempApplication:id,application_id,group_name,constituency_code',
            'loanPhase.constituency.county',
        ]);

        $phase = $phaseApplication->loanPhase;
        $tempApp = $phaseApplication->tempApplication;

        if (!$phase || !$tempApp) {
            return;
        }

        $constituencyName = $phase->constituency?->constituency_name ?? $phase->constituency_code;
        $phaseUrl = $this->buildConstituencyPhaseUrl($phase);

        $this->notifyConstituencyOfficersByCode(
            $phase->constituency_code,
            'HQ action on loan application',
            "HQ {$actionLabel}: {$tempApp->application_id}",
            [
                "Headquarters has {$actionLabel} application {$tempApp->application_id} ({$tempApp->group_name}) in {$phase->phase}.",
                $details,
            ],
            $phaseUrl,
            'View phase',
            "Uwezo Fund: HQ {$actionLabel} application {$tempApp->application_id} in {$constituencyName}. {$details}",
            $queue,
        );
    }

    protected function notifyConstituencyOfficersByCode(
        string $constituencyCode,
        string $emailSubject,
        string $headline,
        array $lines,
        ?string $actionUrl,
        ?string $actionLabel,
        ?string $smsMessage,
        bool $queue = true,
    ): void {
        $officers = $this->constituencyOfficers($constituencyCode);

        if ($officers->isEmpty()) {
            Log::warning('No constituency officers found for notification', [
                'constituency_code' => $constituencyCode,
                'headline' => $headline,
            ]);

            return;
        }

        foreach ($officers as $officer) {
            if ($officer->email) {
                $mail = new ConstituencyOfficerNotificationMail(
                    $headline,
                    $lines,
                    $actionUrl,
                    $actionLabel,
                );

                $queue
                    ? Mail::to($officer->email)->queue($mail)
                    : Mail::to($officer->email)->send($mail);
            }

            if ($smsMessage && $officer->phone_number) {
                $this->smsService->send($officer->phone_number, $smsMessage);
            }
        }
    }

    protected function constituencyOfficers(string $constituencyCode): Collection
    {
        return User::role('constituency_officer')
            ->active()
            ->whereHas('constituencies', fn ($q) => $q->where('constituency_code', $constituencyCode))
            ->get();
    }

    protected function buildConstituencyDashboardUrl(string $constituencyCode): ?string
    {
        $constituency = Constituency::query()
            ->with('county:id,slug')
            ->where('constituency_code', $constituencyCode)
            ->first();

        if (!$constituency?->county?->slug || !$constituency->slug) {
            return null;
        }

        return route('const.app.app_dashboard', [$constituency->county->slug, $constituency->slug]);
    }

    protected function buildConstituencyPhaseUrl(LoanPhase $phase): ?string
    {
        $phase->loadMissing(['constituency.county']);

        $countySlug = $phase->constituency?->county?->slug;
        $constituencySlug = $phase->constituency?->slug;

        if (!$countySlug || !$constituencySlug || !$phase->slug) {
            return $this->buildConstituencyDashboardUrl($phase->constituency_code);
        }

        return route('const.app.app_phase_details', [$countySlug, $constituencySlug, $phase->slug]);
    }

    public function notifyHeadOfSecretariatPhaseReady(LoanPhase $phase, bool $queue = true): void
    {
        $phase->loadMissing(['constituency']);

        $head = SystemRoles::headOfSecretariatUser();
        if (!$head || !$head->isActive()) {
            Log::warning('No active Head of Secretariat to notify for phase', ['phase_id' => $phase->id]);

            return;
        }

        $constituencyName = $phase->constituency?->constituency_name ?? $phase->constituency_code;
        $reviewUrl = route('secretariat.phases.review', $phase);

        if ($head->email) {
            $mail = new SecretariatReviewRequiredMail($phase, $reviewUrl);
            $queue ? Mail::to($head->email)->queue($mail) : Mail::to($head->email)->send($mail);
        }

        if ($head->phone_number) {
            $this->smsService->send(
                $head->phone_number,
                "Uwezo Fund: {$phase->phase} ({$constituencyName}) is HQ-approved and needs your secretariat sign-off for training and disbursement. Review: {$reviewUrl}"
            );
        }
    }

    public function notifyConstituencyOfficersTrainingAuthorized(LoanPhase $phase, bool $queue = true): void
    {
        $phase->loadMissing(['constituency.county']);

        $officers = User::role('constituency_officer')
            ->active()
            ->whereHas('constituencies', fn ($q) => $q->where('constituency_code', $phase->constituency_code))
            ->get();

        if ($officers->isEmpty()) {
            Log::warning('No constituency officers found for training notification', [
                'phase_id' => $phase->id,
                'constituency_code' => $phase->constituency_code,
            ]);

            return;
        }

        $countySlug = $phase->constituency?->county?->slug ?? 'county';
        $constituencySlug = $phase->constituency?->slug ?? $phase->constituency_code;
        $phaseUrl = route('const.app.app_phase_details', [$countySlug, $constituencySlug, $phase->slug]);
        $constituencyName = $phase->constituency?->constituency_name ?? $phase->constituency_code;

        foreach ($officers as $officer) {
            if ($officer->email) {
                $mail = new TrainingScheduleRequiredMail($phase, $phaseUrl);
                $queue ? Mail::to($officer->email)->queue($mail) : Mail::to($officer->email)->send($mail);
            }

            if ($officer->phone_number) {
                $this->smsService->send(
                    $officer->phone_number,
                    "Uwezo Fund: Secretariat approved training for {$phase->phase} ({$constituencyName}). Please schedule training dates in LMS: {$phaseUrl}"
                );
            }
        }
    }

    public function notifyHeadOfSecretariatTrainingCompleted(PhaseApplication $application, bool $queue = true): void
    {
        $application->loadMissing([
            'tempApplication:id,application_id,group_name',
            'loanPhase.constituency:constituency_code,constituency_name',
        ]);

        $head = SystemRoles::headOfSecretariatUser();
        if (!$head || !$head->isActive()) {
            return;
        }

        $phase = $application->loanPhase;
        $appId = $application->tempApplication?->application_id ?? $application->id;
        $constituencyName = $phase?->constituency?->constituency_name ?? $phase?->constituency_code;
        $venue = $phase?->training_venue ?? 'N/A';
        $markedAt = $application->training_completed_at?->format('d M Y H:i') ?? now()->format('d M Y H:i');
        $reviewUrl = route('secretariat.phases.index');

        if ($head->email) {
            $mail = new TrainingCompletedSecretariatMail($application, $reviewUrl);
            $queue ? Mail::to($head->email)->queue($mail) : Mail::to($head->email)->send($mail);
        }

        if ($head->phone_number) {
            $this->smsService->send(
                $head->phone_number,
                "Uwezo Fund: {$phase?->phase} ({$constituencyName}) — group {$appId} training marked complete on {$markedAt} at {$venue}. Members notified."
            );
        }
    }

    public function notifyGroupMembersTrainingCompleted(PhaseApplication $application): void
    {
        $application->loadMissing([
            'tempApplication.groupMembers',
            'loanPhase:id,phase,training_start_date,training_end_date,training_venue,constituency_code',
            'loanPhase.constituency:constituency_code,constituency_name',
        ]);

        $tempApp = $application->tempApplication;
        if (!$tempApp) {
            return;
        }

        $appId = $tempApp->application_id;
        $groupName = $tempApp->group_name;
        $venue = $application->loanPhase?->training_venue ?? 'TBA';
        $markedAt = $application->training_completed_at?->format('d M Y') ?? now()->format('d M Y');
        $phaseName = $application->loanPhase?->phase ?? 'batch';

        foreach ($tempApp->groupMembers as $member) {
            if (empty($member->phone_number)) {
                continue;
            }

            $this->smsService->send(
                $member->phone_number,
                "Uwezo Fund: Dear {$member->name}, {$groupName} (Application No. {$appId}) successfully completed {$phaseName} training on {$markedAt} at {$venue}. "
                . "Keep this application number for your records. Your training certificate is now available for download on the Uwezo portal and LMS."
            );
        }
    }

    public function notifyGroupMembersDisbursed(PhaseApplication $application): void
    {
        $application->loadMissing([
            'tempApplication.groupMembers',
            'loanPhase:id,phase,constituency_code',
            'loanPhase.constituency:constituency_code,constituency_name',
        ]);

        $tempApp = $application->tempApplication;
        if (!$tempApp) {
            return;
        }

        $appId = $tempApp->application_id;
        $groupName = $tempApp->group_name;
        $amount = number_format((float) ($application->amount_issued ?? $application->amount_approved ?? 0));
        $cheque = $application->cheque_number ?: 'N/A';
        $phaseName = $application->loanPhase?->phase ?? 'batch';

        foreach ($tempApp->groupMembers as $member) {
            if (empty($member->phone_number)) {
                continue;
            }

            $this->smsService->send(
                $member->phone_number,
                "Uwezo Fund: Dear {$member->name}, {$groupName} (Application No. {$appId}) has received KES {$amount} "
                . "for {$phaseName}. Cheque/ref: {$cheque}. Repay promptly to remain eligible for future loans. "
                . "Track your loan on the Uwezo portal."
            );
        }
    }

    public function notifyGroupMembersDisbursementFailed(PhaseApplication $application): void
    {
        $application->loadMissing([
            'tempApplication.groupMembers',
            'loanPhase:id,phase',
        ]);

        $tempApp = $application->tempApplication;
        if (!$tempApp) {
            return;
        }

        $appId = $tempApp->application_id;
        $groupName = $tempApp->group_name;
        $reason = $application->disbursement_failure_notes ?: 'Disbursement was not completed at the constituency office.';
        $phaseName = $application->loanPhase?->phase ?? 'batch';

        foreach ($tempApp->groupMembers as $member) {
            if (empty($member->phone_number)) {
                continue;
            }

            $this->smsService->send(
                $member->phone_number,
                "Uwezo Fund: Dear {$member->name}, disbursement for {$groupName} (Application No. {$appId}, {$phaseName}) "
                . "was not completed. Reason: {$reason} Contact your constituency office for next steps."
            );
        }
    }
}
