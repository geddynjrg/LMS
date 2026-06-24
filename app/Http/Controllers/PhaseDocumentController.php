<?php

namespace App\Http\Controllers;

use App\Models\PhaseApplicationDocument;
use App\Services\Loan\PhaseDocumentResolver;
use Illuminate\Support\Facades\Storage;

class PhaseDocumentController extends Controller
{
    public function serve(int $id, string $action, PhaseDocumentResolver $resolver)
    {
        $doc = PhaseApplicationDocument::with('loanPhase')->findOrFail($id);

        $fullPath = $resolver->resolveReadablePath($doc);

        if (!$fullPath || !is_readable($fullPath)) {
            abort(404, 'File not found: the document record exists but the file is missing from storage.');
        }

        $mimeType = $doc->mime_type
            ?: (Storage::disk('local')->exists($doc->path) ? Storage::disk('local')->mimeType($doc->path) : null)
            ?: 'application/octet-stream';

        if ($action === 'view') {
            return response()->file($fullPath, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . addslashes($doc->original_filename) . '"',
            ]);
        }

        return response()->download($fullPath, $doc->original_filename);
    }
}
