<?php

namespace App\Http\Controllers;

use App\Models\LoanPhase;
use App\Services\ReviewChecklistDraftService;
use Illuminate\Http\Request;

class ReviewChecklistDraftController extends Controller
{
    public function __construct(private readonly ReviewChecklistDraftService $drafts)
    {
    }

    public function show(LoanPhase $phase)
    {
        $draft = $this->drafts->loadOrCreate($phase, auth()->id());
        $checklist = json_decode($draft->checklist_json ?? '[]', true) ?: [];
        $progress = json_decode($draft->progress_json ?? '{}', true) ?: [];

        return response()->json([
            'phase_id' => $phase->id,
            'phase_status' => $draft->phase_status,
            'checklist' => $checklist,
            'progress' => $progress,
        ]);
    }

    public function save(Request $request, LoanPhase $phase)
    {
        $validated = $request->validate([
            'progress' => ['required', 'array'],
        ]);

        $draft = $this->drafts->saveProgress(
            $phase,
            $validated['progress'],
            auth()->id()
        );

        return response()->json([
            'ok' => true,
            'progress' => json_decode($draft->progress_json ?? '{}', true) ?: [],
        ]);
    }
}
