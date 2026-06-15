<?php

namespace App\Services\Exports;

use App\Models\LoanPhase;
use App\Models\SecretariatApproval;
use App\Services\Loan\SecretariatApprovalService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class LetterOfAuthorityPdfService
{
    public function __construct(
        protected SecretariatApprovalService $secretariatService,
    ) {
    }

    public function canDownload(LoanPhase $phase): bool
    {
        if (! $this->secretariatService->requiresFundTransferAuthorization($phase)) {
            return false;
        }

        $approval = $this->getFundTransferApproval($phase);

        return $approval !== null
            && $approval->approved_at !== null
            && ! empty($approval->signature_path)
            && Storage::disk('local')->exists($approval->signature_path);
    }

    public function downloadResponse(LoanPhase $phase)
    {
        if (! $this->canDownload($phase)) {
            throw ValidationException::withMessages([
                'download' => 'Letter of authority is not available for this phase.',
            ]);
        }

        $phase->loadMissing(['constituency.county', 'secretariatApprovals.approver:id,name']);
        $approval = $this->getFundTransferApproval($phase);
        $signaturePath = Storage::disk('local')->path($approval->signature_path);
        $signatureMime = Storage::disk('local')->mimeType($approval->signature_path) ?: 'image/png';

        $pdf = Pdf::loadView('pdf.letter-of-authority-disbursement', [
            'phase' => $phase,
            'constituencyName' => $phase->constituency?->constituency_name ?? $phase->constituency_code,
            'countyName' => $phase->constituency?->county?->county_name ?? 'N/A',
            'constCode' => $phase->constituency_code,
            'requestedAmount' => (float) ($phase->total_amount_approved ?? 0),
            'availableBalance' => (float) ($phase->constituency?->constituency_bank_balance ?? 0),
            'signatureData' => base64_encode(file_get_contents($signaturePath)),
            'signatureMime' => $signatureMime,
            'signedAt' => $approval->approved_at,
            'signedBy' => $approval->approver?->name ?? 'Head of Secretariat',
        ])->setPaper('a4');

        $fileName = 'Letter_of_Authority_' . str_replace(' ', '_', $phase->phase_ref ?? $phase->phase) . '.pdf';

        return $pdf->download($fileName);
    }

    protected function getFundTransferApproval(LoanPhase $phase): ?SecretariatApproval
    {
        $phase->loadMissing('secretariatApprovals.approver:id,name');

        return $phase->secretariatApprovals
            ->firstWhere('approval_type', SecretariatApproval::TYPE_FUND_TRANSFER);
    }
}
