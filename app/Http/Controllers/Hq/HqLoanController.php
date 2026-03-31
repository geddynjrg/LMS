<?php

namespace App\Http\Controllers\Hq;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HqLoanController extends Controller
{
    public function phases()
    {
        // ... code
    }

    public function repayments()
    {
        // ... code
    }

    public function receipts()
    {
        return View('hq.loans.receipts');
    }

    public function groupLoans()
    {
        return View('hq.loans.group_loans');
    }
}
