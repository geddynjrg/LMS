<?php

namespace App\Console\Commands;

use App\Models\TempApplication;
use App\Services\Loan\LoanApplicationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class RepairApplicationFiles extends Command
{
    protected $signature = 'applications:repair-files
                            {application_id? : Single application ID to repair}
                            {--missing : Repair all applications whose stored paths are missing on disk}';

    protected $description = 'Re-save application documents from raw_data when files are missing from storage';

    public function handle(LoanApplicationService $service): int
    {
        $applicationId = $this->argument('application_id');

        if ($applicationId) {
            $this->repairOne($service, $applicationId);

            return self::SUCCESS;
        }

        if (!$this->option('missing')) {
            $this->error('Provide an application_id or pass --missing to repair all affected applications.');

            return self::FAILURE;
        }

        $query = TempApplication::query()->whereNotNull('raw_data');
        $repaired = 0;
        $failed = 0;

        $query->orderBy('id')->chunk(50, function ($applications) use ($service, &$repaired, &$failed) {
            foreach ($applications as $application) {
                if (!$this->applicationMissingFiles($application)) {
                    continue;
                }

                try {
                    $this->repairOne($service, $application->application_id, false);
                    $repaired++;
                } catch (\Throwable $e) {
                    $failed++;
                    $this->error("{$application->application_id}: {$e->getMessage()}");
                }
            }
        });

        $this->info("Repaired {$repaired} application(s)." . ($failed ? " Failed: {$failed}." : ''));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    protected function repairOne(LoanApplicationService $service, string $applicationId, bool $single = true): void
    {
        $application = TempApplication::where('application_id', $applicationId)->firstOrFail();

        if (!$this->applicationMissingFiles($application)) {
            if ($single) {
                $this->info("{$applicationId}: all referenced files already exist.");
            }

            return;
        }

        $service->repairApplicationFiles($application->fresh());

        if ($single) {
            $this->info("{$applicationId}: files repaired from raw_data.");
        } else {
            $this->line("{$applicationId}: repaired");
        }
    }

    protected function applicationMissingFiles(TempApplication $application): bool
    {
        foreach ($application->all_files ?? [] as $file) {
            $path = $file['data']['path'] ?? null;

            if ($path && !$this->pathExists($path)) {
                return true;
            }
        }

        foreach ($application->groupMembers as $member) {
            foreach (['photo_member_id', 'certificate_of_disability'] as $field) {
                $path = $member->{$field}['path'] ?? null;

                if ($path && !$this->pathExists($path)) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function pathExists(string $path): bool
    {
        $cleanPath = ltrim(preg_replace('#^(storage/|public/)#', '', $path), '/');

        return Storage::disk('local')->exists($cleanPath)
            || Storage::disk('public')->exists($cleanPath);
    }
}
