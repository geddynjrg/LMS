<?php

namespace App\Services;

use App\Support\PhaseDocumentType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MissingGfsArtefactAlertService
{
    /**
     * Find approved / pending-disbursement phase applications missing GFS or required artefacts.
     *
     * @return Collection<int, object>
     */
    public function alerts(int $limit = 25): Collection
    {
        $statuses = ['approved', 'pending_disbursement', 'in_training'];
        $apps = DB::table('phase_applications as pa')
            ->leftJoin('loan_phases as lp', 'lp.id', '=', 'pa.loan_phase_id')
            ->leftJoin('constituencies as c', 'c.constituency_code', '=', 'lp.constituency_code')
            ->select([
                'pa.id',
                'pa.group_code',
                'pa.gfs_code',
                'pa.status as app_status',
                'pa.loan_phase_id',
                'lp.phase',
                'lp.status as phase_status',
                'lp.constituency_code',
                'c.constituency_name',
            ])
            ->whereIn('pa.status', $statuses)
            ->orderByDesc('pa.updated_at')
            ->limit(max(50, $limit * 3))
            ->get();

        $phaseIds = $apps->pluck('loan_phase_id')->filter()->unique()->values()->all();
        $docsByPhase = collect();
        if ($phaseIds !== []) {
            $docsByPhase = DB::table('phase_application_documents')
                ->whereIn('loan_phase_id', $phaseIds)
                ->get(['loan_phase_id', 'document_type'])
                ->groupBy('loan_phase_id');
        }

        $required = PhaseDocumentType::requiredForConstituencyHqSubmission();
        $alerts = collect();

        foreach ($apps as $app) {
            $issues = [];
            if ($app->gfs_code === null || trim((string) $app->gfs_code) === '') {
                $issues[] = 'Missing GFS code';
            }

            $types = ($docsByPhase->get($app->loan_phase_id) ?? collect())
                ->pluck('document_type')
                ->map(fn ($t) => (string) $t)
                ->all();

            foreach ($required as $type) {
                if (! in_array($type, $types, true)
                    && ! ($type === PhaseDocumentType::PROCESSED_CODE_LIST
                        && in_array(PhaseDocumentType::LEGACY_APPROVED_CODE_LIST, $types, true))) {
                    // HQ-submission docs are phase-level; flag once per app under that phase.
                    $issues[] = 'Missing artefact: '.PhaseDocumentType::label($type);
                }
            }

            if (! in_array(PhaseDocumentType::SIGNED_APPROVED_CODE_LIST, $types, true)
                && in_array($app->app_status, ['pending_disbursement', 'in_training'], true)) {
                $issues[] = 'Missing artefact: '.PhaseDocumentType::label(PhaseDocumentType::SIGNED_APPROVED_CODE_LIST);
            }

            $issues = array_values(array_unique($issues));
            if ($issues === []) {
                continue;
            }

            $alerts->push((object) [
                'phase_application_id' => $app->id,
                'group_code' => $app->group_code,
                'loan_phase_id' => $app->loan_phase_id,
                'phase' => $app->phase,
                'constituency_name' => $app->constituency_name,
                'constituency_code' => $app->constituency_code,
                'app_status' => $app->app_status,
                'issues' => $issues,
            ]);

            if ($alerts->count() >= $limit) {
                break;
            }
        }

        return $alerts->values();
    }
}
