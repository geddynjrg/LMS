<?php
// app/Livewire/MeReporting.php

namespace App\Livewire;

use App\Models\MeReporting as MeIndicator;
use App\Models\Constituency;
use Livewire\Component;
use Livewire\WithPagination;

class MeReporting extends Component
{
    use WithPagination;

    public Constituency $constituency;
    public $financialYear;
    public $indicators = [];
    public $editingId = null;
    public $showForm = false;
    public $quarter = '';

    // Default indicators template with input types
    public $indicatorTemplate = [
        [
            'data_required' => 'Number & Amount disbursed to groups',
            'unit_of_measure' => 'Amount (Ksh) / No. of groups',
            'input_type' => 'dual_input'
        ],
        [
            'data_required' => 'Repayment status: Red (0–40%), Yellow (41–70%), Green (71–100%)',
            'unit_of_measure' => 'No. of groups per category',
            'input_type' => 'triple_input'
        ],
        [
            'data_required' => 'Number of groups/individuals trained',
            'unit_of_measure' => 'No. of groups / beneficiaries',
            'input_type' => 'dual_input'
        ],
        [
            'data_required' => 'Number of active groups practicing table banking',
            'unit_of_measure' => 'No. of groups',
            'input_type' => 'single_input'
        ],
        [
            'data_required' => 'No. of committee members trained',
            'unit_of_measure' => 'No. of members',
            'input_type' => 'single_input'
        ],
        [
            'data_required' => 'Number of trees planted & survived',
            'unit_of_measure' => 'No. of trees',
            'input_type' => 'single_input'
        ],
        [
            'data_required' => 'Groups linked to markets',
            'unit_of_measure' => 'No. of groups / events',
            'input_type' => 'dual_input'
        ],
        [
            'data_required' => 'Partnerships / collaborations created',
            'unit_of_measure' => 'No. of linkages',
            'input_type' => 'single_input'
        ],
        [
            'data_required' => 'Innovative approaches adopted',
            'unit_of_measure' => 'Description',
            'input_type' => 'text_area'
        ],
        [
            'data_required' => 'Beneficiaries mentored',
            'unit_of_measure' => 'No. of groups / individuals',
            'input_type' => 'dual_input'
        ],
        [
            'data_required' => 'Partnerships formalized',
            'unit_of_measure' => 'No. of partnerships',
            'input_type' => 'single_input'
        ],
        [
            'data_required' => 'Value of resources leveraged',
            'unit_of_measure' => 'Ksh / Type',
            'input_type' => 'dual_input'
        ],
        [
            'data_required' => 'No. of issues raised & resolved',
            'unit_of_measure' => 'No. of issues',
            'input_type' => 'single_input'
        ],
        [
            'data_required' => 'No. of engagements (forums, barazas, meetings)',
            'unit_of_measure' => 'No. of events',
            'input_type' => 'single_input'
        ],
        [
            'data_required' => 'No. of conflicts resolved',
            'unit_of_measure' => 'No. of conflicts',
            'input_type' => 'single_input'
        ],
        [
            'data_required' => 'Emerging issues, best practices, success stories, challenges',
            'unit_of_measure' => 'Narrative',
            'input_type' => 'text_area'
        ]
    ];

    protected $rules = [
        'indicators.*.q1_achieved' => 'nullable|numeric',
        'indicators.*.q2_achieved' => 'nullable|numeric',
        'indicators.*.q3_achieved' => 'nullable|numeric',
        'indicators.*.q4_achieved' => 'nullable|numeric',
        'indicators.*.annual_achieved' => 'nullable|numeric',
        'indicators.*.remarks_comments' => 'nullable|string|max:1000',
    ];

    public function mount(Constituency $constituency)
    {
        $this->constituency = $constituency;
        $this->financialYear = MeIndicator::getCurrentFinancialYear();
        $this->quarter = MeIndicator::getCurrentQuarter();
        $this->loadIndicators();
    }

