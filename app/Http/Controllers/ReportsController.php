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
}
