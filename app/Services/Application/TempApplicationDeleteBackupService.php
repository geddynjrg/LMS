<?php

namespace App\Services\Application;

use App\Models\TempApplication;
use App\Services\Backup\DeleteBackupDumper;
use Illuminate\Support\Facades\Schema;

class TempApplicationDeleteBackupService
{
    public function __construct(
        protected DeleteBackupDumper $dumper,
    ) {}

    /**
     * @return array{path: string, tables: list<string>}
     */
    public function backup(TempApplication $application): array
    {
        $applicationId = $application->id;
        $date = now()->format('Y-m-d');
        $directory = storage_path('app/backups/application-delete');
        $filename = "temp-application-delete-backup-{$applicationId}-{$date}.sql";

        $tables = [
            ['temp_applications', "id={$applicationId}"],
            ['temp_group_members', "temp_application_id={$applicationId}"],
        ];

        foreach ([
            'temp_application_approvals',
            'temp_expenses',
            'temp_application_files',
            'temp_files',
        ] as $table) {
            if (Schema::hasTable($table)) {
                $tables[] = [$table, "temp_application_id={$applicationId}"];
            }
        }

        $application->loadMissing(['phaseApplication.groupLoan']);

        $phaseApplication = $application->phaseApplication;
        if ($phaseApplication) {
            $phaseApplicationId = $phaseApplication->id;
            $tables[] = ['phase_applications', "id={$phaseApplicationId}"];

            if (Schema::hasTable('phase_comments')) {
                $tables[] = ['phase_comments', "phase_application_id={$phaseApplicationId}"];
            }

            if ($phaseApplication->group_loan_id) {
                $groupLoanId = $phaseApplication->group_loan_id;
                $tables[] = ['group_loans', "id={$groupLoanId}"];
                $tables[] = ['group_members', "group_loans_id={$groupLoanId}"];
                $tables[] = ['loan_receipts', "group_loans_id={$groupLoanId}"];
            }
        }

        $result = $this->dumper->dump(
            $directory,
            $filename,
            $tables,
            'application',
            'Could not create a backup before deleting this application. Deletion was cancelled.',
        );

        return [
            'path' => $result['path'],
            'tables' => $result['tables'],
        ];
    }
}
