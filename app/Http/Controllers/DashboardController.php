<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Constituency as Constituencies;

class DashboardController extends Controller
{
    public function index(Request $request) {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        if ($user && ($user->hasRole('hq_officer') || $user->hasRole('admin'))) {
            return view('hq.dashboard');
        }

        if ($user && $user->hasRole('head_of_secretariat') && $request->boolean('national')) {
            return view('hq.dashboard');
        }

        if ($user && $user->hasRole('head_of_secretariat')) {
            return redirect()->route('secretariat.phases.index');
        }

        if ($user && $user->hasRole('accountant')) {
            return redirect()->route('accountant.phases.index');
        }

        $constituencies = $user->constituencies;

        return view('dashboard.dashboard', [
            'constituencies' => $constituencies,
        ]);
    }
}
