<?php

namespace App\Http\Controllers;

use App\Models\County;
use App\Models\User;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    //
    public function index(Request $request, County $county)
    {
        return view ('users.view-users', [
            'county' => $county,
        ]);
    }

    public function create(Request $request, County $county)
    {
        return view ('users.create-user', [
            'county' => $county,
        ]);
    }

    public function viewUser (Request $request, County $county, User $user)
    {
        return view ('users.view-user', [
            'county' => $county,
            'user' => $user,
        ]);
    }
}
