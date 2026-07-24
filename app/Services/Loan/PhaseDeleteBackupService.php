<?php

namespace App\Services\Loan;

use App\Models\GroupLoan;
use App\Models\LoanPhase;
use App\Services\Backup\DeleteBackupDumper;
use Illuminate\Support\Facades\Schema;

class PhaseDeleteBackupService
{
    public function __construct(
        protected DeleteBackupDumper $dumper,
    ) {}

    /**
     * @return array{path: string, tables: list<string>}
     */
    public function backup(LoanPhase $phase): array
    {
        $phaseId = $phase->id;
        $date = now()->format('Y-m-d');
        $directory = storage_path('app/backups/phase-delete');
        $filename = "phase-delete-backup-{$phaseId}-{$date}.sql";

        $groupLoanIds = GroupLoan::query()
            ->where('loan_phase_id', $phaseId)
            ->pluck('id')
            ->all();

        $groupIds = GroupLoan::query()
            ->where('loan_phase_id', $phaseId)
            ->whereNotNull('group_id')
            ->pluck('group_id')
            ->unique()
            ->values()
            ->all();

        $tables = [
            ['loan_phases', "id={$phaseId}"],
            ['group_loans', "loan_phase_id={$phaseId}"],
        ];

        if ($groupLoanIds !== []) {
            $idList = implode(',', $groupLoanIds);
            $tables[] = ['group_members', "group_loans_id IN ({$idList})"];
            $tables[] = ['loan_receipts', "group_loans_id IN ({$idList})"];
        }

        if ($groupIds !== []) {
            $tables[] = ['groups', 'id IN (' . implode(',', $groupIds) . ')'];
        }

        foreach ([
            'phase_applications',
            'phase_comments',
            'phase_documents',
            'phase_application_documents',
            'committee_members',
            'secretariat_approvals',
            'hq_reviews',
        ] as $table) {
            if (Schema::hasTable($table)) {
                $tables[] = [$table, "loan_phase_id={$phaseId}"];
            }
        }

        $result = $this->dumper->dump(
            $directory,
            $filename,
            $tables,
            'phase',
            'Could not create a backup before deleting this phase. Deletion was cancelled.',
        );

        return [
            'path' => $result['path'],
            'tables' => $result['tables'],
        ];
    }
}
