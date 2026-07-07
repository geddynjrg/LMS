<?php

namespace App\Services\Loan;

use App\Models\GroupLoan;
use App\Models\LoanPhase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class PhaseDeleteBackupService
{
    /**
     * @return array{path: string, tables: list<string>}
     */
    public function backup(LoanPhase $phase): array
    {
        $phaseId = $phase->id;
        $date = now()->format('Y-m-d');
        $directory = storage_path('app/backups/phase-delete');
        $path = "{$directory}/phase-delete-backup-{$phaseId}-{$date}.sql";

        File::ensureDirectoryExists($directory);

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

        $config = config('database.connections.' . config('database.default'));
        $database = $config['database'];
        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? '3306';
        $username = $config['username'];
        $password = $config['password'] ?? '';

        $dumpBinary = rtrim($config['dump']['dump_binary_path'] ?? '/usr/bin', '/') . '/mysqldump';
        $contents = '';

        foreach ($tables as [$table, $where]) {
            $command = [
                $dumpBinary,
                '-h', (string) $host,
                '-P', (string) $port,
                '-u', (string) $username,
                '--no-create-info',
                '--skip-triggers',
                '--compact',
                '--single-transaction',
                $database,
                $table,
                '--where=' . $where,
            ];

            $result = Process::env(['MYSQL_PWD' => (string) $password])
                ->timeout(300)
                ->run($command);

            if (!$result->successful()) {
                $error = trim($result->errorOutput() ?: $result->output());

                throw ValidationException::withMessages([
                    'phase' => 'Could not create a backup before deleting this phase. Deletion was cancelled.'
                        . ($error !== '' ? " ({$table}: {$error})" : ''),
                ]);
            }

            $output = trim($result->output());
            if ($output !== '') {
                $contents .= $output . "\n\n";
            }
        }

        if ($contents === '') {
            $contents = "-- Phase delete backup for loan_phase_id={$phaseId} (no row data captured)\n";
        }

        if (File::put($path, $contents) === false) {
            throw new RuntimeException("Could not write phase delete backup to {$path}");
        }

        return [
            'path' => $path,
            'tables' => array_column($tables, 0),
        ];
    }
}
