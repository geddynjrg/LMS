<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Constituency as Constituencies;

class DashboardController extends Controller
{
    public function index(Request $request) {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $constituencies = $user->constituencies;

        if ($user && $user->hasRole('hq_officer')) {
            return view('hq.dashboard');
        }

        return view('dashboard.dashboard', [
            'constituencies' => $constituencies,
        ]);
    }
}
