<?php

namespace App\Http\Controllers;

use App\Models\Constituency;
use App\Models\County;
use App\Models\Group;
use Illuminate\Http\Request;

class LoansController extends Controller
{
    public function groupLoans(Request $request, County $county, Constituency $constituency)
    {

        return View('loan.grouploans', [
            'constituency' => $constituency,
            'county' => $county,
        ]);
    }

    public function receipts(Request $request, County $county, Constituency $constituency)
    {

        return View('loan.loan_receipts', [
            'constituency' => $constituency,
            'county' => $county
        ]);
    }

    public function members(Request $request, County $county, Constituency $constituency)
    {
        return View('loan.members', [
            'constituency' => $constituency,
            'county' => $county,
        ]);
    }

    public function groupLoan(Request $request, County $county, Constituency $constituency, Group $group)
    {
        return View('loan.grouploan', [
            'constituency' => $constituency,
            'county' => $county,
            'group' => $group,
        ]);
    }


    public function application(Request $request, County $county, Constituency $constituency)
    {
        return View('loan.application', [
            'county' => $county,
            'constituency' => $constituency,
        ]);
    }


    public function loanphase(Request $request, County $county, Constituency $constituency)
    {
        return View('loan.phase', [
            'constituency' => $constituency,
            'county' => $county,
        ]);
    }

    public function applicants(Request $request, County $county, Constituency $constituency)
    {
        return View('loan.applicants', [
            'county' => $county,
            'constituency' => $constituency,
        ]);
    }

    public function applicant(Request $request, County $county, Constituency $constituency, Group $group)
    {
        return View('loan.applicant', [
            'county' => $county,
            'constituency' => $constituency,
            'group' => $group,
        ]);
    }


}
