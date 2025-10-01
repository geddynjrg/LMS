<?php

namespace App\Livewire;

use App\Models\Constituency;
use App\Models\County;
use Livewire\Component;
use App\Models\Group;

class GroupsInfo extends Component
{
    public $totalgroups = 0;
    public $youths = 0;
    public $women = 0;
    public $pwds = 0;
    public $groups = null;
    public Constituency $constituency;
    public $county;

    public function mount(County $county, Constituency $constituency) {
        $this->constituency = $constituency;
        $this->county = $county;
        $this->allGroups();
    }

    function allGroups()
    {
        $const_code = $this->constituency->constituency_code;
        $this->groups = Group::whereHas('ward.constituency', function ($query) use ($const_code) {
            $query->where('constituency_code', $const_code);
        })
        ->with(['ward'])
        ->get();

        //dd($this->groups);

        $this->totalgroups = $this->groups->count();

        $this->youths = $this->groups->where('category', 'Youth')->count();
        $this->women = $this->groups->where('category', 'Women')->count();
        $this->pwds = $this->groups->where('category', 'PWD')->count();



    }

    public function render()
    {
        return view('livewire.group.groups-info');
    }
}
