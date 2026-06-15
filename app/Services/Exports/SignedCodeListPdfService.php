<?php

namespace App\Services\Exports;

use App\Models\LoanPhase;
use App\Models\SecretariatApproval;
use App\Services\Loan\SecretariatApprovalService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SignedCodeListPdfService
{
    public function __construct(
        protected ExportService $exportService,
        protected SecretariatApprovalService $secretariatService,
    ) {
    }

    public function canDownload(LoanPhase $phase): bool
    {
        $approval = $this->getSigningApproval($phase);

        return $approval !== null
            && $approval->approved_at !== null
            && ! empty($approval->signature_path)
            && Storage::disk('local')->exists($approval->signature_path);
    }

    public function buildRows(LoanPhase $phase): array
    {
        $query = $this->exportService->exportCodeList($phase->id);
        $rows = [];
        $rowNumber = 0;
        $totalApplied = 0;
        $totalRecommended = 0;

        foreach ($query->get() as $app) {
            $rowNumber++;
            $businessType = $app->tempApplication->business_type ?? null;
            $currentActivity = is_array($businessType) ? ($businessType[0] ?? 'N/A') : ($businessType ?? 'N/A');

            $prevAmount = 0;
            $loanLevel = 'L1';

            if ($app->linkedGroup && $app->linkedGroup->currentLoan) {
                $prevAmount = (float) ($app->linkedGroup->currentLoan->amount_issued ?? 0);
                $loanLevel = 'L' . ($app->loan_level ?? 1);
            }

            $applied = (float) ($app->amount_applied ?? 0);
            $recommended = (float) ($app->amount_approved ?? 0);
            $totalApplied += $applied;
            $totalRecommended += $recommended;

            $rows[] = [
                'sno' => $rowNumber,
                'code' => $app->group_code,
                'group_name' => Str::upper($app->tempApplication->group_name ?? 'N/A'),
                'gfs_code' => $app->gfs_code ?? 'N/A',
                'applied' => $applied,
                'activity' => Str::upper($currentActivity),
                'previous_loan' => $prevAmount,
                'loan_level' => $loanLevel,
                'recommended' => $recommended,
                'status' => Str::upper($app->tempApplication->nature_of_business ?? 'N/A'),
            ];
        }

        return [
            'rows' => $rows,
            'totals' => [
                'applied' => $totalApplied,
                'recommended' => $totalRecommended,
            ],
        ];
    }

    public function downloadResponse(LoanPhase $phase)
    {
        if (! $this->canDownload($phase)) {
            throw ValidationException::withMessages([
                'download' => 'Signed approved code list is not available yet.',
            ]);
        }

        $phase->loadMissing([
            'constituency.county',
            'secretariatApprovals.approver:id,name',
        ]);

        $approval = $this->getSigningApproval($phase);
        $signaturePath = Storage::disk('local')->path($approval->signature_path);
        $signatureMime = Storage::disk('local')->mimeType($approval->signature_path) ?: 'image/png';
        $signatureData = base64_encode(file_get_contents($signaturePath));

        $table = $this->buildRows($phase);
        $constituencyName = $phase->constituency?->constituency_name ?? $phase->constituency_code;
        $countyName = $phase->constituency?->county?->county_name ?? 'N/A';

        $pdf = Pdf::loadView('pdf.signed-code-list', [
            'phase' => $phase,
            'rows' => $table['rows'],
            'totals' => $table['totals'],
            'constituencyName' => $constituencyName,
            'countyName' => $countyName,
            'constCode' => $phase->constituency_code,
            'signatureData' => $signatureData,
            'signatureMime' => $signatureMime,
            'signedAt' => $approval->approved_at,
            'signedBy' => $approval->approver?->name ?? 'Head of Secretariat',
        ])->setPaper('a4', 'landscape');

        $fileName = 'Signed_Code_List_' . str_replace(' ', '_', $constituencyName) . '_' . $phase->phase . '.pdf';

        return $pdf->download($fileName);
    }

    protected function getSigningApproval(LoanPhase $phase): ?SecretariatApproval
    {
        $phase->loadMissing('secretariatApprovals.approver:id,name');

        return $phase->secretariatApprovals
            ->firstWhere('approval_type', SecretariatApproval::TYPE_POST_HQ_REVIEW);
    }
}
