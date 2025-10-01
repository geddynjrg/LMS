<?php

namespace App\Livewire;

use App\Models\Constituency;
use App\Models\County;
use Livewire\Component;
use App\Models\Group;
use Carbon\Carbon;
use App\Exports\GroupInstInfor;
use Maatwebsite\Excel\Facades\Excel;

class DisbursementReport extends Component
{

    public Group $group;
    public $date;
    public County $county;
    public Constituency $constituency;

    public function mount (County $county, Constituency $constituency)
    {
        $constituency->load([
            'wards.groups'
        ]);
        $this->county = $county;
        $this->constituency = $constituency;
        $this->date = $this->fy(Carbon::now()->isoFormat('Do MMMM YYYY'));
        $this->group = $this->constituency->wards[0]->groups[0];
    }

    private function fy($date)
    {
        $date = Carbon::parse($date);
        $year = $date->year;

        // Financial year starts on 1st July and ends on 30th June
        if ($date->month > 6) {
            $financialYear = $year . '/' . ($year + 1);
        } else {
            $financialYear = ($year - 1) . '/' . $year;
        }

        return $financialYear;
    }

    public function export()
    {
        return Excel::download(new GroupInstInfor($this->group, $this->constituency), $this->group->name.'_institutional_information.xlsx');
    }

    public function render()
    {


        return view('livewire.reports.disbursement-report');
    }
}
