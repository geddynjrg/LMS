<?php

namespace App\Services\Application;

use App\Models\TempApplication;
use App\Services\Backup\DeleteBackupDumper;

class TempApplicationEditBackupService
{
    public function __construct(
        protected DeleteBackupDumper $dumper,
    ) {}

    /**
     * @return array{path: string, tables: list<string>}
     */
    public function backup(TempApplication $application): array
    {
        $application->loadMissing(['phaseApplication.groupLoan']);

        $applicationId = $application->id;
        $stamp = now()->format('Ymd-His');
        $directory = storage_path('app/backups/application-edit');
        $filename = "temp-application-edit-backup-{$applicationId}-{$stamp}.sql";

        $tables = [
            ['temp_applications', "id={$applicationId}"],
        ];

        $groupIds = array_values(array_unique(array_filter([
            $application->group_id,
            $application->phaseApplication?->group_id,
            $application->phaseApplication?->groupLoan?->group_id,
        ])));

        $phaseApplication = $application->phaseApplication;
        if ($phaseApplication) {
            $tables[] = ['phase_applications', "id={$phaseApplication->id}"];

            if ($phaseApplication->group_loan_id) {
                $groupLoanId = $phaseApplication->group_loan_id;
                $tables[] = ['group_loans', "id={$groupLoanId}"];
            }
        }

        foreach ($groupIds as $groupId) {
            $tables[] = ['groups', "id={$groupId}"];
        }

        $result = $this->dumper->dump(
            $directory,
            $filename,
            $tables,
            'application',
            'Could not create a backup before saving. Changes were cancelled.',
        );

        return [
            'path' => $result['path'],
            'tables' => $result['tables'],
        ];
    }
}
