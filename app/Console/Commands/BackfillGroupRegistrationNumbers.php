<?php

namespace App\Console\Commands;

use App\Models\Group;
use App\Models\TempApplication;
use App\Services\Loan\LoanDebtCheckService;
use Illuminate\Console\Command;

class BackfillGroupRegistrationNumbers extends Command
{
    protected $signature = 'groups:backfill-registrations {--dry-run : Report changes without writing}';

    protected $description = 'Backfill missing group registration numbers from portal applications and historical name matches';

    public function handle(LoanDebtCheckService $debtCheckService): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $updated = 0;
        $lookup = [];

        TempApplication::query()
            ->whereNotNull('group_registration_number')
            ->where('group_registration_number', '!=', '')
            ->whereNotNull('group_name')
            ->whereNotNull('constituency_code')
            ->orderBy('id')
            ->select(['id', 'group_name', 'group_registration_number', 'constituency_code'])
            ->chunkById(500, function ($applications) use ($debtCheckService, &$lookup) {
                foreach ($applications as $application) {
                    $registration = strtoupper(trim((string) $application->group_registration_number));
                    if ($registration === '') {
                        continue;
                    }

                    $nameKey = $debtCheckService->normalizeGroupNameKey($application->group_name);
                    if ($nameKey === '') {
                        continue;
                    }

                    $lookup[$nameKey . '|' . $application->constituency_code] = $registration;
                }
            });

        $this->info('Built ' . count($lookup) . ' registration mapping(s) from portal applications.');

        Group::query()
            ->where(function ($query) {
                $query->whereNull('reg_number')->orWhere('reg_number', '');
            })
            ->with('ward:id,constituency_code')
            ->select(['id', 'name', 'reg_number', 'ward_id'])
            ->orderBy('id')
            ->chunkById(200, function ($groups) use ($debtCheckService, $lookup, $dryRun, &$updated) {
                foreach ($groups as $group) {
                    $constituencyCode = $group->ward?->constituency_code;
                    if (!$constituencyCode) {
                        continue;
                    }

                    $nameKey = $debtCheckService->normalizeGroupNameKey($group->name);
                    if ($nameKey === '') {
                        continue;
                    }

                    $registration = $lookup[$nameKey . '|' . $constituencyCode] ?? null;
                    if ($registration === null) {
                        continue;
                    }

                    $this->line(sprintf(
                        '%s %s -> %s',
                        $dryRun ? '[dry-run]' : '[update]',
                        $group->name,
                        $registration,
                    ));

                    if (!$dryRun) {
                        $group->update(['reg_number' => $registration]);
                    }

                    $updated++;
                }
            });

        $this->info(($dryRun ? 'Would update' : 'Updated') . " {$updated} group registration number(s).");

        return self::SUCCESS;
    }
}
