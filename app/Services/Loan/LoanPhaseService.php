<?php

namespace App\Services\Loan;

use App\Models\LoanPhase;
use App\Models\PhaseApplication;
use App\Models\TempApplication;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class LoanPhaseService
{
    /**
     * Create a new loan review phase
     *
     * @param array $data Validated input data
     * @param string $constituencyCode
     * @return LoanPhase
     */
    public function createPhase(array $data, string $constituencyCode): LoanPhase
    {
        // Validate phase_name format
        $phaseName = trim($data['phaseName'] ?? '');

        if (!preg_match('/^phase\s+\d+$/i', $phaseName)) {
            throw ValidationException::withMessages([
                'phaseName' => 'Phase name must be in the format "Phase [positive number]", e.g. "Phase 3" or "phase 12".',
            ]);
        }

        // Extract the number part (for phase_number)
        preg_match('/\d+/', $phaseName, $matches);
        $phaseNumberFromName = (int) $matches[0];

        if ($phaseNumberFromName <= 0) {
            throw ValidationException::withMessages([
                'phaseName' => 'The phase number must be a positive integer.',
            ]);
        }

        // Check uniqueness (per constituency)
        if (LoanPhase::where('constituency_code', $constituencyCode)
            ->whereRaw('LOWER(phase) = ?', [strtolower($phaseName)])
            ->exists()) {
            throw ValidationException::withMessages([
                'phaseName' => 'This phase name already exists for this constituency.',
            ]);
        }

        $phaseCount = LoanPhase::where('constituency_code', $constituencyCode)->count();
        return DB::transaction(function () use ($phaseName, $data, $constituencyCode, $phaseCount) {
            $result = LoanPhase::create([
                'constituency_code'       => $constituencyCode,
                'phase'                  => $phaseName,
                'phase_name'             => $phaseName,
                'phase_number'           => 'Phase ' . ($phaseCount + 1) . ' - ' . now()->year,
                'review_date'            => $data['reviewDate'],
                'review_venue'           => $data['reviewVenue'] ?? null,
                'constituency_officer_id' => Auth::id() ?? null,
                'status'                 => 'draft',
            ]);

            Log::info('create results: ' . $result->id);

            return $result;
        });
    }

    /**
     * Assign one or multiple applications to a phase
     *
     * @param LoanPhase $phase
     * @param array|int $applicationIds Single ID or array of IDs
     * @throws ValidationException
     */
    public function assignApplications(LoanPhase $phase, $applicationIds): void
    {
        if (!is_array($applicationIds)) {
            $applicationIds = [$applicationIds];
        }

        if (!$phase->canAddApplications()) {
            throw ValidationException::withMessages([
                'phase' => "This phase is not accepting new applications (current status: {$phase->status}).",
            ]);
        }

        DB::transaction(function () use ($phase, $applicationIds) {
            foreach ($applicationIds as $id) {
                $app = TempApplication::findOrFail($id);

                if (!$app) {
                    continue; // or throw if you prefer strict mode
                }

                // Prevent duplicates
                PhaseApplication::firstOrCreate(
                    [
                        'loan_phase_id'        => $phase->id,
                        'temp_application_id'  => $id,
                    ],
                    [
                        'amount_applied'      => $app->loan_amount,
                        'committee_decision'  => 'pending',
                    ]
                );

                $this->invalidateApplicationCache($app);
            }

            $phase->calculateStats();
        });
    }

    /**
     * Remove an application from a phase
     *
     * @param LoanPhase $phase
     * @param int $applicationId
     * @throws ValidationException
     */
    public function removeApplication(LoanPhase $phase, int $applicationId): void
    {
        if ($phase->status !== 'draft') {
            throw ValidationException::withMessages([
                'phase' => 'Applications can only be removed from phases in "draft" status.',
            ]);
        }

        $phaseApplication = PhaseApplication::where('loan_phase_id', $phase->id)
            ->where('temp_application_id', $applicationId)
            ->first();

        if (!$phaseApplication) {
            return; // already not assigned
        }

        DB::transaction(function () use ($phaseApplication, $phase) {
            $phaseApplication->delete();
            $phase->calculateStats();
        });

        $app = TempApplication::findOrFail($phaseApplication->temp_application_id);

        if ($app) {
            $this->invalidateApplicationCache($app);
        }
    }

    /**
     * Bulk assign multiple applications (convenience wrapper)
     */
    public function bulkAssignApplications(LoanPhase $phase, array $applicationIds): void
    {
        $this->assignApplications($phase, $applicationIds);
    }

    /**
     * Mark phase as in_review (after finalizing draft)
     */
    public function startReview(LoanPhase $phase): void
    {
        if ($phase->status !== 'draft') {
            throw ValidationException::withMessages([
                'status' => 'Only draft phases can be moved to in_review.',
            ]);
        }

        if ($phase->phaseApplications()->count() === 0) {
            throw ValidationException::withMessages([
                'applications' => 'Cannot start review without any applications assigned.',
            ]);
        }

        $phase->update(['status' => 'in_review']);
    }

    /**
     * Get phases with useful eager-loaded relations
     */
    public function getActivePhasesForConstituency(string $constituencyCode)
    {
        return LoanPhase::where('constituency_code', $constituencyCode)
            ->whereIn('status', ['draft', 'in_review'])
            ->withCount('phaseApplications')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getAllPhasesForConstituency(string $constituencyCode)
    {
        return LoanPhase::where('constituency_code', $constituencyCode)
            ->withCount('phaseApplications')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    // get phase by id
    public function getPhase($id) : LoanPhase
    {
        return LoanPhase::findOrFail($id);
    }

    /**
     * Invalidate cache for an application after phase change
    */
    private function invalidateApplicationCache(TempApplication $application): void
    {
        $applicationId = $application->application_id;
        $constituencyCode = $application->constituency_code;

        Cache::forget("temp_app_{$applicationId}_{$constituencyCode}");
        Cache::forget("temp_app_detail_{$applicationId}_{$constituencyCode}");

        // Add other cache keys if needed (e.g. stats)
    }
}
