<?php

namespace App\Services\Exports;


use App\Models\GroupLoan;
use App\Models\PhaseApplication;
use App\Exports\CodeListExport;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;
use ZipArchive;
use App\Models\LoanPhase;
use App\Models\TempApplication;

class ExportService
{
    /**
     * Constituency officer Generated Files → Code List.
     * Includes pending applications still under constituency review.
     */
    public function exportConstituencyCodeList(int $phaseId): Builder
    {
        if ($this->isHistoricalCodeListPhase($phaseId)) {
            return $this->historicalCodeListQuery($phaseId);
        }

        return $this->codeListBaseQuery($phaseId)
            ->whereNotIn('status', ['rejected', 'deferred']);
    }

    public function exportCodeList($phaseId, ?string $constituency = null): Builder
    {
        if ($this->isHistoricalCodeListPhase($phaseId)) {
            return $this->historicalCodeListQuery($phaseId);
        }

        return $this->codeListBaseQuery($phaseId)
            ->whereNotIn('status', ['rejected', 'deferred', 'pending']);
    }

    public function isHistoricalCodeListPhase(int $phaseId): bool
    {
        return LoanPhase::query()->findOrFail($phaseId)->isHistoricalUpload();
    }

    private function historicalCodeListQuery(int $phaseId): Builder
    {
        return GroupLoan::query()
            ->where('loan_phase_id', $phaseId)
            ->where('loan_status', 'financed')
            ->with([
                'group:id,name,identifier,code',
                'loanPhase:id,phase,constituency_code',
                'loanPhase.constituency:id,constituency_code,constituency_name,county_code',
                'loanPhase.constituency.county:id,county_name,county_code',
            ])
            ->orderBy('loan_level')
            ->orderBy('id');
    }

    private function codeListBaseQuery(int $phaseId): Builder
    {
        $query = PhaseApplication::query()
            ->where('loan_phase_id', $phaseId)
            ->select([
                'id',
                'loan_phase_id',
                'temp_application_id',
                'group_id',
                'group_code',
                'gfs_code',
                'amount_applied',
                'amount_approved',
                'status',
                'loan_level'
            ])
            ->with([
                'tempApplication' => function($q) {
                    $q->select([
                        'id',
                        'group_name',
                        'business_type',
                        'nature_of_business',
                        'loan_amount',
                    ]);
                },
                'linkedGroup' => function($q) {
                    $q->select(['id', 'name']);
                },
                'linkedGroup.currentLoan' => function($q) {
                    $q->select([
                        'group_loans.id',
                        'group_loans.group_id',
                        'group_loans.amount_issued',
                    ]);
                },
                'reviewPhase' => function($q) {
                    $q->select([
                        'id',
                        'constituency_code',
                        'phase'
                    ]);
                },
                'reviewPhase.constituency' => function($q) {
                    $q->select([
                        'id',
                        'constituency_code',
                        'county_code',
                        'constituency_name'
                    ]);
                },
                'reviewPhase.constituency.wards' => function($q) {
                    $q->select([
                        'id',
                        'constituency_code',
                        'ward_name'
                    ]);
                },
                'reviewPhase.constituency.county' => function($q) {
                    $q->select([
                        'id',
                        'county_name',
                        'county_code'
                    ]);
                }
            ])
            ->orderBy('loan_level', 'asc')
            ->orderBy('group_code', 'asc');

        return $query;
    }

    public function exportStandardVettedQuery($phaseId): Builder
    {
        $query = PhaseApplication::query()
            ->where('loan_phase_id', $phaseId)
            ->whereNotIn('status', ['rejected', 'deferred', 'pending'])
            ->select([
                'id',
                'loan_phase_id',
                'temp_application_id',
                'group_id',
                'amount_applied',
                'amount_approved',
                'loan_level',
                'group_code',
            ])->with([
                'reviewPhase' => function($q) {
                    $q->select([
                        'id',
                        'phase'
                    ]);
                },
                'tempApplication' => function($q) {
                    $q->select([
                        'id',
                        'group_name',
                        'business_type',
                        'nature_of_group',
                        'ward_id',
                        'constituency_code',
                    ]);
                },
                'tempApplication.constituency' => function($q) {
                    $q->select([
                        'id',
                        'constituency_code',
                        'county_code',
                        'constituency_name'
                    ]);
                },
                'tempApplication.constituency.county' => function($q) {
                    $q->select([
                        'id',
                        'county_name',
                        'county_code'
                    ]);
                },
                'tempApplication.ward' => function($q) {
                    $q->select([
                        'id',
                        'ward_name',
                    ]);
                },

            ])
            ->orderBy('loan_level', 'asc')
            ->orderBy('group_code', 'asc');

        return $query;
    }

