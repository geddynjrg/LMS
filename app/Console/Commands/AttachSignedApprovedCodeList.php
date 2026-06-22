<?php

namespace App\Console\Commands;

use App\Models\LoanPhase;
use App\Models\SecretariatApproval;
use App\Services\Exports\SignedCodeListPdfService;
use Illuminate\Console\Command;

class AttachSignedApprovedCodeList extends Command
{
    protected $signature = 'phases:attach-signed-code-list
                            {phase? : Loan phase ID or slug}
                            {--constituency= : Optional constituency code to process all eligible phases}';

    protected $description = 'Generate and attach a Head of Secretariat signed PDF as the approved code list document';

    public function handle(SignedCodeListPdfService $service): int
    {
        $phases = $this->resolvePhases();

        if ($phases->isEmpty()) {
            $this->warn('No eligible phases found.');

            return self::FAILURE;
        }

        $attached = 0;

        foreach ($phases as $phase) {
            if (! $service->canDownload($phase)) {
                $this->line("Skipping {$phase->phase} (#{$phase->id}): secretariat signature not available.");

                continue;
            }

            if ($service->storeAsApprovedCodeListDocument($phase)) {
                $attached++;
                $this->info("Attached signed approved code list for {$phase->phase} (#{$phase->id}).");
            } else {
                $this->error("Failed to attach signed code list for {$phase->phase} (#{$phase->id}).");
            }
        }

        $this->info("Done. Attached {$attached} signed code list document(s).");

        return $attached > 0 ? self::SUCCESS : self::FAILURE;
    }

    private function resolvePhases()
    {
        $identifier = $this->argument('phase');
        $constituencyCode = $this->option('constituency');

        if ($identifier) {
            $phase = is_numeric($identifier)
                ? LoanPhase::find($identifier)
                : LoanPhase::where('slug', $identifier)->first();

            return $phase ? collect([$phase]) : collect();
        }

        $query = LoanPhase::query()
            ->whereHas('secretariatApprovals', function ($q) {
                $q->where('approval_type', SecretariatApproval::TYPE_POST_HQ_REVIEW)
                    ->whereNotNull('approved_at')
                    ->whereNotNull('signature_path');
            });

        if ($constituencyCode) {
            $query->where('constituency_code', $constituencyCode);
        }

        return $query->orderBy('id')->get();
    }
}
