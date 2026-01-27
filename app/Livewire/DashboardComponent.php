<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Constituency as Constituencies;
use Illuminate\Support\Str;



class DashboardComponent extends Component
{
    public $county;
    public $constituencies;
    public $selectedConstituency = '';
    public $user;


    protected $rules = [
        'selectedConstituency' => 'required|max:3|exists:constituencies,constituency_code',
    ];

    public function mount($county, $constituencies)
    {
        $this->user = auth()->user();
        $this->county = $county;
        $this->constituencies = $constituencies;

    }

    public function selected($code) {
        $constituency = Constituencies::where('constituency_code', $code)->firstOrFail();
        if (!$this->user->constituencies()->wherePivot('constituency_code', $code)->exists()) {
            return redirect()->route('dashboard')
                ->with('error', 'You are not authorised for that constituency.');
        }
        // 3.  Persist as the user’s current constituency
        $this->user->update(['current_constituency_code' => $code]);

        // 4.  Optional: keep a copy in session for quick access
        session(['activeConstituency' => $constituency]);
        $countySlug = $constituency->county->slug ?? Str::slug($constituency->county->county_name);

        $constituencySlug  = $constituency->slug;

        session(['constituency' => $constituency]);

        $route = ['const.dashboard',   ['county' => $countySlug, 'constituency' => $constituencySlug]];
        return redirect()->route($route[0], $route[1]);
    }

    public function render()
    {
        return view('livewire.dashboard-component');
    }
}
