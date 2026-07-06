<?php

namespace App\Services\Group;

use App\Models\GroupLoan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class GroupDeleteBackupService
{
    /**
     * @return array{path: string, tables: list<string>}
     */
    public function backup(GroupLoan $groupLoan): array
    {
        $groupLoan->loadMissing('group');

        $groupLoanId = $groupLoan->id;
        $date = now()->format('Y-m-d');
        $directory = storage_path('app/backups/group-delete');
        $path = "{$directory}/group-delete-backup-{$groupLoanId}-{$date}.sql";

        File::ensureDirectoryExists($directory);

        $tables = [
            ['group_loans', "id={$groupLoanId}"],
            ['group_members', "group_loans_id={$groupLoanId}"],
            ['loan_receipts', "group_loans_id={$groupLoanId}"],
        ];

        if ($groupLoan->group_id) {
            $tables[] = ['groups', "id={$groupLoan->group_id}"];
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
                ->timeout(120)
                ->run($command);

            if (!$result->successful()) {
                throw ValidationException::withMessages([
                    'group' => 'Could not create a backup before deleting this group. Deletion was cancelled.',
                ]);
            }

            $output = trim($result->output());
            if ($output !== '') {
                $contents .= $output . "\n\n";
            }
        }

        if ($contents === '') {
            $contents = "-- Group delete backup for group_loans_id={$groupLoanId} (no row data captured)\n";
        }

        if (File::put($path, $contents) === false) {
            throw new RuntimeException("Could not write group delete backup to {$path}");
        }

        return [
            'path' => $path,
            'tables' => array_column($tables, 0),
        ];
    }
}
