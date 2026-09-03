<?php

namespace App\Jobs;

use App\Imports\MembersTemplateImport;
use App\Models\Constituency;
use App\Support\ExcelEmptyRowTrimmer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ProcessMembersTemplateImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 900;

    public function __construct(
        public string $filePath,
        public string $constituencyCode,
        public array $phases,
        public string $progressKey,
        public string $resultKey,
    ) {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        ini_set('memory_limit', '1024M');
        set_time_limit($this->timeout);

        $this->updateProgress(8, 'Preparing spreadsheet...');

        $constituency = Constituency::query()
            ->where('constituency_code', $this->constituencyCode)
            ->firstOrFail();

        try {
            $trim = ExcelEmptyRowTrimmer::trim($this->filePath);
            if (($trim['removed'] ?? 0) > 0) {
                $this->updateProgress(
                    10,
                    "Removed {$trim['removed']} empty Excel rows, then reading {$trim['kept']} data rows..."
                );
            } else {
                $this->updateProgress(10, 'Reading spreadsheet...');
            }

            $import = new MembersTemplateImport($constituency, $this->phases, $this->progressKey);
            Excel::import($import, $this->filePath);

            $result = $import->getImportResult();

            if ($result['success']) {
                Cache::put($this->resultKey, [
                    'success' => true,
                    'message' => $result['message'],
                    'stats' => [
                        'imported' => $result['imported_count'] ?? 0,
                        'skipped' => 0,
                        'new' => $result['new_members_created'] ?? 0,
                    ],
                ], now()->addHour());

                $this->updateProgress(100, 'Import completed successfully.');
            } else {
                Cache::put($this->resultKey, [
                    'success' => false,
                    'message' => $result['message'] ?? 'Import failed.',
                    'errors' => $import->getErrors(),
                    'total_errors' => count($import->getErrors()),
                    'display_errors' => array_slice($import->getErrors(), 0, 20),
                    'stats' => [
                        'imported' => 0,
                        'skipped' => 0,
                        'new' => 0,
                    ],
                ], now()->addHour());

                $this->updateProgress(100, 'Import failed — see errors below.');
            }
        } catch (Throwable $e) {
            Log::error('Members template import failed', [
                'constituency_code' => $this->constituencyCode,
                'file' => $this->filePath,
                'error' => $e->getMessage(),
            ]);

            $message = $e->getMessage();
            $errors = str_contains($message, 'Validation errors found:')
                ? array_filter(explode("\n", str_replace('Validation errors found:', '', $message)))
                : [$message];

            Cache::put($this->resultKey, [
                'success' => false,
                'message' => $message,
                'errors' => array_values($errors),
                'total_errors' => count($errors),
                'display_errors' => array_slice(array_values($errors), 0, 20),
                'stats' => [
                    'imported' => 0,
                    'skipped' => 0,
                    'new' => 0,
                ],
            ], now()->addHour());

            $this->updateProgress(100, 'Import failed.');
        } finally {
            if (is_file($this->filePath)) {
                @unlink($this->filePath);
            }

            Cache::put($this->progressKey, [
                'progress' => 100,
                'message' => Cache::get($this->progressKey)['message'] ?? 'Finished',
                'status' => 'done',
                'updated_at' => now(),
            ], now()->addHour());
        }
    }


    public function failed(?Throwable $e): void
    {
        $message = $e?->getMessage() ?: 'Import failed.';
        if (str_contains($message, 'attempted too many times') || str_contains(class_basename($e ?? new \Exception()), 'MaxAttempts')) {
            $message = 'Import was interrupted by the queue (job ran longer than the queue reservation). Please upload again. If this persists, copy only the member data rows into a new blank workbook first.';
        } elseif (str_contains($message, 'timed out') || str_contains(class_basename($e ?? new \Exception()), 'Timeout')) {
            $message = 'Import timed out. The Excel file likely contains tens of thousands of empty formatted rows. Copy only the member rows into a new blank workbook and upload again.';
        }

        Cache::put($this->resultKey, [
            'success' => false,
            'message' => $message,
            'errors' => [$message],
            'total_errors' => 1,
            'display_errors' => [$message],
            'stats' => [
                'imported' => 0,
                'skipped' => 0,
                'new' => 0,
            ],
        ], now()->addHour());

        $this->updateProgress(100, 'Import failed.');
    }

    private function updateProgress(int $progress, string $message): void
    {
        Cache::put($this->progressKey, [
            'progress' => $progress,
            'message' => $message,
            'status' => $progress >= 100 ? 'done' : 'running',
            'updated_at' => now(),
        ], now()->addHour());
    }
}
