<?php

namespace App\Http\Controllers;

use App\Models\PhaseApplicationDocument;
use App\Services\DocumentPreviewCopilotService;
use App\Services\DocumentTextExtractionService;
use App\Services\Loan\PhaseDocumentResolver;
use App\Support\HqAccess;

class PhaseDocumentController extends Controller
{
    protected function authorizePhaseDocumentAccess(PhaseApplicationDocument $doc): void
    {
        $user = auth()->user();
        $phase = $doc->loanPhase;

        if ($phase && HqAccess::phaseCanBeViewedBy($user, $phase)) {
            return;
        }

        if ($user?->hasRole('constituency_officer')
            && $phase
            && $user->constituencies()->where('constituency_code', $phase->constituency_code)->exists()) {
            return;
        }

        if ($user?->hasAnyRole(['super-admin', 'head_of_secretariat', 'accountant'])) {
            return;
        }

        abort(403, 'You do not have permission to access this document.');
    }

    public function serve(int $id, string $action, PhaseDocumentResolver $resolver)
    {
        $doc = PhaseApplicationDocument::with('loanPhase')->findOrFail($id);

        $this->authorizePhaseDocumentAccess($doc);

        $fullPath = $resolver->resolveReadablePath($doc);

        if (!$fullPath || !is_readable($fullPath)) {
            abort(404, 'File not found: the document record exists but the file is missing from storage.');
        }

        $mimeType = $doc->mime_type
            ?: mime_content_type($fullPath)
            ?: 'application/octet-stream';

        if ($action === 'view') {
            return response()->file($fullPath, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . addslashes($doc->original_filename) . '"',
                'X-Frame-Options' => 'SAMEORIGIN',
            ]);
        }

        return response()->download($fullPath, $doc->original_filename);
    }

    public function preview(
        int $id,
        PhaseDocumentResolver $resolver,
        DocumentTextExtractionService $extractor,
        DocumentPreviewCopilotService $copilot,
    ) {
        $doc = PhaseApplicationDocument::with(['loanPhase.constituency'])->findOrFail($id);

        $this->authorizePhaseDocumentAccess($doc);

        $fullPath = $resolver->resolveReadablePath($doc);
        $ocr = null;
        if ($fullPath) {
            $key = 'phase-doc-'.$doc->id;
            $ocr = $extractor->find($key);
            if (! $ocr || $ocr->status === 'pending') {
                $result = $extractor->extractAndStore($key, $fullPath, $doc->mime_type);
                $ocr = (object) [
                    'status' => $result['status'],
                    'extraction_method' => $result['method'],
                    'extracted_text' => $result['text'] ?? null,
                    'error_message' => $result['error'] ?? null,
                ];
            }
        }

        $copilotSummary = $copilot->summarize(
            $doc,
            is_object($ocr) ? ($ocr->extracted_text ?? null) : null
        );

        return view('documents.phase-document-preview', [
            'doc' => $doc,
            'phase' => $doc->loanPhase,
            'canEmbed' => $doc->can_view_inline && $fullPath !== null,
            'fileMissing' => $fullPath === null,
            'embedUrl' => route('phase.doc.serve', ['id' => $doc->id, 'action' => 'view']),
            'downloadUrl' => route('phase.doc.serve', ['id' => $doc->id, 'action' => 'download']),
            'ocr' => $ocr,
            'copilot' => $copilotSummary,
        ]);
    }
}
