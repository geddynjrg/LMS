<?php

namespace App\Services\Application;

use App\Models\TempApplication;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class TempApplicationDeleteBackupService
{
    /**
     * @return array{path: string, tables: list<string>}
     */
    public function backup(TempApplication $application): array
    {
        $applicationId = $application->id;
        $date = now()->format('Y-m-d');
        $directory = storage_path('app/backups/application-delete');
        $path = "{$directory}/temp-application-delete-backup-{$applicationId}-{$date}.sql";

        File::ensureDirectoryExists($directory);

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
                    'application' => 'Could not create a backup before deleting this application. Deletion was cancelled.'
                        . ($error !== '' ? " ({$table}: {$error})" : ''),
                ]);
            }

            $output = trim($result->output());
            if ($output !== '') {
                $contents .= $output . "\n\n";
            }
        }

        if ($contents === '') {
            $contents = "-- Temp application delete backup for temp_application_id={$applicationId} (no row data captured)\n";
        }

        if (File::put($path, $contents) === false) {
            throw new RuntimeException("Could not write application delete backup to {$path}");
        }

        return [
            'path' => $path,
            'tables' => array_column($tables, 0),
        ];
    }
}
