<?php

namespace App\Services\Application;

use App\Models\GroupLoan;
use App\Services\Backup\DeleteBackupDumper;

class ApplicationDeleteBackupService
{
    public function __construct(
        protected DeleteBackupDumper $dumper,
    ) {}

    /**
     * @return array{path: string, tables: list<string>}
     */
    public function backup(GroupLoan $groupLoan): array
    {
        $groupLoan->loadMissing('group');

        $groupLoanId = $groupLoan->id;
        $date = now()->format('Y-m-d');
        $directory = storage_path('app/backups/application-delete');
        $filename = "application-delete-backup-{$groupLoanId}-{$date}.sql";

        $tables = [
            ['group_loans', "id={$groupLoanId}"],
            ['group_members', "group_loans_id={$groupLoanId}"],
            ['loan_receipts', "group_loans_id={$groupLoanId}"],
        ];

        if ($groupLoan->group_id) {
            $tables[] = ['groups', "id={$groupLoan->group_id}"];
        }

        $result = $this->dumper->dump(
            $directory,
            $filename,
            $tables,
            'application',
            'Could not create a backup before deleting this application. Deletion was cancelled.',
            120,
        );

        return [
            'path' => $result['path'],
            'tables' => $result['tables'],
        ];
    }
}
