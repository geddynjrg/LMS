<?php

namespace App\Http\Controllers\Hq;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HqReportController extends Controller
{
    public function index()
    {
       return View('reports.hq.generate_report');
    }
}
