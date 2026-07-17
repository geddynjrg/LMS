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

        TempApplication::query()
            ->whereNotNull('group_registration_number')
            ->where('group_registration_number', '!=', '')
            ->whereNotNull('group_name')
            ->orderBy('id')
            ->chunkById(200, function ($applications) use ($debtCheckService, $dryRun, &$updated) {
                foreach ($applications as $application) {
                    $registration = trim((string) $application->group_registration_number);
                    if ($registration === '') {
                        continue;
                    }

                    $nameKey = $debtCheckService->normalizeGroupNameKey($application->group_name);
                    if ($nameKey === '') {
                        continue;
                    }

                    $groups = Group::query()
                        ->whereHas(
                            'ward',
                            fn ($query) => $query->where('constituency_code', $application->constituency_code),
                        )
                        ->where(function ($query) {
                            $query->whereNull('reg_number')->orWhere('reg_number', '');
                        })
                        ->get()
                        ->filter(
                            fn (Group $group) => $debtCheckService->normalizeGroupNameKey($group->name) === $nameKey,
                        );

                    foreach ($groups as $group) {
                        $this->line(sprintf(
                            '%s %s -> %s',
                            $dryRun ? '[dry-run]' : '[update]',
                            $group->name,
                            $registration,
                        ));

                        if (!$dryRun) {
                            $group->update(['reg_number' => strtoupper($registration)]);
                        }

                        $updated++;
                    }
                }
            });

        $this->info(($dryRun ? 'Would update' : 'Updated') . " {$updated} group registration number(s).");

        return self::SUCCESS;
    }
}
