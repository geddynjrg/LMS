<?php

namespace App\Http\Controllers\Accountant;

use App\Http\Controllers\Controller;
use App\Models\LoanPhase;

class AccountantController extends Controller
{
    public function index()
    {
        return view('accountant.index');
    }

    public function review(LoanPhase $phase)
    {
        return view('accountant.review', compact('phase'));
    }
}
