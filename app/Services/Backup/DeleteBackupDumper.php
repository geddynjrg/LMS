<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class DeleteBackupDumper
{
    /**
     * @param  list<array{0: string, 1: string}>  $tables  Pairs of [table name, WHERE clause]
     * @return array{path: string, tables: list<string>, skipped: list<string>}
     */
    public function dump(
        string $directory,
        string $filename,
        array $tables,
        string $errorField,
        string $errorMessage,
        int $timeoutSeconds = 300,
    ): array {
        File::ensureDirectoryExists($directory);

        $path = rtrim($directory, '/') . '/' . $filename;
        $config = config('database.connections.' . config('database.default'));
        $database = $config['database'];
        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? '3306';
        $username = $config['username'];
        $password = $config['password'] ?? '';

        $dumpBinary = rtrim($config['dump']['dump_binary_path'] ?? '/usr/bin', '/') . '/mysqldump';

        if (!is_executable($dumpBinary)) {
            throw ValidationException::withMessages([
                $errorField => $errorMessage . ' (mysqldump binary not found or not executable)',
            ]);
        }

        $contents = '';
        $dumpedTables = [];
        $skippedTables = [];

        foreach ($tables as [$table, $where]) {
            if (!Schema::hasTable($table)) {
                $skippedTables[] = $table;

                continue;
            }

            $column = $this->extractWhereColumn($where);
            if ($column !== null && !Schema::hasColumn($table, $column)) {
                Log::warning('Delete backup skipped table because WHERE column is missing.', [
                    'table' => $table,
                    'column' => $column,
                    'where' => $where,
                ]);
                $skippedTables[] = $table;

                continue;
            }

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
                ->timeout($timeoutSeconds)
                ->run($command);

            if (!$result->successful()) {
                $error = trim($result->errorOutput() ?: $result->output());

                throw ValidationException::withMessages([
                    $errorField => $errorMessage
                        . ($error !== '' ? " ({$table}: {$error})" : ''),
                ]);
            }

            $output = trim($result->output());
            if ($output !== '') {
                $contents .= $output . "\n\n";
            }

            $dumpedTables[] = $table;
        }

        if ($contents === '') {
            $contents = "-- Delete backup (no row data captured)\n";
        }

        if (File::put($path, $contents) === false) {
            throw new RuntimeException("Could not write delete backup to {$path}");
        }

        return [
            'path' => $path,
            'tables' => $dumpedTables,
            'skipped' => $skippedTables,
        ];
    }

    protected function extractWhereColumn(string $where): ?string
    {
        if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\s*(=|IN\b)/i', trim($where), $matches)) {
            return $matches[1];
        }

        return null;
    }
}
