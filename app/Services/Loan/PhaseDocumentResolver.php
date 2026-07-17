<?php

namespace App\Services\Loan;

use App\Models\LoanPhase;
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
        $resolvedPath = $this->resolveStoredPath($document);
        if ($resolvedPath !== null) {
            return $resolvedPath;
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

            return $this->resolveStoredPath($document, persistMatch: true);
        } catch (\Throwable $e) {
            Log::error('Failed to regenerate missing processed code list', [
                'document_id' => $document->id,
                'phase_id' => $phase->id,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    protected function resolveStoredPath(
        PhaseApplicationDocument $document,
        bool $persistMatch = false,
    ): ?string {
        foreach ($this->candidateRelativePaths($document) as $relativePath) {
            foreach (['local', 'public'] as $disk) {
                if (!Storage::disk($disk)->exists($relativePath)) {
                    continue;
                }

                if ($persistMatch && $relativePath !== $document->path) {
                    $document->forceFill(['path' => $relativePath])->saveQuietly();
                }

                return Storage::disk($disk)->path($relativePath);
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    protected function candidateRelativePaths(PhaseApplicationDocument $document): array
    {
        $candidates = [];

        if ($document->path) {
            $candidates[] = $document->path;
        }

        $phase = $document->loanPhase ?? $document->loanPhase()->first();
        if (!$phase) {
            return array_values(array_unique(array_filter($candidates)));
        }

        $directory = "phases/{$phase->slug}";
        $basename = basename((string) $document->path);
        if ($basename !== '' && $basename !== '.') {
            $candidates[] = "{$directory}/{$basename}";
        }

        foreach ([$document->filename, $document->original_filename] as $name) {
            if (!$name) {
                continue;
            }

            $candidates[] = "{$directory}/{$name}";

            $extension = pathinfo((string) $name, PATHINFO_EXTENSION);
            if ($extension === '') {
                $resolved = $document->resolvedExtension();
                if ($resolved !== '') {
                    $candidates[] = "{$directory}/{$name}.{$resolved}";
                    $candidates[] = "{$directory}/" . rtrim($name, '-_') . ".{$resolved}";
                }
            }
        }

        $prefix = $this->filenamePrefix($document->filename ?: $document->original_filename);
        if ($prefix !== '' && Storage::disk('local')->exists($directory)) {
            foreach (Storage::disk('local')->files($directory) as $file) {
                if (str_starts_with(basename($file), $prefix)) {
                    $candidates[] = $file;
                }
            }
        }

        return array_values(array_unique(array_filter($candidates)));
    }

    protected function filenamePrefix(?string $name): string
    {
        if (!$name) {
            return '';
        }

        $stem = pathinfo($name, PATHINFO_FILENAME);

        return rtrim((string) $stem, '-_');
    }
}
