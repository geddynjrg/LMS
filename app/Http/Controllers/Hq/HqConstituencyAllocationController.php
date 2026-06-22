<?php

namespace App\Http\Controllers\Hq;

use App\Exports\ConstituencyAllocationTemplateExport;
use App\Http\Controllers\Controller;
use App\Models\Constituency;
use App\Services\Constituency\ConstituencyAllocationService;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class HqConstituencyAllocationController extends Controller
{
    public function index()
    {
        return view('hq.constituency-allocations');
    }

    public function downloadTemplate(
        Constituency $constituency,
        ConstituencyAllocationService $service,
    ): BinaryFileResponse {
        $constituency->load('county');
        $profile = $service->forConstituency($constituency->constituency_code);

        $filename = 'constituency-allocations-' . $constituency->slug . '-' . now()->format('Ymd') . '.xlsx';

        return Excel::download(
            new ConstituencyAllocationTemplateExport($constituency, $profile),
            $filename
        );
    }
}
