<?php

namespace App\Http\Controllers;

use App\Models\Constituency;
use App\Models\County;
use Illuminate\Http\Request;

class MeReportingController extends Controller
{
    //
    public function home(Request $request, County $county, Constituency $constituency)
    {
        return View('monitoring.home', [
            'constituency' => $constituency,
            'county' => $county,
        ]);
    }
    public function data(Request $request, County $county, Constituency $constituency)
    {
        return View('monitoring.data-view', [
            'constituency' => $constituency,
            'county' => $county,
        ]);
    }
}
