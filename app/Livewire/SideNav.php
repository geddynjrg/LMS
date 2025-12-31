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

    private function navigation(): void
    {
        $this->routeName = Route::currentRouteName();

        if (Route::is('const.groups.*') || Route::is('groups*')) {
            $this->nav = 'groups';
            return;
        }
        if (Route::is('const.loans.*') || Route::is('grouploans*')) {
            $this->nav = 'loans';
            return;
        }
        if (Route::is('const.reports.*')) {
            $this->nav = 'reports';
            return;
        }
        if (Route::is('const.monitoring.*')) {
            $this->nav = 'monitoring';
            return;
        }
        if (Route::is('const.app.*')) {
            $this->nav = 'app';
            return;
        }

        $this->nav = null;
    }

    public function selected (string $code)
    {
        $constituency = Constituency::with('county')->where('constituency_code', $code)->first();
        if (! $constituency) {
            session()->flash('error', 'Constituency not found.');
            return redirect()->route('dashboard');
        }

        if (! $this->user->constituencies->contains('constituency_code', $code)) {
            session()->flash('error', 'You are not authorised for that constituency.');
            return redirect()->route('dashboard');
        }

        $this->user->update(['current_constituency_code' => $code]);
        session(['activeConstituencyCode' => $code]);

        // 4.  Optional: keep a copy in session for quick access
        session(['activeConstituency' => $constituency]);

        // 5.  Build the URL pieces (slug form)
        $countySlug = $constituency->county?->slug ?? Str::slug(optional($constituency->county)->county_name ?? 'unknown');

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
            'const.app.app_dashboard'  => ['const.app.app_dashboard',  ['county' => $countySlug, 'constituency' => $constituencySlug]],
            'const.app.app_loans'  => ['const.app.app_loans',  ['county' => $countySlug, 'constituency' => $constituencySlug]],
            'const.app.app_loan'  => ['const.app.app_loan',  ['county' => $countySlug, 'constituency' => $constituencySlug]],
            'const.app.app_review'  => ['const.app.app_review',  [
                    'county' => $countySlug,
                    'constituency' => $constituencySlug,
                    'phase' => 1, // You'll need to get the active phase ID
                ]],
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
