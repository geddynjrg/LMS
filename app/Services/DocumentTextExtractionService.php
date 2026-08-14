<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class DocumentTextExtractionService
{
    public const SOURCE = 'core_lms';

    /**
     * Extract text from a local file path and upsert into document_extracted_texts.
     *
     * @param  array<string, mixed>|null  $completeness
     * @return array{status:string,method:string,text:?string}
     */
    public function extractAndStore(string $documentKey, string $absolutePath, ?string $mimeType = null, ?array $completeness = null): array
    {
        $result = $this->extract($absolutePath, $mimeType);

        DB::table('document_extracted_texts')->upsert([
            [
                'source_system' => self::SOURCE,
                'document_key' => $documentKey,
                'extraction_method' => $result['method'],
                'status' => $result['status'],
                'extracted_text' => $result['text'],
                'completeness_json' => $completeness ? json_encode($completeness) : null,
                'error_message' => $result['error'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ], ['source_system', 'document_key'], [
            'extraction_method',
            'status',
            'extracted_text',
            'completeness_json',
            'error_message',
            'updated_at',
        ]);

        return $result;
    }

    public function find(string $documentKey): ?object
    {
        return DB::table('document_extracted_texts')
            ->where('source_system', self::SOURCE)
            ->where('document_key', $documentKey)
            ->first();
    }

    /**
     * @return array{status:string,method:string,text:?string,error?:string}
     */
    public function extract(string $absolutePath, ?string $mimeType = null): array
    {
        if (! is_readable($absolutePath)) {
            return ['status' => 'failed', 'method' => 'none', 'text' => null, 'error' => 'unreadable'];
        }

        $mime = strtolower((string) ($mimeType ?: mime_content_type($absolutePath) ?: ''));
        $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

        if ($mime === 'application/pdf' || $ext === 'pdf') {
            $pdfText = $this->extractPdfText($absolutePath);
            if ($pdfText !== null && trim($pdfText) !== '') {
                return ['status' => 'extracted', 'method' => 'pdf_text', 'text' => $this->truncate($pdfText)];
            }
            $ocr = $this->ocrWithTesseract($absolutePath);
            if ($ocr !== null) {
                return ['status' => 'extracted', 'method' => 'tesseract_pdf', 'text' => $this->truncate($ocr)];
            }

            return [
                'status' => 'unavailable',
                'method' => 'pdf_pending_ocr',
                'text' => null,
                'error' => 'PDF has no extractable text; install tesseract/pdftotext for OCR',
            ];
        }

        if (str_starts_with($mime, 'image/') || in_array($ext, ['jpg', 'jpeg', 'png', 'tif', 'tiff', 'webp'], true)) {
            $ocr = $this->ocrWithTesseract($absolutePath);
            if ($ocr !== null) {
                return ['status' => 'extracted', 'method' => 'tesseract', 'text' => $this->truncate($ocr)];
            }

            return [
                'status' => 'unavailable',
                'method' => 'image_pending_ocr',
                'text' => null,
                'error' => 'tesseract not available in container',
            ];
        }

        return ['status' => 'skipped', 'method' => 'unsupported', 'text' => null, 'error' => 'unsupported mime'];
    }

    protected function extractPdfText(string $path): ?string
    {
        if ($this->binaryExists('pdftotext')) {
            try {
                $result = Process::timeout(30)->run(['pdftotext', '-q', '-layout', $path, '-']);
                if ($result->successful()) {
                    return $result->output();
                }
            } catch (\Throwable $e) {
                Log::debug('[OCR] pdftotext failed', ['error' => $e->getMessage()]);
            }
        }

        // Lightweight embedded-string scrape for text-based PDFs
        $raw = @file_get_contents($path, false, null, 0, 2_000_000);
        if ($raw === false) {
            return null;
        }
        $chunks = [];
        if (preg_match_all('/\((\\\\.|[^\\\\)]){3,}\)/s', $raw, $matches)) {
            foreach ($matches[0] as $match) {
                $text = substr($match, 1, -1);
                $text = str_replace(['\\n', '\\r', '\\t', '\\(', '\\)'], ["\n", '', "\t", '(', ')'], $text);
                if (preg_match('/[A-Za-z0-9]{3,}/', $text)) {
                    $chunks[] = $text;
                }
            }
        }

        return $chunks ? implode("\n", $chunks) : null;
    }

    protected function ocrWithTesseract(string $path): ?string
    {
        if (! $this->binaryExists('tesseract')) {
            return null;
        }

        try {
            $result = Process::timeout(60)->run(['tesseract', $path, 'stdout', '-l', 'eng']);
            if ($result->successful()) {
                return $result->output();
            }
        } catch (\Throwable $e) {
            Log::debug('[OCR] tesseract failed', ['error' => $e->getMessage()]);
        }

        return null;
    }

    protected function binaryExists(string $binary): bool
    {
        try {
            $result = Process::run(['which', $binary]);

            return $result->successful() && trim($result->output()) !== '';
        } catch (\Throwable) {
            return false;
        }
    }

    protected function truncate(string $text): string
    {
        $text = trim($text);
        if (strlen($text) > 100_000) {
            return substr($text, 0, 100_000)."\n…[truncated]";
        }

        return $text;
    }
}
