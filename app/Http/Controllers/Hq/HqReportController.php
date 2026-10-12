<?php

namespace App\Http\Controllers\Hq;

use App\Http\Controllers\Controller;

class HqReportController extends Controller
{
    public function index()
    {
       return View('reports.hq.generate_report');
    }

    public function applicationVolume()
    {
        return view('reports.hq.application_volume');
    }
}
