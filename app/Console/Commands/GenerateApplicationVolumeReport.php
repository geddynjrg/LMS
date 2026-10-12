<?php

namespace App\Console\Commands;

use App\Exports\ApplicationVolumeReportExport;
use App\Services\Reports\ApplicationVolumePreviewService;
use App\Services\Reports\ApplicationVolumeReportService;
use App\Services\Reports\LibreOfficeDocumentConverter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Maatwebsite\Excel\Facades\Excel;

class GenerateApplicationVolumeReport extends Command
{
    protected $signature = 'reports:application-volume
        {type=constituencies : constituencies|phases|groups|members}
        {--county= : County code}
        {--constituency= : Constituency code}
        {--phase= : Phase ID}
        {--group-loan= : Group loan ID}
        {--output= : Output path under storage/app (default auto)}
        {--preview : Print first 20 rows to console instead of writing Excel}
        {--preview-pdf : Build Excel and convert to PDF with LibreOffice for viewing}
        {--format=xlsx : xlsx|pdf (pdf uses LibreOffice)}';

    protected $description = 'Preview or download application-volume reports (constituency / phase / group / member). PDF preview uses LibreOffice.';

    public function handle(
        ApplicationVolumeReportService $reports,
        ApplicationVolumePreviewService $previewService,
        LibreOfficeDocumentConverter $libreOffice
    ): int {
        $type = (string) $this->argument('type');
        if (! isset(ApplicationVolumeReportService::TYPES[$type])) {
            $this->error('Invalid type. Use: '.implode(', ', array_keys(ApplicationVolumeReportService::TYPES)));

            return self::FAILURE;
        }

        $params = [
            'type' => $type,
            'county_code' => $this->option('county') ?: null,
            'constituency_code' => $this->option('constituency') ?: null,
            'phase_id' => $this->option('phase') ?: null,
            'group_loan_id' => $this->option('group-loan') ?: null,
        ];

        if ($this->option('preview')) {
            try {
                $result = $reports->build($params + ['preview_limit' => 20]);
            } catch (\Throwable $e) {
                $this->error($e->getMessage());

                return self::FAILURE;
            }

            $this->info($result['title'].' — '.$result['total'].' row(s)');
            $this->table(
                $result['headings'],
                $result['rows']->map(fn ($r) => array_map(fn ($v) => is_scalar($v) ? (string) $v : json_encode($v), $r))->all()
            );
            if ($result['truncated']) {
                $this->warn('Showing console preview only. Use --preview-pdf for LibreOffice PDF, or omit --preview to export.');
            }

            return self::SUCCESS;
        }

        if ($this->option('preview-pdf') || $this->option('format') === 'pdf') {
            if (! $libreOffice->isAvailable()) {
                $this->error('LibreOffice is not installed in this environment.');

                return self::FAILURE;
            }

            try {
                $built = $previewService->buildLibreOfficePreview($params);
            } catch (\Throwable $e) {
                $this->error($e->getMessage());

                return self::FAILURE;
            }

            $this->info($built['title'].' — '.$built['total'].' row(s)');
            $this->info('Excel: storage/app/'.$built['xlsx_relative']);
            $this->info('PDF (LibreOffice): storage/app/'.$built['pdf_relative']);
            $this->line('Open with: xdg-open storage/app/'.$built['pdf_relative'].'   (or any PDF viewer / LibreOffice)');

            if ($output = $this->option('output')) {
                $target = storage_path('app/'.ltrim($output, '/'));
                File::ensureDirectoryExists(dirname($target));
                File::copy(storage_path('app/'.$built['pdf_relative']), $target);
                $this->info('Copied PDF to storage/app/'.ltrim($output, '/'));
            }

            return self::SUCCESS;
        }

        try {
            $result = $reports->build($params);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info($result['title'].' — '.$result['total'].' row(s)');

        $relative = $this->option('output')
            ?: ('reports/application-volume-'.$type.'-'.now()->format('Ymd-His').'.xlsx');
        $relative = ltrim($relative, '/');

        Excel::store(
            new ApplicationVolumeReportExport($result['headings'], $result['rows'], $result['title']),
            $relative
        );

        $this->info('Saved to storage/app/'.$relative);

        return self::SUCCESS;
    }
}
