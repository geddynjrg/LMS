<?php

namespace App\Services\Loan;

use App\Models\PhaseApplicationDocument;
use App\Services\Exports\ProcessedCodeListDocumentService;
use App\Support\PhaseDocumentType;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PhaseDocumentResolver
{
    public function __construct(
        protected ProcessedCodeListDocumentService $processedCodeListService,
    ) {
    }

    public function resolveReadablePath(PhaseApplicationDocument $document): ?string
    {
        if ($document->path && Storage::disk('local')->exists($document->path)) {
            return Storage::disk('local')->path($document->path);
        }

        if (!in_array($document->document_type, PhaseDocumentType::processedCodeListTypes(), true)) {
            return null;
        }

        $phase = $document->loanPhase ?? $document->loanPhase()->first();
        if (!$phase) {
            return null;
        }

        try {
            $this->processedCodeListService->generateAndAttach($phase);
            $document->refresh();

            if ($document->path && Storage::disk('local')->exists($document->path)) {
                return Storage::disk('local')->path($document->path);
            }
        } catch (\Throwable $e) {
            Log::error('Failed to regenerate missing processed code list', [
                'document_id' => $document->id,
                'phase_id' => $phase->id,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }
}
