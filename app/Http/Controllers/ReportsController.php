<?php

namespace App\Http\Controllers;

use App\Models\Constituency;
use App\Models\County;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    public function codeList(Request $request, County $county, Constituency $constituency)
    {
        return View('reports.code_list', [
            'constituency' => $constituency,
            'county' => $county,
        ]);
    }

    public function disbursement(Request $request, County $county, Constituency $constituency)
    {
        return View('reports.disbursement_report', [
            'constituency' => $constituency,
            'county' => $county,
        ]);
    }

    public function dynamicReport(Request $request, County $county, Constituency $constituency)
    {
        return View('reports.dynamic_report', [
            'constituency' => $constituency,
            'county' => $county,
        ]);
    }

    public function dataUpload(Request $request, County $county, Constituency $constituency)
    {
        return View('reports.data_upload', [
            'constituency' => $constituency,
            'county' => $county,
        ]);
    }

    public function export(Request $request, County $county, Constituency $constituency)
    {
        return View('reports.export', [
            'constituency' => $constituency,
            'county' => $county,
        ]);
    }

    public function restoreDeletedData(Request $request, County $county, Constituency $constituency)
    {
        return View('reports.restore_deleted_data', [
            'constituency' => $constituency,
            'county' => $county,
        ]);
    }

    public function countyAuditTrail(Request $request, County $county)
    {
        return View('reports.audit_trail', [
            'county' => $county,
        ]);
    }

    public function constituencyAuditTrail(Request $request, County $county, Constituency $constituency)
    {
        return View('reports.constituency_audit_trail', [
            'constituency' => $constituency,
            'county' => $county,
        ]);
    }

    public function nationalIndex()
    {
        return redirect()->route('hq.reports.index');
    }
}
