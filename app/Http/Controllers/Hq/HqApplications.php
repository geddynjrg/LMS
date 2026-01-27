<?php

namespace App\Http\Controllers\Hq;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LoanPhase;

class HqApplications extends Controller
{
    public function index(Request $request) {
        return view('hq.app.application_list');
    }

    public function review(Request $request, LoanPhase $phase)
    {
        return View('hq.app.phase_review', [
            'phase' => $phase
        ]);
    }

    public function tasks(Request $request)
    {
        return view('hq.app.phase_tasks');
    }
}