    public function exportdisaggregatedDataQuery($phaseId): Builder
    {
        $query = PhaseApplication::query()
            ->where('loan_phase_id', $phaseId)
            ->whereNotIn('status', ['rejected', 'deferred', 'pending'])
            ->select([
                'id',
                'loan_phase_id',
                'temp_application_id',
                'loan_level',
                'group_code',
            ])->with([
                'reviewPhase' => function($q) {
                    $q->select([
                        'id',
                        'phase'
                    ]);
                },
                'tempApplication' => function($q) {
                    $q->select([
                        'id',
                        'group_name',
                        'nature_of_group',
                        'ward_id',
                        'constituency_code',
                    ])
                    ->withCount([
                        'groupMembers as male_count' => function ($query) {
                            $query->where('gender', 'male');
                        },
                        'groupMembers as female_count' => function ($query) {
                            $query->where('gender', 'female');
                        },
                        'groupMembers as pwd_male_count' => function ($query) {
                            $query->where('gender', 'male')->where('person_with_disability', 1);
                        },
                        'groupMembers as pwd_female_count' => function ($query) {
                            $query->where('gender', 'female')->where('person_with_disability', 1);
                        }
                    ]);
                },
                'tempApplication.constituency' => function($q) {
                    $q->select([
                        'id',
                        'constituency_code',
                        'county_code',
                        'constituency_name'
                    ]);
                },
                'tempApplication.constituency.county' => function($q) {
                    $q->select([
                        'id',
                        'county_name',
                        'county_code'
                    ]);
                },
                'tempApplication.ward' => function($q) {
                    $q->select([
                        'id',
                        'ward_name',
                    ]);
                },

            ])
            ->orderBy('loan_level', 'asc')
            ->orderBy('group_code', 'asc');

        return $query;
    }

    public function exportdisbursementDataQuery($phaseId): Builder
    {
        $query = PhaseApplication::query()
            ->where('loan_phase_id', $phaseId)
            ->whereNotIn('status', ['rejected', 'deferred', 'pending'])
            ->select([
                'id',
                'loan_phase_id',
                'temp_application_id',
                'loan_level',
                'group_code',
                'amount_approved',
            ])->with([
                'reviewPhase' => function($q) {
                    $q->select([
                        'id',
                        'phase'
                    ]);
                },
                'tempApplication' => function($q) {
                    $q->select([
                        'id',
                        'group_name',
                        'nature_of_group',
                        'ward_id',
                        'constituency_code',
                        'bank_name',
                        'bank_branch',
                        'bank_account_number',
                    ]);
                },
                'tempApplication.constituency' => function($q) {
                    $q->select([
                        'id',
                        'constituency_code',
                        'county_code',
                        'constituency_name'
                    ]);
                },
                'tempApplication.constituency.county' => function($q) {
                    $q->select([
                        'id',
                        'county_name',
                        'county_code'
                    ]);
                },
                'tempApplication.ward' => function($q) {
                    $q->select([
                        'id',
                        'ward_name',
                    ]);
                },

            ])
            ->orderBy('loan_level', 'asc')
            ->orderBy('group_code', 'asc');

        return $query;
    }


    public function downloadAllPhaseDocuments($phase)
    {
        if (!$phase instanceof LoanPhase) {
            $phase = LoanPhase::with(['phaseApplicationDocuments'])->findOrFail($phase);
        }


        $documents = $phase->phaseApplicationDocuments;

        if ($documents->isEmpty()) {
            //$dispatch('flash-error', message: 'No documents found for this phase.');
            return;
        }

        $zipFileName = 'Documents_' . str_replace('/', '-', $phase->slug) . '.zip';
        $tempDir = storage_path('app/public/temp');
        $zipPath = $tempDir . '/' . $zipFileName;

        if (!file_exists(storage_path('app/public/temp'))) {
            mkdir(storage_path('app/public/temp'), 0755, true);
        }

        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            foreach ($documents as $doc) {
                $filePath = storage_path('app/public/' . $doc->path);

                if (file_exists($filePath)) {
                    // We use the original_filename so the files inside the ZIP are readable
                    $zip->addFile($filePath, $doc->original_filename);
                }
            }
            $zip->close();
        }

        // Stream the download and delete the temp file after sending
        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

    public function exportInstitutionalInfoData($tempAppId)
    {
        $temp_app = TempApplication::with(['constituency', 'groupMembers', 'phaseApplications'])->findOrFail($tempAppId);

        dd($temp_app);
    }

}
