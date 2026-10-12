<?php

namespace App\Http\Controllers\Hq;

use App\Exports\ApplicationVolumeReportExport;
use App\Http\Controllers\Controller;
use App\Services\Reports\ApplicationVolumePreviewService;
use App\Services\Reports\ApplicationVolumeReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ApplicationVolumeExportController extends Controller
{
    public function __construct(
        protected ApplicationVolumeReportService $reports,
        protected ApplicationVolumePreviewService $previewService
    ) {
    }

    public function download(Request $request)
    {
        try {
            $result = $this->reports->build($this->paramsFromRequest($request));
        } catch (\Throwable $e) {
            return back()->with('application_volume_error', $e->getMessage());
        }

        $slug = str_replace('_', '-', $result['type']);
        $filename = 'application-volume-'.$slug.'-'.now()->format('Ymd-His').'.xlsx';

        return Excel::download(
            new ApplicationVolumeReportExport($result['headings'], $result['rows'], $result['title']),
            $filename
        );
    }

    public function previewPdf(Request $request)
    {
        try {
            $built = $this->previewService->buildLibreOfficePreview($this->paramsFromRequest($request));
        } catch (\Throwable $e) {
            return back()->with('application_volume_error', $e->getMessage());
        }

        return redirect()->route('reports.application-volume.preview-file', [
            'token' => $built['token'],
            'format' => 'pdf',
        ]);
    }

    public function show(Request $request, string $token, string $format)
    {
        $format = strtolower($format);
        if (! in_array($format, ['pdf', 'xlsx', 'html'], true)) {
            abort(404);
        }

        $relative = 'reports/previews/'.$token.'.'.$format;
        if (! Storage::disk('local')->exists($relative)) {
            abort(404, 'Preview file expired or not found. Generate the preview again.');
        }

        $mime = match ($format) {
            'pdf' => 'application/pdf',
            'html' => 'text/html; charset=UTF-8',
            default => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        };

        $disposition = $request->boolean('download') ? 'attachment' : 'inline';
        $filename = 'application-volume-preview.'.$format;

        return response()->file(Storage::disk('local')->path($relative), [
            'Content-Type' => $mime,
            'Content-Disposition' => $disposition.'; filename="'.$filename.'"',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function paramsFromRequest(Request $request): array
    {
        $type = (string) $request->input('type', 'constituencies');
        if (! isset(ApplicationVolumeReportService::TYPES[$type])) {
            $type = 'constituencies';
        }

        return [
            'type' => $type,
            'county_code' => $request->filled('county_code') ? (string) $request->input('county_code') : null,
            'constituency_code' => $request->filled('constituency_code') ? (string) $request->input('constituency_code') : null,
            'phase_id' => $request->filled('phase_id') ? $request->input('phase_id') : null,
            'group_loan_id' => $request->filled('group_loan_id') ? $request->input('group_loan_id') : null,
        ];
    }
}
