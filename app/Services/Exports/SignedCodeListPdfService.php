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
        $pdf = $this->buildPdf($phase);

        if ($pdf === null) {
            throw ValidationException::withMessages([
                'download' => 'Signed approved code list is not available yet.',
            ]);
        }

        $constituencyName = $phase->constituency?->constituency_name ?? $phase->constituency_code;
        $fileName = 'Signed_Code_List_' . str_replace(' ', '_', $constituencyName) . '_' . $phase->phase . '.pdf';

        return $pdf->download($fileName);
    }

    /**
     * Generate the signed PDF and store it as the phase approved_code_list document.
     * Replaces any HQ-uploaded Excel/Word version once Head of Secretariat has signed.
     */
    public function storeAsApprovedCodeListDocument(LoanPhase $phase, ?int $uploadedBy = null): bool
    {
        $pdf = $this->buildPdf($phase);

        if ($pdf === null) {
            return false;
        }

        if (! $phase->relationLoaded('phaseApplicationDocuments')) {
            $phase->load('phaseApplicationDocuments');
        }

        $directory = "phases/{$phase->slug}";
        $filename = 'approved-code-list-signed-' . now()->format('Ymd_His') . '.pdf';
        $path = "{$directory}/{$filename}";
        $pdfBytes = $pdf->output();

        Storage::disk('local')->put($path, $pdfBytes);

        $existing = $phase->phaseApplicationDocuments
            ->firstWhere('document_type', 'approved_code_list');

        if ($existing) {
            if ($existing->path && Storage::disk('local')->exists($existing->path)) {
                Storage::disk('local')->delete($existing->path);
            }
            $existing->delete();
        }

        $phase->phaseApplicationDocuments()->create([
            'loan_phase_id' => $phase->id,
            'title' => 'Approved Code List (Signed)',
            'document_type' => 'approved_code_list',
            'original_filename' => $filename,
            'filename' => $filename,
            'path' => $path,
            'mime_type' => 'application/pdf',
            'size' => strlen($pdfBytes),
            'uploaded_by' => $uploadedBy,
        ]);

        return true;
    }

    protected function buildPdf(LoanPhase $phase): ?\Barryvdh\DomPDF\PDF
    {
        if (! $this->canDownload($phase)) {
            return null;
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

        return Pdf::loadView('pdf.signed-code-list', [
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
    }

    protected function getSigningApproval(LoanPhase $phase): ?SecretariatApproval
    {
        $phase->loadMissing('secretariatApprovals.approver:id,name');

        return $phase->secretariatApprovals
            ->firstWhere('approval_type', SecretariatApproval::TYPE_POST_HQ_REVIEW);
    }
}
