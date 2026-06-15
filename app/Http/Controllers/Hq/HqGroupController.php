<?php

namespace App\Http\Controllers\Hq;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HqGroupController extends Controller
{
    public function index(Request $request) {
        return view('hq.group.groups_list');
    }

    public function dashboard(Request $request) {
        return view('hq.group.dashboard');
    }
}
