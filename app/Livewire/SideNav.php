<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Constituency;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class SideNav extends Component
{
    public Collection $constituencies;
    public $county;
    public $constituency = '';
    public $nav;
    public $currentConstituency = null;
    public $routeName;
    public $user;

    public function mount()
    {
        $this->user = auth()->user();
        $this->county = $this->user->county;
        if (!empty($this->user->constituencies)) $this->constituencies = $this->user->constituencies;
        $this->currentConstituency = $this->user->currentConstituency;
        //if ($this->constituencies->isEmpty())  dd($this->constituencies);

        $this->navigation();
    }

    function navigation()
    {
        $this->routeName = Route::currentRouteName();

        //dd($this->routeName);
        if ($this->routeName == 'groupsinfo' || $this->routeName == 'groupinfo' || $this->routeName == 'groupMembers' || $this->routeName == 'multiGroupMembers') {
            $this->nav = 'groups';
        }elseif ($this->routeName == 'const.dashboard') {
            $this->nav = 'const.dashboard';
        }elseif ($this->routeName == 'grouploans' || $this->routeName == 'loanreceipts' || $this->routeName == 'loanmembers' || $this->routeName == 'grouploan' || $this->routeName == 'loanapplication' || $this->routeName == 'loanphase' || $this->routeName == 'applicants' || $this->routeName == 'applicant') {
            $this->nav = 'loans';
        }
        elseif ($this->routeName == 'codelist' || $this->routeName == 'disbursement' || $this->routeName == 'dynamic-report' || $this->routeName == 'data-upload' || $this->routeName == 'export') {
            $this->nav = 'reports';
        }
        elseif ($this->routeName == 'monitor_home' || $this->routeName == 'data_view') {
            $this->nav = 'monitoring';
        }

    }

    public function selected (string $code)
    {
        // 1.  Find the constituency by its code
        $constituency = Constituency::where('constituency_code', $code)->firstOrFail();

        // 2.  Authorisation – make sure user is assigned to it
        if (! $this->user->constituencies()->wherePivot('constituency_code', $code)->exists()) {
            return redirect()->route('dashboard')
                ->with('error', 'You are not authorised for that constituency.');
        }

        // 3.  Persist as the user’s current constituency
        $this->user->update(['current_constituency_code' => $code]);

        // 4.  Optional: keep a copy in session for quick access
        session(['activeConstituency' => $constituency]);

        // 5.  Build the URL pieces (slug form)
        $countySlug = $constituency->county->slug ?? Str::slug($constituency->county->county_name);

        $constituencySlug  = $constituency->slug;

        // 6.  Decide where to redirect
        $route = match ($this->routeName) {
            'const.groups.index'  => ['const.groups.index',  ['county' => $countySlug, 'constituency' => $constituencySlug]],
            'const.groups.members'  => ['const.groups.members',  ['county' => $countySlug, 'constituency' => $constituencySlug]],
            'const.groups.multi-group-members'  => ['const.groups.multi-group-members',  ['county' => $countySlug, 'constituency' => $constituencySlug]],
            'const.loans.phases'  => ['const.loans.phases',  ['county' => $countySlug, 'constituency' => $constituencySlug]],
            'const.loans.application'  => ['const.loans.application',  ['county' => $countySlug, 'constituency' => $constituencySlug]],
            'const.loans.applicants'  => ['const.loans.applicants',  ['county' => $countySlug, 'constituency' => $constituencySlug]],
            'const.loans.grouploans'  => ['const.loans.grouploans',  ['county' => $countySlug, 'constituency' => $constituencySlug]],
            'const.loans.loanreceipts'  => ['const.loans.loanreceipts',  ['county' => $countySlug, 'constituency' => $constituencySlug]],
            'const.reports.codelist'  => ['const.reports.codelist',  ['county' => $countySlug, 'constituency' => $constituencySlug]],
            'const.reports.general'  => ['const.reports.dynamic-report',  ['county' => $countySlug, 'constituency' => $constituencySlug]],
            'const.reports.disbursement'  => ['const.reports.disbursement',  ['county' => $countySlug, 'constituency' => $constituencySlug]],
            'const.reports.data-upload'  => ['const.reports.data-upload',  ['county' => $countySlug, 'constituency' => $constituencySlug]],
            'const.loans.loanmembers'  => ['const.loans.loanmembers',  ['county' => $countySlug, 'constituency' => $constituencySlug]],
            'const.monitoring.monitor_home'  => ['const.monitoring.monitor_home',  ['county' => $countySlug, 'constituency' => $constituencySlug]],
            'const.monitoring.data_view'  => ['const.monitoring.data_view',  ['county' => $countySlug, 'constituency' => $constituencySlug]],
            'users.viewusers'  => ['users.viewusers', ['county' => $countySlug]],
            'users.adduser'  => ['users.adduser', ['county' => $countySlug]],
            default => ['const.dashboard',   ['county' => $countySlug, 'constituency' => $constituencySlug]],
        };

        return redirect()->route($route[0], $route[1]);
    }

    public function render()
    {
        return view('livewire.side-nav');
    }
}
