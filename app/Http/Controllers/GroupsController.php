<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Constituency;
use App\Models\County;
use App\Models\Group;

class GroupsController extends Controller
{
    public function groupsInfo(Request $request, County $county, Constituency $constituency) {
        return view ('group.view-groups', [
            'constituency' => $constituency,
            'county' => $county,
        ]);
    }

    public function groupinfo(Request $request, County $county, Constituency $constituency, Group $group) {
        return view ('group.view-group', [
            'county' => $county,
            'constituency' => $constituency,
            'group' => $group,
        ]);
    }

    public function groupmembers(Request $request, County $county, Constituency $constituency)
    {
        return view ('group.view-group-members', [
            'constituency' => $constituency,
            'county' => $county,
        ]);
    }
}
