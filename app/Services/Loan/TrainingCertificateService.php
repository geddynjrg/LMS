<?php

namespace App\Services\Loan;

use App\Models\LoanPhase;
use App\Models\PhaseApplication;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TrainingCertificateService
{
    public const CERTIFICATE_WAIT_DAYS = 4;

    public function certificateEligibleFrom(?LoanPhase $phase): ?Carbon
    {
        if (!$phase?->training_start_date) {
            return null;
        }

        return $phase->training_start_date->copy()->startOfDay()->addDays(self::CERTIFICATE_WAIT_DAYS);
    }

    public function isCertificateDownloadAvailable(?LoanPhase $phase): bool
    {
        $eligibleFrom = $this->certificateEligibleFrom($phase);

        if (!$eligibleFrom) {
            return false;
        }

        return now()->startOfDay()->gte($eligibleFrom);
    }

    /**
     * Portal/API: certificate is available once disbursed (CO confirmed cheque pickup),
     * or after the 4-day wait for trained groups still awaiting disbursement.
     */
    public function isPortalCertificateAvailable(PhaseApplication $application): bool
    {
        if ($application->status === 'disbursed') {
            return true;
        }

        if (!$application->is_trained) {
            return false;
        }

        return $this->isCertificateDownloadAvailable($application->loanPhase);
    }

    public function isDisbursementCertificateAvailable(PhaseApplication $application): bool
    {
        return $application->status === 'disbursed';
    }

    public function certificateAvailabilityMessage(?LoanPhase $phase): ?string
    {
        $eligibleFrom = $this->certificateEligibleFrom($phase);

        if (!$eligibleFrom) {
            return null;
        }

        if ($this->isCertificateDownloadAvailable($phase)) {
            return null;
        }

        return 'Training certificate will be available for download from '
            . $eligibleFrom->format('d M Y')
            . ' (4 days after the scheduled training start date).';
    }

    public function generateAndStoreIfEligible(PhaseApplication $application): ?string
    {
        $application->loadMissing('loanPhase');

        if (!$this->isPortalCertificateAvailable($application)) {
            return null;
        }

        return $this->ensureStored(
            $application,
            enforceWaitPeriod: $application->status !== 'disbursed',
        );
    }

    public function generateOnTrainingComplete(PhaseApplication $application): ?string
    {
        if (!$application->is_trained) {
            return null;
        }

        return $this->ensureStored($application, enforceWaitPeriod: false);
    }

    public function isOfficerCertificateAvailable(PhaseApplication $application): bool
    {
        if ($application->status === 'disbursed') {
            return true;
        }

        if (!$application->is_trained) {
            return false;
        }

        $application->loadMissing('loanPhase');

        return $this->isCertificateDownloadAvailable($application->loanPhase);
    }

    protected function ensureStored(PhaseApplication $application, bool $enforceWaitPeriod): ?string
    {
        if (
            $application->training_certificate_path
            && Storage::disk('local')->exists($application->training_certificate_path)
        ) {
            return $application->training_certificate_path;
        }

        return $this->generateAndStore($application, $enforceWaitPeriod);
    }

    public function generateAndStore(PhaseApplication $application, bool $enforceWaitPeriod = true): string
    {
        $application->load([
            'tempApplication:id,application_id,group_name,constituency_code',
            'loanPhase:id,phase,training_start_date,training_end_date,training_venue,constituency_code',
        ]);

        if (
            $enforceWaitPeriod
            && !$this->isDisbursementCertificateAvailable($application)
            && !$this->isCertificateDownloadAvailable($application->loanPhase)
        ) {
            throw ValidationException::withMessages([
                'training' => $this->certificateAvailabilityMessage($application->loanPhase)
                    ?? 'Training certificate is not available yet.',
            ]);
        }

        $groupName = $application->tempApplication?->group_name ?? 'Group';
        $appId = $application->tempApplication?->application_id ?? 'N/A';
        $phase = $application->loanPhase;
        $venue = $phase?->training_venue ?? 'N/A';
        $start = $phase?->training_start_date?->format('d M Y') ?? 'N/A';
        $end = $phase?->training_end_date?->format('d M Y') ?? 'N/A';

        $html = view('pdf.training-certificate', compact('groupName', 'appId', 'venue', 'start', 'end'))->render();

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'landscape');
        $filename = 'training-cert-' . Str::slug($appId) . '-' . now()->format('YmdHis') . '.pdf';
        $path = "training-certificates/phase-{$application->loan_phase_id}/{$filename}";

        Storage::disk('local')->put($path, $pdf->output());

        $application->update(['training_certificate_path' => $path]);

        return $path;
    }
}
