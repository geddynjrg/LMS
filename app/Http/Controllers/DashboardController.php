<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Constituency as Constituencies;

class DashboardController extends Controller
{
    public function index(Request $request) {
        $constituencies = auth()->user()->constituencies;

        return view('dashboard.dashboard', [
            'constituencies' => $constituencies,
        ]);
    }


}
