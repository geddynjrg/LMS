<?php

namespace App\Services;

use App\Models\LoanPhase;
use App\Support\PhaseDocumentType;
use Illuminate\Support\Facades\DB;

class ReviewChecklistDraftService
{
    public const SOURCE = 'core_lms';

    /**
     * Build rule-based checklist items for a phase review.
     *
     * @return list<array{id: string, label: string, required: bool, category: string}>
     */
    public function generateItemsForPhase(LoanPhase $phase): array
    {
        $items = [];

        foreach (PhaseDocumentType::requiredForConstituencyHqSubmission() as $type) {
            $items[] = [
                'id' => 'doc_'.$type,
                'label' => 'Confirm uploaded: '.PhaseDocumentType::label($type),
                'required' => true,
                'category' => 'documents',
            ];
        }

        $items[] = [
            'id' => 'doc_'.PhaseDocumentType::SIGNED_APPROVED_CODE_LIST,
            'label' => 'Confirm uploaded: '.PhaseDocumentType::label(PhaseDocumentType::SIGNED_APPROVED_CODE_LIST),
            'required' => false,
            'category' => 'documents',
        ];

        $items[] = [
            'id' => 'check_amounts_consistent',
            'label' => 'Verify approved amounts are consistent with the code list',
            'required' => true,
            'category' => 'review',
        ];

        $items[] = [
            'id' => 'check_gfs_codes',
            'label' => 'Verify GFS codes are present for approved applications',
            'required' => true,
            'category' => 'review',
        ];

        $items[] = [
            'id' => 'check_phase_status_'.$phase->status,
            'label' => 'Confirm phase status ('.$phase->status.') matches review action',
            'required' => true,
            'category' => 'workflow',
        ];

        return $items;
    }

    /**
     * @param  array<string, bool>  $progress
     * @return object{id:int, checklist_json:mixed, progress_json:mixed, phase_status:string}|null
     */
    public function loadOrCreate(LoanPhase $phase, ?int $userId = null): object
    {
        $applicationId = (string) $phase->id;
        $phaseStatus = (string) ($phase->status ?? '');
        $existing = DB::table('review_checklist_drafts')
            ->where('source_system', self::SOURCE)
            ->where('application_id', $applicationId)
            ->where('phase_status', $phaseStatus)
            ->first();

        if ($existing) {
            return $existing;
        }

        $checklist = $this->generateItemsForPhase($phase);
        $id = DB::table('review_checklist_drafts')->insertGetId([
            'source_system' => self::SOURCE,
            'application_id' => $applicationId,
            'phase_id' => $phase->id,
            'phase_status' => $phaseStatus,
            'checklist_json' => json_encode($checklist),
            'progress_json' => json_encode(new \stdClass()),
            'created_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('review_checklist_drafts')->where('id', $id)->first();
    }

    /**
     * @param  array<string, bool>  $progress
     */
    public function saveProgress(LoanPhase $phase, array $progress, ?int $userId = null): object
    {
        $draft = $this->loadOrCreate($phase, $userId);
        DB::table('review_checklist_drafts')
            ->where('id', $draft->id)
            ->update([
                'progress_json' => json_encode($progress),
                'updated_at' => now(),
            ]);

        return DB::table('review_checklist_drafts')->where('id', $draft->id)->first();
    }
}
