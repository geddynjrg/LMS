<?php

namespace App\Services\Loan;

use App\Models\TempApplication;
use Illuminate\Support\Facades\Log;

class SystemVerificationService
{
    public function __construct(
        protected TempApplicationService $applicationService
    ) {}

    /**
     * Run auto verification for applications just assigned to a phase.
     *
     * @return array<int, string> temp_application_id => outcome
     */
    public function autoVerifyAssignedApplications(array $tempApplicationIds): array
    {
        if (! config('applications.auto_system_verification')) {
            return [];
        }

        $outcomes = [];

        TempApplication::query()
            ->whereIn('id', $tempApplicationIds)
            ->with(['phaseApplication', 'groupMembers'])
            ->each(function (TempApplication $application) use (&$outcomes) {
                $outcomes[$application->id] = $this->autoVerify($application);
            });

        return $outcomes;
    }

    public function autoVerify(TempApplication $application): string
    {
        if (! config('applications.auto_system_verification')) {
            return 'skipped_disabled';
        }

        if (! $application->phaseApplication) {
            return 'skipped_no_phase';
        }

        if (in_array($application->link_status, ['linked', 'attempted_no_match'], true)) {
            return 'skipped_already_verified';
        }

        if ($application->phaseApplication->group_id) {
            return 'skipped_already_linked';
        }

        try {
            $matches = $this->applicationService->findMatchingGroups($application);
            $strongMatches = $matches->filter(fn ($group) => $this->isStrongMatch($group))->values();

            if ($matches->isEmpty()) {
                $this->applicationService->markAsNoMatch($application);

                activity('system-verification')
                    ->performedOn($application)
                    ->withProperties(['outcome' => 'no_match', 'matches' => 0])
                    ->log('Auto system verification: no master group match — registered as new (Wezesha).');

                return 'no_match';
            }

            if ($strongMatches->count() === 1) {
                $group = $strongMatches->first();
                $this->applicationService->linkToMasterGroup($application, $group->id);

                activity('system-verification')
                    ->performedOn($application)
                    ->withProperties([
                        'outcome' => 'auto_linked',
                        'group_id' => $group->id,
                        'match_score' => $group->match_score,
                    ])
                    ->log("Auto system verification: linked to {$group->name} (Endeleza).");

                return 'auto_linked';
            }

            activity('system-verification')
                ->performedOn($application)
                ->withProperties([
                    'outcome' => 'manual_required',
                    'match_count' => $matches->count(),
                    'strong_match_count' => $strongMatches->count(),
                ])
                ->log('Auto system verification: ambiguous matches — manual review required.');

            return 'manual_required';
        } catch (\Throwable $e) {
            Log::warning('Auto system verification failed', [
                'application_id' => $application->application_id,
                'temp_application_id' => $application->id,
                'error' => $e->getMessage(),
            ]);

            return 'failed';
        }
    }

    protected function isStrongMatch(object $group): bool
    {
        return ($group->match_score ?? 0) >= 90
            || ($group->id_matches ?? 0) >= 4
            || ($group->reg_matched ?? false);
    }
}