    public function loadIndicators()
    {
        // Load existing data or initialize template
        $existingIndicators = MeIndicator::where('constituency_code', $this->constituency->constituency_code)
            ->where('financial_year', $this->financialYear)
            ->get()
            ->keyBy('data_required');

        $this->indicators = [];

        foreach ($this->indicatorTemplate as $template) {
            $dataRequired = $template['data_required'];

            if (isset($existingIndicators[$dataRequired])) {
                // Load existing data and preserve input_type
                $existingData = $existingIndicators[$dataRequired]->toArray();
                $this->indicators[$dataRequired] = array_merge($existingData, [
                    'input_type' => $template['input_type']
                ]);
            } else {
                // Initialize new indicator with input_type
                $this->indicators[$dataRequired] = [
                    'data_required' => $dataRequired,
                    'unit_of_measure' => $template['unit_of_measure'],
                    'input_type' => $template['input_type'],
                    'q1_achieved' => null,
                    'q2_achieved' => null,
                    'q3_achieved' => null,
                    'q4_achieved' => null,
                    'annual_achieved' => null,
                    'remarks_comments' => null,
                    'constituency_code' => $this->constituency->constituency_code,
                    'financial_year' => $this->financialYear,
                    'quarter' => $this->quarter
                ];
            }
        }
    }

    public function updatedFinancialYear()
    {
        $this->loadIndicators();
    }

    public function updatedQuarter()
    {
        $this->loadIndicators();
    }

    // public function saveIndicators()
    // {
    //     $this->validate();

    //     foreach ($this->indicators as $indicatorData) {
    //         // Remove input_type and id before saving to database
    //         $saveData = array_diff_key($indicatorData, [
    //             'input_type' => true,
    //             'id' => true, // Remove id to prevent update issues
    //             'created_at' => true,
    //             'updated_at' => true
    //         ]);

    //         // Only include fields that exist in the database
    //         $databaseFields = [
    //             'unit_of_measure',
    //             'q1_achieved',
    //             'q2_achieved',
    //             'q3_achieved',
    //             'q4_achieved',
    //             'annual_achieved',
    //             'remarks_comments',
    //             'constituency_code',
    //             'financial_year',
    //             'quarter'
    //         ];

    //         $filteredData = array_intersect_key($saveData, array_flip($databaseFields));

    //         MeIndicator::updateOrCreate(
    //             [
    //                 'constituency_code' => $this->constituency->constituency_code,
    //                 'financial_year' => $this->financialYear,
    //                 'quarter' => $this->quarter,
    //                 'data_required' => $indicatorData['data_required']
    //             ],
    //             $filteredData
    //         );
    //     }

    //     session()->flash('message', 'M&E data saved successfully!');
    //     $this->loadIndicators(); // Reload to get any calculated fields
    // }
    public function saveIndicators()
    {
        $this->validate();

        foreach ($this->indicators as $indicatorData) {
            // Create a clean data array with only database fields
            $saveData = [
                'unit_of_measure' => $indicatorData['unit_of_measure'],
                'q1_achieved' => $indicatorData['q1_achieved'],
                'q2_achieved' => $indicatorData['q2_achieved'],
                'q3_achieved' => $indicatorData['q3_achieved'],
                'q4_achieved' => $indicatorData['q4_achieved'],
                'annual_achieved' => $indicatorData['annual_achieved'],
                'remarks_comments' => $indicatorData['remarks_comments'],
            ];

            MeIndicator::updateOrCreate(
                [
                    'constituency_code' => $this->constituency->constituency_code,
                    'financial_year' => $this->financialYear,
                    'data_required' => $indicatorData['data_required']
                ],
                $saveData
            );
        }

        session()->flash('message', 'M&E data saved successfully!');
        $this->loadIndicators();
    }

    public function calculateAnnualTotal($indicatorKey)
    {
        // Only calculate for numeric input types
        if (in_array($this->indicators[$indicatorKey]['input_type'], ['single_input', 'dual_input', 'triple_input'])) {
            $q1 = $this->indicators[$indicatorKey]['q1_achieved'] ?? 0;
            $q2 = $this->indicators[$indicatorKey]['q2_achieved'] ?? 0;
            $q3 = $this->indicators[$indicatorKey]['q3_achieved'] ?? 0;
            $q4 = $this->indicators[$indicatorKey]['q4_achieved'] ?? 0;

            $this->indicators[$indicatorKey]['annual_achieved'] = $q1 + $q2 + $q3 + $q4;
        }
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

    public function render()
    {
        return view('livewire.monitoring.me-reporting', [
            'financialYears' => $this->getFinancialYears()
        ]);
    }
}
