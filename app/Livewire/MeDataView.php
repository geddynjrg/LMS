<?php

namespace App\Livewire;

use App\Models\Constituency;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MeDataExport;
use App\Models\MeReporting;

class MeDataView extends Component
{
     use WithPagination;

    public $constituency;
    public $financialYear;
    public $quarter = 'all';
    public $search = '';
    public $perPage = 20;

    protected $queryString = [
        'financialYear' => ['except' => ''],
        'quarter' => ['except' => 'all'],
        'search' => ['except' => ''],
        'page' => ['except' => 1],
    ];

    public function mount(Constituency $constituency)
    {
        $this->constituency = $constituency;
        $this->financialYear = MeReporting::getCurrentFinancialYear();
    }

    public function getFinancialYears()
    {
        $currentYear = date('Y');
        $currentMonth = date('n');

        // Determine current financial year
        if ($currentMonth >= 7) {
            $currentFinancialYear = $currentYear . '-' . ($currentYear + 1);
        } else {
            $currentFinancialYear = ($currentYear - 1) . '-' . $currentYear;
        }

        $years = [];
        $startYear = 2014; // Starting from 2014-2015 financial year

        // Generate all financial years from 2014-2015 to current financial year
        for ($year = $startYear; $year <= $currentYear + 1; $year++) {
            $financialYear = $year . '-' . ($year + 1);

            // Only add years up to and including current financial year
            if ($financialYear <= $currentFinancialYear) {
                $years[] = $financialYear;
            }
        }

        // Reverse the array so most recent years appear first
        return array_reverse($years);
    }

    public function exportToExcel()
    {
        $filename = 'M&E_Data_' . $this->constituency->constituency_name . '_' . $this->financialYear;

        if ($this->quarter !== 'all') {
            $filename .= '_' . $this->quarter;
        }

        $filename .= '_' . date('Y-m-d') . '.xlsx';

        return Excel::download(
            new MeDataExport(
                $this->constituency->constituency_code,
                $this->financialYear,
                $this->quarter
            ),
            $filename
        );
    }

    public function getIndicatorsProperty()
    {
        $query = MeReporting::where('constituency_code', $this->constituency->constituency_code)
            ->where('financial_year', $this->financialYear);

        if ($this->search) {
            $query->where(function($q) {
                $q->where('data_required', 'like', '%' . $this->search . '%')
                  ->orWhere('remarks_comments', 'like', '%' . $this->search . '%');
            });
        }

        return $query->paginate($this->perPage);
    }

    public function getSummaryStatsProperty()
    {
        $indicators = MeReporting::where('constituency_code', $this->constituency->constituency_code)
            ->where('financial_year', $this->financialYear)
            ->get();

        $totalRecords = $indicators->count();
        $quartersCovered = $indicators->pluck('quarter')->unique()->count();
        $totalAnnualValue = $indicators->sum('annual_achieved');

        return [
            'total_records' => $totalRecords,
            'quarters_covered' => $quartersCovered,
            'total_annual_value' => $totalAnnualValue,
            'data_entries' => $indicators->groupBy('data_required')->count()
        ];
    }

    public function render()
    {
        return view('livewire.monitoring.me-data-view', [
            'indicators' => $this->indicators,
            'financialYears' => $this->getFinancialYears(),
            'summaryStats' => $this->summaryStats,
            'quarters' => [
                'all' => 'All Quarters',
                'Q1' => 'Q1 (July - September)',
                'Q2' => 'Q2 (October - December)',
                'Q3' => 'Q3 (January - March)',
                'Q4' => 'Q4 (April - June)'
            ]
        ]);
    }
}
