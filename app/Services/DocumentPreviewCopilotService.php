<?php

namespace App\Services;

use App\Support\PhaseDocumentType;
use Illuminate\Support\Str;

class DocumentPreviewCopilotService
{
    /**
     * Rule-based assistive summary from OCR text + document type.
     *
     * @param  object{document_type?: string|null, title?: string|null}|array  $doc
     * @return array{
     *   assistive: true,
     *   detected_type_hint: string,
     *   id_matches: list<string>,
     *   date_matches: list<string>,
     *   suggested_actions: list<string>,
     *   notes: list<string>
     * }
     */
    public function summarize(object|array $doc, ?string $ocrText): array
    {
        $doc = is_array($doc) ? (object) $doc : $doc;
        $type = (string) ($doc->document_type ?? '');
        $label = PhaseDocumentType::label($type);
        $text = trim((string) ($ocrText ?? ''));

        $idMatches = [];
        if ($text !== '' && preg_match_all('/\b\d{7,8}\b/', $text, $m)) {
            $idMatches = array_values(array_unique(array_slice($m[0], 0, 5)));
        }

        $dateMatches = [];
        if ($text !== '' && preg_match_all('/\b(?:\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}|\d{4}[\/\-]\d{1,2}[\/\-]\d{1,2})\b/', $text, $dm)) {
            $dateMatches = array_values(array_unique(array_slice($dm[0], 0, 5)));
        }

        $actions = [
            'Verify the uploaded file matches the expected document type ('.$label.').',
            'Cross-check extracted IDs/dates against the phase application records.',
        ];

        if (in_array($type, PhaseDocumentType::bankReconciliationTypes(), true)) {
            $actions[] = 'Confirm statement period and account balances align with the transfer request.';
        }
        if (in_array($type, PhaseDocumentType::processedCodeListTypes(), true)
            || $type === PhaseDocumentType::SIGNED_APPROVED_CODE_LIST) {
            $actions[] = 'Confirm group codes and amounts match HQ-approved figures.';
        }
        if ($type === PhaseDocumentType::MINUTES) {
            $actions[] = 'Confirm committee attendance and resolution to submit to HQ.';
        }

        $notes = [];
        if ($text === '') {
            $notes[] = 'No OCR text available yet — open the file manually.';
        } else {
            $notes[] = 'OCR captured '.Str::length($text).' characters (assistive only, not an auto-decision).';
            if (stripos($text, str_replace('_', ' ', $type)) !== false
                || stripos($text, explode(' ', $label)[0] ?? '') !== false) {
                $notes[] = 'Text may reference the expected document category.';
            }
        }

        return [
            'assistive' => true,
            'detected_type_hint' => $label,
            'id_matches' => $idMatches,
            'date_matches' => $dateMatches,
            'suggested_actions' => $actions,
            'notes' => $notes,
        ];
    }
}
