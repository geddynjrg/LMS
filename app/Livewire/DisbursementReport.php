<?php

namespace App\Livewire;

use App\Exports\ConstituencyDisbursementExport;
use App\Models\Constituency;
use App\Models\County;
use App\Models\GroupLoan;
use Carbon\Carbon;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class DisbursementReport extends Component
{
    public $date;
    public County $county;
    public Constituency $constituency;
    public $disbursements;
    public $totalAmountIssued = 0;

    public function mount(County $county, Constituency $constituency)
    {
        $this->county = $county;
        $this->constituency = $constituency->load('county');
        $this->date = $this->fy(Carbon::now()->isoFormat('Do MMMM YYYY'));
        $this->loadDisbursements();
    }

    private function loadDisbursements(): void
    {
        $this->disbursements = GroupLoan::query()
            ->where('loan_status', 'financed')
            ->whereHas('group.ward', function ($query) {
                $query->where('constituency_code', $this->constituency->constituency_code);
            })
            ->with([
                'group.ward',
                'loanPhase' => fn ($query) => $query->select('id', 'phase', 'constituency_code'),
            ])
            ->orderByDesc('disbursement_date')
            ->orderBy('loan_type')
            ->get();

        $this->totalAmountIssued = $this->disbursements->sum('amount_issued');
    }

    private function fy($date)
    {
        $date = Carbon::parse($date);
        $year = $date->year;

        if ($date->month > 6) {
            $financialYear = $year . '/' . ($year + 1);
        } else {
            $financialYear = ($year - 1) . '/' . $year;
        }

        return $financialYear;
    }

    public function export()
    {
        $fileName = ucwords(strtolower($this->constituency->constituency_name))
            . '_Disbursement_Report_'
            . now()->format('Y-m-d')
            . '.xlsx';

        return Excel::download(
            new ConstituencyDisbursementExport($this->constituency, $this->disbursements),
            $fileName
        );
    }

    public function render()
    {
        return view('livewire.reports.disbursement-report');
    }
}
