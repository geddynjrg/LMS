<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Constituency as Constituencies;
use App\Models\Constituency;
use App\Models\County;

class ConstituencyController extends Controller
{
    public function index(Request $request, County $county, Constituency $constituency) {
        return view('dashboard.constituency', [
            'county' => $county,
            'constituency' =>  $constituency,
        ]);

    }

}
