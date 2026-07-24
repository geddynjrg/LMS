<?php

namespace App\Services\Loan;

use App\Models\ActivityLog;
use App\Models\LoanPhase;
use App\Models\PhaseApplication;
use App\Models\TempApplication;
use App\Models\Group;
use App\Models\GroupLoan;
use App\Jobs\SendTrainingScheduleNotifications;
use App\Jobs\SyncApplicationStatusToDrupal;
use App\Services\IdempotencyService;
use App\Services\WorkflowNotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use App\Support\PhaseDocumentType;
use App\Models\Member;


class LoanPhaseService
{
    protected $applicationService;
    protected $analysisService;
    protected $idempotencyService;
    protected $systemVerificationService;

    /**
     * Limit HQ-facing queries to phases assigned to the officer or not yet assigned.
     */
    public static function applyHqOfficerVisibilityFilter($query, int $officerId, string $column = 'assigned_hq_officer_id'): void
    {
        $query->where(function ($q) use ($officerId, $column) {
            $q->where($column, $officerId)
                ->orWhereNull($column);
        });
    }

    // Laravel automatically resolves this for you
    public function __construct(
        TempApplicationService $applicationService,
        LoanAnalysisService $analysisService,
        IdempotencyService $idempotencyService,
        SystemVerificationService $systemVerificationService
        )
    {
        $this->applicationService = $applicationService;
        $this->analysisService =  $analysisService;
        $this->idempotencyService = $idempotencyService;
        $this->systemVerificationService = $systemVerificationService;
    }
    /**
     * Create a new loan review phase
     *
     * @param array $data Validated input data
     * @param string $constituencyCode
     * @return LoanPhase
     */
    public function createPhase(array $data, string $constituencyCode): LoanPhase
    {
        // Validate phase_name format
        $phaseName = trim($data['phaseName'] ?? '');

        if (!preg_match('/^phase\s+\d+$/i', $phaseName)) {
            throw ValidationException::withMessages([
                'phaseName' => 'Phase name must be in the format "Phase [positive number]", e.g. "Phase 3" or "phase 12".',
            ]);
        }

        // Extract the number part (for phase_number)
        preg_match('/\d+/', $phaseName, $matches);
        $phaseNumberFromName = (int) $matches[0];

        if ($phaseNumberFromName <= 0) {
            throw ValidationException::withMessages([
                'phaseName' => 'The phase number must be a positive integer.',
            ]);
        }

        // Check uniqueness (per constituency)
        if (LoanPhase::where('constituency_code', $constituencyCode)
            ->whereRaw('LOWER(phase) = ?', [strtolower($phaseName)])
            ->exists()) {
            throw ValidationException::withMessages([
                'phaseName' => 'This phase name already exists for this constituency.',
            ]);
        }

        $phaseCount = LoanPhase::where('constituency_code', $constituencyCode)->count();
        return DB::transaction(function () use ($phaseName, $data, $constituencyCode, $phaseCount) {
            $result = LoanPhase::create([
                'constituency_code'       => $constituencyCode,
                'phase'                  => $phaseName,
                'phase_name'             => $phaseName,
                'phase_number'           => 'Phase ' . ($phaseCount + 1) . ' - ' . now()->year,
                'review_date'            => $data['reviewDate'],
                'review_venue'           => $data['reviewVenue'] ?? null,
                'constituency_officer_id' => Auth::id() ?? null,
                'status'                 => 'draft',
            ]);

            Log::info('create results: ' . $result->id);

            return $result;
        });
    }

    /**
     * Assign one or multiple applications to a phase
     *
     * @param LoanPhase $phase
     * @param array|int $applicationIds Single ID or array of IDs
     * @throws ValidationException
     */
    public function assignApplications(LoanPhase $phase, $applicationIds): void
    {
        $errors = [];

        if (!is_array($applicationIds)) {
            $applicationIds = [$applicationIds];
        }

        if (!$phase->canAddApplications()) {
            throw ValidationException::withMessages([
                'phase' => "This phase is not accepting new applications (current status: {$phase->status}).",
            ]);
        }

        DB::transaction(function () use ($phase, $applicationIds, &$errors) {
            $newApps = TempApplication::whereIn('id', $applicationIds)
                ->select(['id', 'group_name', 'loan_amount', 'group_registration_number', 'application_id'])
                ->get();

            $existingRegNumbers = TempApplication::whereHas('phaseApplication', function ($query) use ($phase) {
                    $query->where('loan_phase_id', $phase->id);
                })
                ->pluck('group_registration_number')
                ->filter()
                ->toArray();

            $dataToInsert = [];
            $now = now();

            foreach ($newApps as $app) {
                // Check for duplicate group registration in this phase
                if (in_array($app->group_registration_number, $existingRegNumbers)) {
                    $errors[] = "This application with Reg: no. {$app->group_registration_number} is already in {$phase->phase}";
                    continue;
                }

                $dataToInsert[] = [
                    'loan_phase_id'       => $phase->id,
                    'temp_application_id' => $app->id,
                    'amount_applied'      => $app->loan_amount,
                    'status'  => 'pending',
                    'created_at'          => $now,
                    'updated_at'          => $now,
                ];

                // Add to tracker so we don't allow duplicates within the same selection batch
                $existingRegNumbers[] = $app->group_registration_number;
            }

            if (!empty($dataToInsert)) {
                PhaseApplication::insert($dataToInsert);

                $insertedIds = collect($newApps)->pluck('application_id')->toArray();
                $insertedTempIds = collect($dataToInsert)->pluck('temp_application_id')->all();

                $this->invalidateMultipleApplicationsCache($insertedIds);

                activity('phase-assignment')
                    ->performedOn($phase)
                    ->withProperties(['added' => count($dataToInsert), 'errors' => $errors])
                    ->log("Assigned applications with " . count($errors) . " skips.");

                $verificationOutcomes = $this->systemVerificationService
                    ->autoVerifyAssignedApplications($insertedTempIds);

                foreach ($insertedIds as $applicationId) {
                    $this->applicationService->invalidateApplicationCache($applicationId);
                }

                if (! empty($verificationOutcomes)) {
                    activity('phase-assignment')
                        ->performedOn($phase)
                        ->withProperties(['verification' => $verificationOutcomes])
                        ->log('Auto system verification completed for newly assigned applications.');
                }
            }
        });

        if (!empty($errors)) {
            throw ValidationException::withMessages([
                'applications' => $errors,
            ]);
        }
    }

    /**
     * Remove an application from a phase
     *
     * @param LoanPhase $phase
     * @param int $applicationId
     * @throws ValidationException
     */
    public function removeApplication(LoanPhase $phase, int $applicationId): void
    {
        if ($phase->status !== 'draft') {
            throw ValidationException::withMessages([
                'phase' => 'Applications can only be removed from phases in "draft" status.',
            ]);
        }

        $phaseApplication = PhaseApplication::where('loan_phase_id', $phase->id)
            ->where('temp_application_id', $applicationId)
            ->first();

        if (!$phaseApplication) {
            return; // already not assigned
        }

        $app = DB::transaction(function () use ($phaseApplication, $phase) {
            $phaseApplication->delete();
            $phase->calculateStats();

            $app = TempApplication::findOrFail($phaseApplication->temp_application_id);

            if ($app) {
                $app->update([
                    'link_status' => 'not_attempted',
                ]);

                return $app;
            }


        });

        if ($app) $this->applicationService->invalidateApplicationCache($app->application_id);


    }

    /**
     * Bulk assign multiple applications (convenience wrapper)
     */
    public function bulkAssignApplications(LoanPhase $phase, array $applicationIds): void
    {
        $this->assignApplications($phase, $applicationIds);
    }

    /**
     * Mark phase as in_review (after finalizing draft)
     */
    public function startReview(LoanPhase $phase): void
    {
        if ($phase->status !== 'draft') {
            throw ValidationException::withMessages([
                'status' => 'Only draft phases can be moved to in_review.',
            ]);
        }

        if ($phase->phaseApplications()->count() === 0) {
            throw ValidationException::withMessages([
                'applications' => 'Cannot start review without any applications assigned.',
            ]);
        }

        $phase->update(['status' => 'in_review']);
    }

    /**
     * Get phases with useful eager-loaded relations
     */
    public function getActivePhasesForConstituency(string $constituencyCode)
    {
        return LoanPhase::where('constituency_code', $constituencyCode)
            ->whereIn('status', ['draft', 'in_review'])
            ->withCount('phaseApplications')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getPhasesForConstituencyByStatus(string $constituencyCode, $status)
    {
        return LoanPhase::where('constituency_code', $constituencyCode)
            ->where('status', $status)
            ->select('id', 'phase', 'status', 'slug')
            ->orderBy('phase')
            ->get();
    }

    public function getAllPhasesByStatus(string $status)
    {
        return LoanPhase::where('status', $status)
            ->select('id', 'phase', 'status', 'slug')
            ->orderBy('phase')
            ->get();
    }

    public function getAllPhasesForConstituency(string $constituencyCode)
    {
        return LoanPhase::where('constituency_code', $constituencyCode)
            ->select('id', 'phase', 'status', 'slug')
            ->orderBy('created_at', 'desc')
            ->get();
    }


    public function getDetailedPhases(string $constituencyCode = '', ?string $search = null, int $perPage = 15, ?string $status = 'draft', int $page = 1)
    {
        $phasesTag = config('cache.tags.phases.constituency_detailed') . $constituencyCode;

        if ($constituencyCode === ''){
           $query = LoanPhase::where('status', $status)
            ->withCount([
                'phaseApplications as total_apps',
                'phaseApplications as pending_apps' => fn($q) => $q->where('status', 'pending'),
                'phaseApplications as approved_apps' => fn($q) => $q->where('status', 'approved'),
                'phaseApplications as rejected_apps' => fn($q) => $q->where('status', 'rejected'),
            ])
            // Replace DB::raw with these for better readability and performance
            ->withSum('phaseApplications as total_requested', 'amount_applied')
            ->withSum(['phaseApplications as total_approved' => fn($q) => $q->where('status', '!=', 'pending')], 'amount_approved')
            ->when(filled($search), function($q) use ($search) {
                    $searchTerm = '%' . $search . '%';
                    $q->where(function($sub) use ($searchTerm) {
                        $sub->where('phase', 'like', $searchTerm)
                            ->orWhere('id', 'like', $searchTerm);
                    });
                })
            ->orderBy('created_at', 'desc');
        } else {
        $query = LoanPhase::where('constituency_code', $constituencyCode)
            ->where('status', $status)
            ->withCount([
                'phaseApplications as total_apps',
                'phaseApplications as pending_apps' => fn($q) => $q->where('status', 'pending'),
                'phaseApplications as approved_apps' => fn($q) => $q->where('status', 'approved'),
                'phaseApplications as rejected_apps' => fn($q) => $q->where('status', 'rejected'),
            ])
            // Replace DB::raw with these for better readability and performance
            ->withSum('phaseApplications as total_requested', 'amount_applied')
            ->withSum(['phaseApplications as total_approved' => fn($q) => $q->where('status', '!=', 'pending')], 'amount_approved')
            ->when(filled($search), function($q) use ($search) {
                    $searchTerm = '%' . $search . '%';
                    $q->where(function($sub) use ($searchTerm) {
                        $sub->where('phase', 'like', $searchTerm)
                            ->orWhere('id', 'like', $searchTerm);
                    });
                })
            ->orderBy('created_at', 'desc');
        }
        // if (!filled($search)) {
        //
        //     Log::info($query->toSql(), $query->getBindings());
        // }

        // Only cache when NO search term
        if (!filled($search)) {
            $cacheKey = "phases_detailed_{$constituencyCode}_{$status}_p{$page}_limit{$perPage}";

            return Cache::tags([$phasesTag])->remember($cacheKey, now()->addMinutes(15), function() use ($query, $perPage) {
                return $query->paginate($perPage);
            });
        }

        return $query->paginate($perPage);
    }

    public function getDetailedPhasesQuery(?string $constituencyCode = '', array $statuses, array $filters = [], ?string $sortField, ?string $sortDirection): Builder
    {
        $query = LoanPhase::query()
            ->select([
                'loan_phases.id',
                'loan_phases.constituency_code',
                'loan_phases.phase',
                'loan_phases.status',
                'loan_phases.submitted_to_hq_at',
                'loan_phases.slug',
                'loan_phases.review_date',
                'loan_phases.hq_approved_at',
                'constituencies.constituency_name',
                'counties.county_name',
                'counties.county_code',
                ...\App\Support\LoanPhaseFinancialTotals::selectExpressions(),
            ])
            ->leftJoin('constituencies', 'loan_phases.constituency_code', '=', 'constituencies.constituency_code')
            ->leftJoin('counties', 'constituencies.county_code', '=', 'counties.county_code')
            ->with(['constituency.county'])
            ->whereIn('loan_phases.status', $statuses);

        if(!empty($constituencyCode)) $query->where('loan_phases.constituency_code', $constituencyCode);

        if ($search = $filters['search'] ?? null) {
            $searchTerm = '%' . $search . '%';
            $query->where(function($sub) use ($searchTerm) {
                $sub->where('loan_phases.phase', 'like', $searchTerm)
                    ->orWhere('loan_phases.id', 'like', $searchTerm);
            });
        }

        if ($status = $filters['status'] ?? null) {
            $query->where('loan_phases.status', $status);
        }

        if ($constituencyCode = $filters['constituency_code'] ?? null) {
            $query->where('loan_phases.constituency_code', $constituencyCode);
        }

        if ($countyCode = $filters['county_code'] ?? null) {
            $query->where('counties.county_code', $countyCode);
        }

        if ($sortField && $sortDirection) {
            $this->applyOptimizedSorting($constituencyCode, $query, $sortField, $sortDirection);
        } else {
            $query->orderBy('loan_phases.submitted_to_hq_at', 'desc');
        }

        return $query;
    }

    protected function applyOptimizedSorting(?string $constituencyCode, Builder $query, ?string $sortField, ?string $sortDirection): void
    {
        if ($sortField && $sortDirection) {
            $sortMap = [
                'county_code' => 'counties.county_name',
                'status' => 'loan_phases.status',
                'phase' => 'loan_phases.phase',
                'submission' => 'loan_phases.submitted_to_hq_at',
                'applied' => 'total_requested',
                'approved' => 'total_approved',
                'review_date' => 'loan_phases.review_date',
                'approved_date' => 'loan_phases.hq_approved_at',
            ];

            if (array_key_exists($sortField, $sortMap)) {
                $column = $sortMap[$sortField];

                if (in_array($sortField, ['applied', 'approved', 'issued'])) {
                    $query->orderBy(DB::raw($column), $sortDirection);
                } else {
                    $query->orderBy($column, $sortDirection);
                }
                return;
            }
        }

        $this->applyDefaultSorting($constituencyCode, $query);
    }

    protected function applyDefaultSorting(?string $constituencyCode, Builder $query): void
    {
        if (!empty($constituencyCode)) {
            $query->orderBy('loan_phases.updated_at', 'desc');
        } else {
            $query->orderBy('loan_phases.submitted_to_hq_at', 'desc');
        }

        $query->orderBy('loan_phases.id', 'desc');
    }

    public function getDetailedPhase($phase, int $perPage = 15, array $filters = [], int $page = 1, ?string $sortField, ?string $sortDirection): array
    {
        $phaseId = ($phase instanceof LoanPhase) ? $phase->id : $phase;

        $phaseTag = config('cache.tags.phases.individual_detailed') . $phaseId;

        // if (collect($filters)->filter()->isEmpty()) {
        //     $cacheKey = "phase_details_page_{$page}_{$sortField}_{$sortDirection}";
        //     return Cache::tags([$phaseTag])->remember($cacheKey, now()->addMinutes(15), function () use ($phase, $perPage, $filters, $sortField, $sortDirection) {
        //         return $this->fetchPhaseFromDb($phase, $perPage, $filters, $sortField, $sortDirection);
        //     });
        // }

        return $this->fetchPhaseFromDb($phase, $perPage, $filters, $sortField, $sortDirection);
    }

    public function fetchPhaseFromDb($phase, int $perPage = 15, array $filters = [], ?string $sortField, ?string $sortDirection) : array
    {
        $approvedStatuses = ['pending_submission_to_hq', 'pending_hq_approval', 'in_training', 'approved'];
        if (!$phase instanceof LoanPhase) {
            $phase = LoanPhase::findOrFail($phase);
        }

        if ($phase->isHistoricalUpload()) {
            return $this->fetchHistoricalPhaseFromDb($phase, $perPage, $filters, $sortField, $sortDirection);
        }

        $stats = $phase->phaseApplications()
            ->join('temp_applications', 'phase_applications.temp_application_id', '=', 'temp_applications.id')
            ->selectRaw("
                COUNT(*) as total_applications,
                SUM(CASE WHEN phase_applications.status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN phase_applications.status = 'pending_submission_to_hq' THEN 1 ELSE 0 END) as pending_submission_to_hq_count,
                SUM(CASE WHEN phase_applications.status = 'pending_hq_approval' THEN 1 ELSE 0 END) as pending_hq_approval_count,
                SUM(CASE WHEN phase_applications.status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
                SUM(CASE WHEN phase_applications.status = 'approved' THEN 1 ELSE 0 END) as approved_count,
                SUM(CASE WHEN phase_applications.status = 'disbursed' THEN 1 ELSE 0 END) as disbursed_count,

                /* Nature of group counts - using single pass check */
                SUM(CASE WHEN temp_applications.nature_of_group LIKE '%youth%' THEN 1 ELSE 0 END) as youth_count,
                SUM(CASE WHEN temp_applications.nature_of_group LIKE '%women%' THEN 1 ELSE 0 END) as women_count,
                SUM(CASE WHEN temp_applications.nature_of_group LIKE '%pwd%' THEN 1 ELSE 0 END) as pwd_count,

                /* Combined Status + Nature counts */
                SUM(CASE WHEN phase_applications.status IN ('" . implode("','", $approvedStatuses) . "') AND temp_applications.nature_of_group LIKE '%youth%' THEN 1 ELSE 0 END) as approved_youth_count,
                SUM(CASE WHEN phase_applications.status IN ('" . implode("','", $approvedStatuses) . "') AND temp_applications.nature_of_group LIKE '%women%' THEN 1 ELSE 0 END) as approved_women_count,
                SUM(CASE WHEN phase_applications.status IN ('" . implode("','", $approvedStatuses) . "') AND temp_applications.nature_of_group LIKE '%pwd%' THEN 1 ELSE 0 END) as approved_pwd_count,

                /* Disbursed Status + Nature counts */
                SUM(CASE WHEN phase_applications.status = 'disbursed' AND temp_applications.nature_of_group LIKE '%youth%' THEN 1 ELSE 0 END) as disbursed_youth_count,
                SUM(CASE WHEN phase_applications.status = 'disbursed' AND temp_applications.nature_of_group LIKE '%women%' THEN 1 ELSE 0 END) as disbursed_women_count,
                SUM(CASE WHEN phase_applications.status = 'disbursed' AND temp_applications.nature_of_group LIKE '%pwd%' THEN 1 ELSE 0 END) as disbursed_pwd_count,

                /* Financial Totals */
                SUM(temp_applications.loan_amount) as total_requested,
                SUM(CASE WHEN phase_applications.status IN ('" . implode("','", $approvedStatuses) . "') THEN phase_applications.amount_approved ELSE 0 END) as total_approved,
                SUM(CASE WHEN phase_applications.status = 'disbursed' THEN phase_applications.amount_issued ELSE 0 END) as total_issued
            ")
            ->first();

        $phase->forceFill($stats->toArray());

        $phase->load([
            'phaseApplicationDocuments' => fn($q) => $q->select('id', 'loan_phase_id', 'title', 'document_type', 'path', 'uploaded_by', 'created_at')->latest(),
            'constituencyOfficer:id,name,email,phone_number',
            'hqOfficer:id,name,email,phone_number'
        ]);

        $phase->has_minutes = $phase->phaseApplicationDocuments->contains('document_type', PhaseDocumentType::MINUTES);
        $bankReconService = app(BankReconciliationService::class);
        $phase->has_bank_reconciliation_main = $bankReconService->hasMainAccountReconciliation($phase);
        $phase->has_bank_reconciliation_repayment = $bankReconService->hasLoanRepaymentReconciliation($phase);
        $phase->has_bank_reconciliation = $bankReconService->hasAllRequiredReconciliations($phase);
        $phase->has_request_for_transfer_letter = $phase->phaseApplicationDocuments->contains('document_type', PhaseDocumentType::REQUEST_FOR_TRANSFER_LETTER);
        $phase->has_processed_code_list = $phase->phaseApplicationDocuments
            ->pluck('document_type')
            ->intersect(PhaseDocumentType::processedCodeListTypes())
            ->isNotEmpty();
        $phase->has_approved_code_list = $phase->has_processed_code_list;
        $phase->has_management_proposal_minutes = $phase->phaseApplicationDocuments->contains('document_type', PhaseDocumentType::MANAGEMENT_PROPOSAL_REVIEW_MINUTES);
        $phase->has_signed_approved_code_list = $phase->phaseApplicationDocuments->contains('document_type', PhaseDocumentType::SIGNED_APPROVED_CODE_LIST);

        $applicationsQuery = $phase->phaseApplications()
            ->select([
                'phase_applications.id',
                'phase_applications.loan_phase_id',
                'phase_applications.temp_application_id',
                'phase_applications.status',
                'phase_applications.gfs_code',
                'phase_applications.loan_level',
                'phase_applications.amount_approved',
                'phase_applications.amount_issued',
                'phase_applications.updated_at',
                'phase_applications.is_trained',
            ])
            ->join('temp_applications', 'phase_applications.temp_application_id', '=', 'temp_applications.id')
            ->with(['tempApplication' => function($q) {
                $q->select(
                    'id',
                    'application_id',
                    'group_name',
                    'group_registration_number',
                    'ward_id',
                    'loan_amount',
                    'risk_level',
                    'nature_of_group',
                    'business_type',
                    'loan_type'
                )
                ->with('ward:id,ward_name');
            }]);

        $applicationsQuery->when($filters['integrity'] ?? null, function($q, $integrity) use ($phase){
            if($integrity === 'is_duplicate') {
                return $q->whereIn('temp_applications.group_registration_number', function($sub) use ($phase) {
                    $sub->select('ta.group_registration_number')
                        ->from('temp_applications as ta')
                        ->join('phase_applications as pa', 'pa.temp_application_id', '=', 'ta.id')
                        ->where('pa.loan_phase_id', '!=', $phase->id)
                        ->whereIn('pa.status', ['pending_hq_approval', 'approved', 'in_training'])
                        ->groupBy('ta.group_registration_number');
                    });
            }

            if ($integrity === 'is_defaulter') {
                return $q->whereIn('temp_applications.group_registration_number', function($sub) {
                    $sub->select('groups.reg_number')
                        ->from('group_loans')
                        ->join('groups', 'group_loans.group_id', '=', 'groups.id')
                        ->whereIn('group_loans.loan_status', ['financed'])
                        ->where(function($query) {
                            $query->where('group_loans.is_defaulted', 1)
                                ->orWhere('group_loans.total_arrears', '>', 0)
                                ->orWhereRaw('(group_loans.amount_issued - group_loans.amount_repaid) > 0');
                        });
                });
            }
        })
        ->when($filters['search'] ?? null, function($q, $search) {
            $q->where(fn($sub) => $sub->where('temp_applications.group_name', 'like', "%{$search}%")
                ->orWhere('temp_applications.application_id', 'like', "%{$search}%"));
        })
        ->when($filters['risk'] ?? null, fn($q, $risk) => $q->where('temp_applications.risk_level', 'like', "%{$risk}%"))
        ->when($filters['status'] ?? null, fn($q, $status) => $q->where('phase_applications.status', $status));

        $sortMap = [
            'applicationId'   => 'temp_applications.application_id',
            'group_name'      => 'temp_applications.group_name',
            'category'        => 'temp_applications.nature_of_group',
            'amount_applied'  => 'temp_applications.loan_amount',
            'amount_approved' => 'phase_applications.amount_approved',
            'status'          => 'phase_applications.status',
        ];

        $orderBy = $sortMap[$sortField] ?? 'phase_applications.updated_at';
        $applications = $applicationsQuery->orderBy($orderBy, $sortDirection ?? 'desc')->paginate($perPage);

        $regNumbers = $applications->getCollection()->pluck('tempApplication.group_registration_number')->filter()->toArray();
        $validation = $this->analysisService->performGlobalCheck($regNumbers, $phase->id);

        // dd($validation);
        $applications->getCollection()->transform(function ($app) use ($validation) {
            $regNo = $app->tempApplication->group_registration_number;
            $app->is_duplicate = $validation['current']->has($regNo);

            $app->duplicate_info = $app->is_duplicate ? $validation['current']->get($regNo)->first() : null;

            if ($validation['legacy']->has($regNo)) {
                $loans = $validation['legacy']->get($regNo);

                $app->legacy_debt_balance = $loans->sum('outstanding_balance');

                // 2. Get a list of all constituencies involved (if they owe in multiple places)
                // $app->legacy_debt_constituency = $loans->pluck('constituency_name')->unique()->implode(', ');

                // 3. Keep the most recent group name for reference
                // $app->legacy_group_name = $loans->first()->group_name;

                // 4. Mark as defaulter if ANY loan has a balance > 0
                $app->is_defaulter = ($app->legacy_debt_balance > 0);

                // 5. Total count of loans they have had in the past
                // $app->legacy_loan_count = $loans->count();
            }

            return $app;
        });

        return [
            'phase' => $phase,
            'applications' => $applications,
            'is_historical_upload' => false,
        ];
    }

    /**
     * Historical upload phases store beneficiaries as group_loans (no phase_applications).
     */
    protected function fetchHistoricalPhaseFromDb(
        LoanPhase $phase,
        int $perPage,
        array $filters,
        ?string $sortField,
        ?string $sortDirection
    ): array {
        $stats = $phase->grouploans()
            ->whereIn('loan_status', ['financed', 'cancelled'])
            ->selectRaw("
                COUNT(*) as total_applications,
                0 as pending_count,
                0 as pending_submission_to_hq_count,
                0 as pending_hq_approval_count,
                SUM(CASE WHEN loan_status = 'cancelled' THEN 1 ELSE 0 END) as rejected_count,
                SUM(CASE WHEN loan_status = 'financed' THEN 1 ELSE 0 END) as approved_count,
                SUM(CASE WHEN loan_status = 'financed' THEN 1 ELSE 0 END) as disbursed_count,
                SUM(CASE WHEN LOWER(category) LIKE '%youth%' THEN 1 ELSE 0 END) as youth_count,
                SUM(CASE WHEN LOWER(category) LIKE '%women%' THEN 1 ELSE 0 END) as women_count,
                SUM(CASE WHEN LOWER(category) LIKE '%pwd%' THEN 1 ELSE 0 END) as pwd_count,
                SUM(CASE WHEN loan_status = 'financed' AND LOWER(category) LIKE '%youth%' THEN 1 ELSE 0 END) as approved_youth_count,
                SUM(CASE WHEN loan_status = 'financed' AND LOWER(category) LIKE '%women%' THEN 1 ELSE 0 END) as approved_women_count,
                SUM(CASE WHEN loan_status = 'financed' AND LOWER(category) LIKE '%pwd%' THEN 1 ELSE 0 END) as approved_pwd_count,
                SUM(CASE WHEN loan_status = 'financed' AND LOWER(category) LIKE '%youth%' THEN 1 ELSE 0 END) as disbursed_youth_count,
                SUM(CASE WHEN loan_status = 'financed' AND LOWER(category) LIKE '%women%' THEN 1 ELSE 0 END) as disbursed_women_count,
                SUM(CASE WHEN loan_status = 'financed' AND LOWER(category) LIKE '%pwd%' THEN 1 ELSE 0 END) as disbursed_pwd_count,
                COALESCE(SUM(amount_applied), 0) as total_requested,
                COALESCE(SUM(CASE WHEN loan_status = 'financed' THEN amount_approved ELSE 0 END), 0) as total_approved,
                COALESCE(SUM(CASE WHEN loan_status = 'financed' THEN amount_issued ELSE 0 END), 0) as total_issued
            ")
            ->first();

        $phase->forceFill($stats?->toArray() ?? []);

        $query = DB::table('group_loans as gl')
            ->join('groups as g', 'gl.group_id', '=', 'g.id')
            ->join('wards as w', 'g.ward_id', '=', 'w.id')
            ->where('gl.loan_phase_id', $phase->id)
            ->whereIn('gl.loan_status', ['financed', 'cancelled'])
            ->select([
                'gl.id',
                'gl.loan_phase_id',
                'gl.group_id',
                'gl.amount_applied',
                'gl.amount_approved',
                'gl.amount_issued',
                'gl.amount_repaid',
                'gl.loan_status',
                'gl.loan_level',
                'gl.category',
                'gl.sector',
                'gl.gfs_code',
                'gl.status',
                'gl.disbursement_date',
                'gl.updated_at',
                'g.name as group_name',
                'g.slug as group_slug',
                'g.identifier as group_identifier',
                'w.ward_name',
                DB::raw('(gl.amount_issued - gl.amount_repaid) as balance'),
            ]);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('g.name', 'like', "%{$search}%")
                    ->orWhere('g.identifier', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('gl.status', $filters['status']);
        }

        if (!empty($filters['risk'])) {
            $query->where('gl.category', 'like', "%{$filters['risk']}%");
        }

        $sortMap = [
            'applicationId' => 'g.identifier',
            'group_name' => 'g.name',
            'category' => 'gl.category',
            'amount_applied' => 'gl.amount_applied',
            'amount_approved' => 'gl.amount_approved',
            'status' => 'gl.status',
            'updated' => 'gl.updated_at',
            'disbursement_date' => 'gl.disbursement_date',
            'issued' => 'gl.amount_issued',
            'repaid' => 'gl.amount_repaid',
            'balance' => 'balance',
            'ward' => 'w.ward_name',
        ];

        $orderBy = $sortMap[$sortField] ?? 'gl.disbursement_date';
        $applications = $query->orderBy($orderBy, $sortDirection ?? 'desc')->paginate($perPage);

        return [
            'phase' => $phase,
            'applications' => $applications,
            'is_historical_upload' => true,
        ];
    }

    public function getPhasesByStatus(string $constituencyCode, ?string $search = null, int $perPage = 15)
    {
        return LoanPhase::where('constituency_code', $constituencyCode)
            ->withCount([
                'phaseApplications as total_apps',
                'phaseApplications as pending_apps' => fn($q) => $q->where('status', 'pending'),
                'phaseApplications as approved_apps' => fn($q) => $q->where('status', 'approved'),
                'phaseApplications as rejected_apps' => fn($q) => $q->where('status', 'rejected'),
            ])
            ->withSum('phaseApplications as total_requested', 'amount_applied')
            ->withSum('phaseApplications as total_approved', 'amount_approved')
            // Get the latest vetting activity date
            ->withMax('phaseApplications as last_vetting_at', 'updated_at')
            ->when($search, function ($q) use ($search) {
                $q->where('phase_name', 'like', "%{$search}%");
            })
            ->orderBy('last_vetting_at', 'desc')
            ->get()
            ->groupBy('status');
    }

    public function getPhasesSummary(string $constituencyCode = ''): array
    {
        if (empty($constituencyCode)) {
            $summaryTag = config('cache.tags.phases.national', 'hq_');
            $cacheKey = "phases_summary_national";
        } else {
            $summaryTag = config('cache.tags.phases.constituency_summary', 'constituency_summary_') . $constituencyCode;
            $cacheKey = "phases_summary_{$constituencyCode}";
        }

        $query = LoanPhase::query()
                ->select([
                    DB::raw('COUNT(*) as total_phases'),
                    DB::raw("SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as pending_submission"),
                    DB::raw("SUM(CASE WHEN status IN ('submitted_to_hq', 'approved', 'in_training') THEN 1 ELSE 0 END) as processing_hq"),
                    DB::raw("SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_ready"),
                    DB::raw("SUM(CASE WHEN status = 'in_training' THEN 1 ELSE 0 END) as in_training"),
                    DB::raw("SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as declined"),
                    DB::raw("SUM(CASE WHEN status = 'disbursed' THEN 1 ELSE 0 END) as disbursed"),
                    DB::raw("SUM(CASE WHEN status IN ('disbursed', 'rejected') THEN 1 ELSE 0 END) as completed_phases"),
                ]);

            if (!empty($constituencyCode)) {
                $query->where('constituency_code', $constituencyCode);
            }

            $result = $query->first();

            return [
                'total_phases' => $result->total_phases,
                'pending_submission' => $result->pending_submission,
                'processing_hq' => $result->processing_hq,
                'approved_ready' => $result->approved_ready,
                'in_training' => $result->in_training,
                'declined' => $result->declined,
                'disbursed' => $result->disbursed,
                'completed_phases' => $result->completed_phases,
            ];

        // return Cache::tags([$summaryTag])->remember($cacheKey, now()->addMinutes(10), function () use ($constituencyCode) {
        //     $query = LoanPhase::query()
        //         ->select([
        //             DB::raw('COUNT(*) as total_phases'),
        //             DB::raw("SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as pending_submission"),
        //             DB::raw("SUM(CASE WHEN status = 'submitted_to_hq' THEN 1 ELSE 0 END) as processing_hq"),
        //             DB::raw("SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_ready"),
        //             DB::raw("SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as declined"),
        //             DB::raw("SUM(CASE WHEN status = 'disbursed' THEN 1 ELSE 0 END) as disbursed"),
        //         ]);

        //     if (!empty($constituencyCode)) {
        //         $query->where('constituency_code', $constituencyCode);
        //     }

        //     $result = $query->first();

        //     return [
        //         'total_phases' => $result->total_phases,
        //         'pending_submission' => $result->pending_submission,
        //         'processing_hq' => $result->processing_hq,
        //         'approved_ready' => $result->approved_ready,
        //         'declined' => $result->declined,
        //         'disbursed' => $result->disbursed,
        //     ];
        // });
    }

    // get phase by id
    public function getPhase($id) : LoanPhase
    {
        return LoanPhase::findOrFail($id);
    }

    public function getVettingPercentage($total_apps, $pending): int
    {
        $total = $total_apps;

        if ($total === 0) {
            return 0;
        }

        $vettedCount = $total_apps - $pending;

        return (int) round(($vettedCount / $total) * 100);
    }

    public function invalidateCodeListReportCache(string $constituencyCode): void
    {
        Cache::forget("code_list_report_{$constituencyCode}");
    }

    public function invalidatePhasesCache(string $constituencyCode): void
    {
        $phases_detailed = config('cache.tags.phases.constituency_detailed') . $constituencyCode;
        $phases_summary = config('cache.tags.phases.constituency_summary') . $constituencyCode;

        // Clear all cache entries associated with this tags
        Cache::tags([$phases_detailed])->flush();
        Cache::tags([$phases_summary])->flush();
        $this->invalidateCodeListReportCache($constituencyCode);
    }

    public function uploadPhaseTypedDocument($phase, $uploadDocumentFile, $filePayload) : void
    {
        if (!$phase instanceof LoanPhase) {
            $phase = LoanPhase::with(['phaseApplicationDocuments'])->findOrFail($phase);
        }

        if (empty($filePayload)){
            throw ValidationException::withMessages([
                $filePayload['modal'] => 'The file payload is empty.',
            ]);
        }

        if ($uploadDocumentFile->getSize() > 2 * 1024 * 1024) {
            throw ValidationException::withMessages([
                $filePayload['modal'] => 'The file must not exceed 2MB in size.',
            ]);
        }

        $directory = "phases/{$phase->slug}";
        $extension = strtolower($uploadDocumentFile->getClientOriginalExtension() ?: '');
        if ($extension === '') {
            $extension = match ($uploadDocumentFile->getMimeType()) {
                'application/pdf' => 'pdf',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
                'application/vnd.ms-excel' => 'xls',
                'application/msword' => 'doc',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                default => 'bin',
            };
        }

        $filename = $filePayload['fileName'] ?? 'document-' . time();
        if (!str_contains($filename, '.')) {
            $filename = rtrim($filename, '-_') . '-' . time() . '.' . $extension;
        }

        $path = $uploadDocumentFile->storeAs($directory, $filename, 'local');
        $this->ensurePhaseDocumentPermissions($path);

        try {
            DB::beginTransaction();

            $existingMinutes = $phase->phaseApplicationDocuments()
                ->where('document_type', $filePayload['documentType'])
                ->first();

            if ($existingMinutes) {
                Storage::disk('local')->delete($existingMinutes->path);
                $existingMinutes->delete();
            }
            $phase->phaseApplicationDocuments()->create([
                'loan_phase_id'     => $phase->id,
                'title'             => $filePayload['title'] ?? 'Added File',
                'document_type'     => $filePayload['documentType'] ?? 'added',
                'original_filename' => $uploadDocumentFile->getClientOriginalName(),
                'filename'          => $filename,
                'path'              => $path,
                'mime_type'         => $uploadDocumentFile->getMimeType(),
                'size'              => $uploadDocumentFile->getSize(),
                'statement_period'  => $filePayload['statementPeriod'] ?? null,
                'uploaded_by'       => auth()->id(),
            ]);

            DB::commit();

        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error("Failed to upload phase minutes: " . $th->getMessage());

            if (Storage::disk('local')->exists($path)) {
                Storage::disk('local')->delete($path);
            }

            throw $th;
        }
    }

    public function storeProcessedCodeListDocument(LoanPhase $phase, string $binary, string $originalFilename): void
    {
        $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION) ?: 'xlsx');
        if (! in_array($extension, ['xlsx', 'xls'], true)) {
            $extension = 'xlsx';
        }

        $mimeType = $extension === 'xls'
            ? 'application/vnd.ms-excel'
            : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        $this->persistProcessedCodeListDocument(
            $phase,
            fn (string $directory) => $this->writeProcessedCodeListBinary($directory, $binary, $extension),
            $originalFilename,
            $mimeType,
        );
    }

    public function storeProcessedCodeListFromUpload(LoanPhase $phase, $uploadedFile): void
    {
        $extension = strtolower($uploadedFile->getClientOriginalExtension() ?: '');
        if ($extension === '') {
            $extension = match ($uploadedFile->getMimeType()) {
                'application/vnd.ms-excel' => 'xls',
                default => 'xlsx',
            };
        }

        if (! in_array($extension, ['xlsx', 'xls'], true)) {
            throw ValidationException::withMessages([
                'codeListFile' => 'Only Excel files (.xlsx, .xls) are accepted.',
            ]);
        }

        if ($uploadedFile->getSize() > 5 * 1024 * 1024) {
            throw ValidationException::withMessages([
                'codeListFile' => 'The processed code list must not exceed 5MB.',
            ]);
        }

        $this->persistProcessedCodeListDocument(
            $phase,
            fn (string $directory) => $uploadedFile->storeAs(
                $directory,
                'processed-code-list-' . time() . '.' . $extension,
                'local',
            ),
            $uploadedFile->getClientOriginalName(),
            $uploadedFile->getMimeType(),
        );
    }

    protected function writeProcessedCodeListBinary(string $directory, string $binary, string $extension): string
    {
        $filename = 'processed-code-list-' . time() . '.' . $extension;
        $path = "{$directory}/{$filename}";

        Storage::disk('local')->put($path, $binary);

        return $path;
    }

    protected function persistProcessedCodeListDocument(
        LoanPhase $phase,
        callable $storeFile,
        string $originalFilename,
        string $mimeType,
    ): void {
        $phase->loadMissing('phaseApplicationDocuments');

        $directory = "phases/{$phase->slug}";
        $path = $storeFile($directory);
        $this->ensurePhaseDocumentPermissions($path);
        $filename = basename($path);
        $size = Storage::disk('local')->size($path);

        try {
            DB::beginTransaction();

            $existing = $phase->phaseApplicationDocuments()
                ->whereIn('document_type', PhaseDocumentType::processedCodeListTypes())
                ->first();

            if ($existing) {
                Storage::disk('local')->delete($existing->path);
                $existing->delete();
            }

            $phase->phaseApplicationDocuments()->create([
                'loan_phase_id' => $phase->id,
                'title' => 'Processed Code List',
                'document_type' => PhaseDocumentType::PROCESSED_CODE_LIST,
                'original_filename' => $originalFilename,
                'filename' => $filename,
                'path' => $path,
                'mime_type' => $mimeType,
                'size' => $size,
                'uploaded_by' => auth()->id(),
            ]);

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Failed to store processed code list: ' . $th->getMessage());

            if (Storage::disk('local')->exists($path)) {
                Storage::disk('local')->delete($path);
            }

            throw $th;
        }
    }

    public function uploadPhaseDocument($phase, $file, $title, $documentType) : void
    {
        if (!$phase instanceof LoanPhase) {
            $phase = LoanPhase::with(['phaseApplicationDocuments'])->findOrFail($phase);
        }

        if ($file->getSize() > 2 * 1024 * 1024) {
            throw ValidationException::withMessages([
                'file' => 'The file must not exceed 2MB in size.',
            ]);
        }

        $directory = "phases/{$phase->slug}";
        $filename = Str::slug($title) . '-' . time() . '.' . $file->getClientOriginalExtension();

        $path = $file->storeAs($directory, $filename, 'local');
        $this->ensurePhaseDocumentPermissions($path);

        DB::beginTransaction();

        try {
            $phase->phaseApplicationDocuments()->create([
                'loan_phase_id'     => $phase->id,
                'title'             => $title,
                'document_type'     => $documentType,
                'original_filename' => $file->getClientOriginalName(),
                'filename'          => $filename,
                'path'              => $path,
                'mime_type'         => $file->getMimeType(),
                'size'              => $file->getSize(),
                'uploaded_by'       => auth()->id(),
            ]);


            DB::commit();

        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error("Failed to upload phase document: " . $th->getMessage());

            if (Storage::disk('local')->exists($path)) {
                Storage::disk('local')->delete($path);
            }

            throw $th;
        }
    }

    public function deleteApplicationPhaseDocument($document) : void
    {
        try {
            DB::beginTransaction();

            if (Storage::disk('local')->exists($document->path)) {
                Storage::disk('local')->delete($document->path);
            }

            $document->delete();

            DB::commit();

        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error("Failed to delete phase document: " . $th->getMessage());
            throw $th;
        }
    }

    public function getPhaseStats($id) : LoanPhase
    {
        return LoanPhase::select(['id', 'phase', 'slug'])->withCount([
                'phaseApplications as total_apps',
                'phaseApplications as pending_apps' => fn($q) => $q->where('status', 'pending'),
                'phaseApplications as approved_apps' => fn($q) => $q->where('status', 'approved'),
                'phaseApplications as rejected_apps' => fn($q) => $q->where('status', 'rejected'),
            ])
            ->withExists(['phaseApplicationDocuments as has_minutes' => function ($query) {
                $query->where('document_type', 'minutes');
            }])
            ->withExists(['phaseApplicationDocuments as has_bank_reconciliation' => function ($query) {
                $query->where('document_type', 'bank_reconciliation');
            }])
            ->findOrFail($id);
    }

    public function submitToHq($phase, $remarks, $idempotencyKey)
    {
        if (!$phase->has_minutes || !$phase->has_bank_reconciliation || !($phase->has_request_for_transfer_letter ?? false)) {
            throw ValidationException::withMessages([
                'phase' => 'Signed committee minutes, both current bank reconciliations (main account and loan repayment), and the request for transfer letter are required before submitting to HQ.',
            ]);
        }

        $pendingApplications = $phase->phaseApplications()
            ->where('status', 'pending_submission_to_hq')
            ->with(['tempApplication' => function($query) {
                $query->select([
                    'id',
                    'group_name',
                    'application_id',
                    'group_registration_number',
                ]);
            }])
            ->get();

        $randomHex = strtoupper(bin2hex(random_bytes(2)));

        if ($pendingApplications->isEmpty()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'phase' => 'No approved applications found in this phase ready for HQ submission.'
            ]);
        }

        $ref = "UWEZO/HQ/{$phase->constituency_code}/{$phase->slug}/" . now()->format('Ymd') . "/" . $randomHex;

        $stats = [
            'count' => $pendingApplications->count(),
            'total_approved' => $phase->total_approved, // This is a computed/ghost column
        ];

        $phase->refresh();

        DB::transaction(function () use ($phase, $pendingApplications, $remarks, $ref, $idempotencyKey, $stats) {


            $tempAppIds = $pendingApplications->pluck('temp_application_id')->toArray();
            TempApplication::whereIn('id', $tempAppIds)->update([
                'status' => 'under_hq_review',
                'lms_to_loan_app_sync_status' => 'pending',
                'updated_at' => now()
            ]);

            $phase->phaseApplications()
                ->where('status', 'pending_submission_to_hq')
                ->update([
                    'status' => 'pending_hq_approval',
                    'level' => 'hq',
                    'action' => 'processing_at_hq',
                    'updated_at' => now()
                ]);

            foreach ($pendingApplications as $app) {
                activity()
                    ->performedOn($app)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'application_id' => $app->tempApplication->application_id,
                        'group_name' => $app->tempApplication->group_name,
                        'reg_number' => $app->tempApplication->group_registration_number,
                        'previous_status' => 'pending_submission_to_hq',
                        'new_status' => 'pending_hq_approval',
                        'constituency_code' => $phase->constituency_code
                    ])
                    ->log("Application #{$app->tempApplication->application_id} for {$app->tempApplication->group_name} was submitted to HQ.");

                SyncApplicationStatusToDrupal::pushAfterCommit($app->temp_application_id);
            }

            $phase->update([
                'status' => 'submitted_to_hq',
                'submitted_to_hq_at' => now(),
                'constituency_officer_id' => auth()->id(),
                'phase_ref' => $ref,
                'committee_remarks' => $remarks ?? null,
                'accountant_review_status' => null,
                'accountant_notes' => null,
                'accountant_reviewed_at' => null,
                'accountant_reviewed_by' => null,
                'hq_accountant_resubmit_notes' => null,
                'hq_accountant_resubmitted_at' => null,
                'hq_accountant_resubmitted_by' => null,
            ]);

            activity()
                ->performedOn($phase)
                ->causedBy(auth()->user())
                ->withProperties([
                    'phase' => $phase->phase,
                    'reference' => $ref,
                    'batch_count' => $stats['count'],
                    'total_amount_approved' => $stats['total_approved'],
                    'committee_remarks' => $remarks ?? null,
                    'status' => 'submitted_to_hq',
                    'affected_applications' => $pendingApplications->map(function($app) {
                        return [
                            'app_id' => $app->tempApplication->application_id,
                            'group_name' => $app->tempApplication->group_name,
                        ];
                    })->toArray(),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent()
                ])
                ->log("Phase finalized and submitted to HQ with Ref: {$ref}");

            app(\App\Services\Constituency\ConstituencyAllocationService::class)
                ->syncAllSubmittedPhasesForConstituency($phase->constituency_code, $phase->fresh());

            $this->idempotencyService->release($idempotencyKey);
        });

        try {
            app(WorkflowNotificationService::class)->notifyHqOfficersPhaseSubmitted($phase->fresh());
        } catch (\Throwable $e) {
            Log::error('Failed to notify HQ officers of phase submission', [
                'phase_id' => $phase->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function returnPhaseToConstituency(LoanPhase $phase, string $remarks, string $idempotencyKey): void
    {
        if ($phase->status !== 'submitted_to_hq') {
            throw ValidationException::withMessages([
                'phase' => 'Only phases currently at HQ can be returned to the constituency for amendment.',
            ]);
        }

        if (trim($remarks) === '') {
            throw ValidationException::withMessages([
                'returnRemarks' => 'Please provide a reason for returning this phase to the constituency.',
            ]);
        }

        DB::transaction(function () use ($phase, $remarks, $idempotencyKey) {
            $pendingApplications = $phase->phaseApplications()
                ->where('status', 'pending_hq_approval')
                ->get();

            if ($pendingApplications->isEmpty()) {
                throw ValidationException::withMessages([
                    'phase' => 'No applications are pending HQ review in this phase.',
                ]);
            }

            $tempAppIds = $pendingApplications->pluck('temp_application_id')->all();

            TempApplication::whereIn('id', $tempAppIds)->update([
                'status' => 'under_constituency_review',
                'lms_to_loan_app_sync_status' => 'pending',
                'updated_at' => now(),
            ]);

            $phase->phaseApplications()
                ->where('status', 'pending_hq_approval')
                ->update([
                    'status' => 'pending_submission_to_hq',
                    'level' => 'constituency',
                    'action' => 'returned_from_hq',
                    'updated_at' => now(),
                ]);

            $phase->update([
                'status' => 'draft',
                'submitted_to_hq_at' => null,
                'hq_remarks' => $remarks,
            ]);

            foreach ($pendingApplications as $app) {
                activity()
                    ->performedOn($app)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'previous_status' => 'pending_hq_approval',
                        'new_status' => 'pending_submission_to_hq',
                        'return_reason' => $remarks,
                        'constituency_code' => $phase->constituency_code,
                    ])
                    ->log('Application returned to constituency for amendment by HQ.');

                SyncApplicationStatusToDrupal::pushAfterCommit($app->temp_application_id);
            }

            activity()
                ->performedOn($phase)
                ->causedBy(auth()->user())
                ->withProperties([
                    'phase' => $phase->phase,
                    'return_reason' => $remarks,
                    'batch_count' => $pendingApplications->count(),
                    'status' => 'draft',
                ])
                ->log("Phase returned to constituency for amendment by HQ.");

            $this->invalidatePhaseCache($phase->id);
            $this->idempotencyService->release($idempotencyKey);
        });

        try {
            app(WorkflowNotificationService::class)
                ->notifyConstituencyOfficersPhaseReturned($phase->fresh(), $remarks);
        } catch (\Throwable $e) {
            Log::error('Failed to notify constituency officers of phase return', [
                'phase_id' => $phase->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function approvePhaseApplications($phase, $remarks, $idempotencyKey)
    {
        $pendingApplications = $phase->phaseApplications()
            ->where('status', 'pending_hq_approval')
            ->with([
                    'tempApplication' => function($query) {
                        $query->select([
                            'id',
                            'application_id',
                            'group_name',
                            'group_registration_number',
                            'ward_id',
                            'nature_of_group',
                            'business_type',
                            'date_registered',
                            'bank_name',
                            'bank_branch',
                            'bank_account_number',
                            'nearest_landmark',
                            'created_at',
                        ]);
                    },
                    'tempApplication.groupMembers:id,temp_application_id,name,id_number,gender,phone_number,position,person_with_disability'
                ])
            ->get();

        if ($pendingApplications->isEmpty()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'phase' => 'No approved applications found in this phase ready for HQ Approval.'
            ]);
        }

        $capturedStats = [
            'total_requested' => $phase->total_requested,
            'total_approved' => $phase->total_approved,
            'batch_count' => $pendingApplications->count(),
        ];

        $phase->refresh();

        DB::transaction(function () use ($phase, $pendingApplications, $remarks, $idempotencyKey, $capturedStats) {
            $phase->phaseApplications()
                ->where('status', 'pending_hq_approval')
                ->update([
                    'status' => 'approved',
                    'level' => 'constituency',
                    'action' => 'approved_at_hq',
                    'updated_at' => now()
                ]);

            $tempAppIds = $pendingApplications->pluck('temp_application_id')->toArray();
            TempApplication::whereIn('id', $tempAppIds)->update([
                'status' => 'approved',
                'lms_to_loan_app_sync_status' => 'pending',
                'updated_at' => now()
            ]);

            foreach ($pendingApplications as $app) {

                if (!$app->tempApplication) continue;

                $group = null;
                $loanType = 'New';

                if ($app->group_id) {
                    $group = Group::where('id', $app->group_id)
                        ->whereHas('ward', function ($q) use ($phase) {
                        $q->where('constituency_code', $phase->constituency_code);
                    })
                    ->with('currentLoan')
                    ->first();

                    if ($group) {
                        if($group->currentLoan) $loanType = 'Refinancing';

                        $group->update([
                            'name' => trim($app->tempApplication->group_name),
                            'ward_id' => $app->tempApplication->ward_id,
                            'reg_number' => trim($app->tempApplication->group_registration_number),
                            'date_registered' => $app->tempApplication->date_registered,
                            'bank' => trim($app->tempApplication->bank_name) . ', ' . trim($app->tempApplication->bank_branch),
                            'account' => $app->tempApplication->bank_account_number,
                            'location' => $app->tempApplication->nearest_landmark,
                        ]);
                    }
                }

                if (!$group) {
                    $group = Group::create([
                            'ward_id' => $app->tempApplication->ward_id,
                            'name' => trim($app->tempApplication->group_name),
                            'code' => $app->group_code,
                            'reg_number' => trim($app->tempApplication->group_registration_number),
                            'date_registered' => $app->tempApplication->date_registered,
                            'bank' => trim($app->tempApplication->bank_name) . ', ' . trim($app->tempApplication->bank_branch),
                            'account' => $app->tempApplication->bank_account_number,
                            'location' => $app->tempApplication->nearest_landmark,
                        ]
                    );

                    $app->update(['group_id' => $group->id]);
                }

                $groupLoan = GroupLoan::create([
                    'loan_phase_id' => $app->loan_phase_id,
                    'group_id' => $group->id,
                    'gfs_code' => $app->gfs_code,
                    'category' => $app->tempApplication->nature_of_group,
                    'sector' => is_array($app->tempApplication->business_type) ? ($app->tempApplication->business_type[0] ?? 'other') : $app->tempApplication->business_type,
                    'loan_type' => $loanType,
                    'status' => 'pending',
                    'application_date' => $app->tempApplication->created_at,
                    'loan_status' => 'pending',
                    'loan_level' => $app->loan_level,
                    'amount_applied' => $app->amount_applied,
                    'amount_approved' => $app->amount_approved
                ]);

                if ($app->tempApplication->groupMembers) {
                    foreach ($app->tempApplication->groupMembers as $tempMember) {

                        $member = Member::updateOrCreate(
                            ['member_Id' => $tempMember->id_number],
                            [
                                'name'     => trim($tempMember->name),
                                'number'   => $tempMember->phone_number,
                                'gender'   => ucfirst(strtolower($tempMember->gender)),
                                'disabled' => (bool)$tempMember->person_with_disability,
                            ]
                        );

                        DB::table('group_members')->updateOrInsert(
                            [
                                'member_Id'      => $member->member_Id,
                                'group_loans_id' => $groupLoan->id
                            ],
                            [
                                'role'       => $tempMember->position ?? 'Member',
                                'updated_at' => now(),
                                'created_at' => now()
                            ]
                        );
                    }
                }

                $app->update([
                    'group_loan_id' => $groupLoan->id,
                ]);

                activity()
                    ->performedOn($app)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'group_id' => $group->id,
                        'group_name' => $app->tempApplication->group_name,
                        'amount_approved' => $app->amount_approved,
                        'loan_type' => $loanType,
                        'reg_number' => $app->tempApplication->group_registration_number,
                        'previous_status' => 'pending_hq_approval',
                        'new_status' => 'approved',
                        'constituency_code' => $phase->constituency_code
                    ])
                    ->log("Application #{$app->tempApplication->application_id} for {$app->tempApplication->group_name} was approved and synced to the main groups table.");

                if ($loanType === 'New') {
                    activity()
                        ->performedOn($group)
                        ->causedBy(auth()->user())
                        ->log("New group record created in constituency {$phase->constituency_code} during HQ approval.");
                }

                SyncApplicationStatusToDrupal::pushAfterCommit($app->temp_application_id);
            }

            $phase->update([
                'status' => 'approved',
                'hq_remarks' => $remarks,
                'hq_officer_id' => auth()->id(),
                'hq_approved_at' => now(),
                'total_amount_applied' => $capturedStats['total_requested'],
                'total_amount_approved' => $capturedStats['total_approved']
            ]);

            activity()
                ->performedOn($phase)
                ->causedBy(auth()->user())
                ->withProperties([
                    'phase' => $phase->phase,
                    'reference' =>$phase->phase_ref,
                    'batch_count' => $capturedStats['total_requested'],
                    'total_amount_applied' => $capturedStats['total_requested'],
                    'total_amount_approved' => $capturedStats['total_approved'],
                    'hq_remarks' => $remarks,
                    'status' => 'approved',
                    'affected_groups' => $pendingApplications->map(function($app) {
                        return [
                            'app_id' => $app->tempApplication->application_id,
                            'group_name' => $app->tempApplication->group_name,
                        ];
                    })->toArray(),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent()
                ])
                ->log("HQ Approval Processed: {$phase->phase} approved with " . $capturedStats['batch_count'] . " applications.");

            $this->idempotencyService->release($idempotencyKey);
        });

        try {
            app(WorkflowNotificationService::class)->notifyHeadOfSecretariatPhaseReady($phase->fresh());

            app(WorkflowNotificationService::class)->notifyConstituencyOfficersHqApplicationAction(
                $pendingApplications->first()->fresh(['tempApplication', 'loanPhase.constituency.county']),
                'approved',
                "{$capturedStats['batch_count']} application(s) in {$phase->phase} were approved by HQ.",
            );
        } catch (\Throwable $e) {
            Log::error('Failed to notify constituency officers of HQ approval', [
                'phase_id' => $phase->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function saveGroupTrainingSchedule(PhaseApplication $application, string $idempotencyKey, array $payload): void
    {
        if ($application->status !== 'approved') {
            throw ValidationException::withMessages([
                'application' => 'Only approved groups can be scheduled for training.',
            ]);
        }

        $application->loadMissing(['tempApplication', 'loanPhase']);
        $phase = $application->loanPhase;

        if (!$phase || !in_array($phase->status, ['approved', 'in_training'], true)) {
            throw ValidationException::withMessages([
                'phase' => 'Training cannot be scheduled for this phase in its current state.',
            ]);
        }

        DB::transaction(function () use ($application, $phase, $idempotencyKey, $payload) {
            if ($application->tempApplication) {
                $application->tempApplication->update([
                    'status' => 'under_hq_review',
                    'lms_to_loan_app_sync_status' => 'pending',
                    'updated_at' => now(),
                ]);
            }

            $application->update([
                'status' => 'in_training',
                'level' => 'constituency',
                'action' => 'set_for_traning',
                'training_start_date' => $payload['trainingStartDate'],
                'training_end_date' => $payload['trainingEndDate'],
                'training_venue' => $payload['trainingVenue'],
                'training_notes' => $payload['trainingNotes'] ?? null,
                'updated_at' => now(),
            ]);

            if ($phase->status === 'approved') {
                $phase->update(['status' => 'in_training']);
            }

            $tempApp = $application->tempApplication;
            if ($tempApp) {
                activity()
                    ->performedOn($application)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'application_id' => $tempApp->application_id,
                        'group_name' => $tempApp->group_name,
                        'previous_status' => 'approved',
                        'new_status' => 'in_training',
                        'training_start_date' => $payload['trainingStartDate'],
                        'training_end_date' => $payload['trainingEndDate'],
                        'training_venue' => $payload['trainingVenue'],
                        'constituency_code' => $phase->constituency_code,
                    ])
                    ->log("Application #{$tempApp->application_id} for {$tempApp->group_name} was set for training.");

                SyncApplicationStatusToDrupal::pushAfterCommit($application->temp_application_id);
            }

            $this->idempotencyService->release($idempotencyKey);
        });

        app(WorkflowNotificationService::class)->notifyGroupMembersTrainingScheduled(
            $application->fresh(['tempApplication.groupMembers']),
            $phase->fresh()
        );
    }

    public function saveTrainingSchedule($phase, $idempotencyKey, array $payload)
    {
        $pendingApplications = $phase->phaseApplications()
            ->where('status', 'approved')
            ->with(['tempApplication' => function($query) {
                $query->select([
                    'id',
                    'group_name',
                    'application_id',
                ]);
            }])
            ->get();

        if ($pendingApplications->isEmpty()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'phase' => 'No approved applications found in this phase ready for training.'
            ]);
        }

        $apps_count = $pendingApplications->count();

        $phase->refresh();

        DB::transaction(function () use ($phase, $idempotencyKey, $pendingApplications, $payload, $apps_count) {
            $tempAppIds = $pendingApplications->pluck('temp_application_id')->toArray();
            TempApplication::whereIn('id', $tempAppIds)->update([
                'status' => 'under_hq_review',
                'lms_to_loan_app_sync_status' => 'pending',
                'updated_at' => now()
            ]);

            $phase->phaseApplications()
                ->where('status', 'approved')
                ->update([
                    'status' => 'in_training',
                    'level' => 'constituency',
                    'action' => 'set_for_traning',
                    'training_start_date' => $payload['trainingStartDate'],
                    'training_end_date' => $payload['trainingEndDate'],
                    'training_venue' => $payload['trainingVenue'],
                    'training_notes' => $payload['trainingNotes'] ?? null,
                    'updated_at' => now()
                ]);

            foreach ($pendingApplications as $app) {
                if (!$app->tempApplication) continue;

                activity()
                    ->performedOn($app)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'application_id' => $app->tempApplication->application_id,
                        'group_name' => $app->tempApplication->group_name,
                        'previous_status' => 'approved',
                        'new_status' => 'in_training',
                        'constituency_code' => $phase->constituency_code
                    ])
                    ->log("Application #{$app->tempApplication->application_id} for {$app->tempApplication->group_name} was set for training.");

                SyncApplicationStatusToDrupal::pushAfterCommit($app->temp_application_id);
            }

            $phase->update([
                'training_start_date' => $payload['trainingStartDate'],
                'training_end_date'   => $payload['trainingEndDate'],
                'training_venue'      => $payload['trainingVenue'],
                'status'              => 'in_training',
            ]);

            activity()
                ->performedOn($phase)
                ->causedBy(auth()->user())
                ->withProperties([
                    'phase' => $phase->phase,
                    'reference' =>$phase->phase_ref,
                    'batch_count' => $apps_count,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent()
                ])
                ->log("Training scheduled for {$apps_count} group(s) at {$payload['trainingVenue']} from {$payload['trainingStartDate']} to {$payload['trainingEndDate']}");

            $this->idempotencyService->release($idempotencyKey);
        });

        SendTrainingScheduleNotifications::dispatchSync($phase->id);
    }

    public function finalizePhase($phase)
    {
        $phase->refresh();

        if (!$phase->canFinalizeDisbursement()) {
            throw new \RuntimeException(
                'Cannot close this phase until every group is marked as disbursed, not disbursed, or rejected.'
            );
        }

        $total_issued = $phase->total_issued;

        DB::transaction(function () use ($phase, $total_issued){
            $phase->update([
                'status' => 'disbursed',
                'is_locked' => true,
                'locked_at' => now(),
                'locked_by' => auth()->id(),
                'amount_disbursed' => $total_issued,
                'disbursed_at' => now()
            ]);

            activity()
                ->performedOn($phase)
                ->causedBy(auth()->user())
                ->withProperties([
                    'old_status' => 'in_intraining',
                    'new_values' => $phase->fresh()->only(['status', 'is_locked', 'locked_at', 'locked_by', 'amount_disbursed', 'disbursed_at']),
                    'total_disbursed' => $total_issued,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->event('phase_disbursed')
                ->log("#{$phase->phase} has been disbursed with total amount of {$total_issued}");
        });

    }

    public function invalidatePhaseCache($phaseId): void
    {
        $phase_detaileds = config('cache.tags.phases.individual_detailed') . $phaseId;
        Cache::tags([$phase_detaileds])->flush();
    }

    public function invalidateMultipleApplicationsCache(array $applicationIds): void
    {
        foreach ($applicationIds as $id) {
            $this->applicationService->invalidateApplicationCache($id);
        }
    }

    public function getFileMetadata($file)
    {
        if (!$file) return null;

        if (!is_string($file)) {
            $name = $file->getClientOriginalName();
            $ext = strtolower($file->getClientOriginalExtension());
        } else {
            $name = basename($file);
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        }

        $icons = [
            'pdf'  => 'fa-file-pdf text-danger',
            'doc'  => 'fa-file-word text-primary',
            'docx' => 'fa-file-word text-primary',
            'xls'  => 'fa-file-excel text-success',
            'xlsx' => 'fa-file-excel text-success',
        ];

        return [
            'name' => $name,
            'icon' => $icons[$ext] ?? 'fa-file-alt text-muted',
        ];
    }

    private function ensurePhaseDocumentPermissions(string $path): void
    {
        $fullPath = Storage::disk('local')->path($path);
        $directory = dirname($fullPath);

        if (is_dir($directory)) {
            @chmod($directory, 0775);
        }

        if (is_file($fullPath)) {
            @chmod($fullPath, 0664);
        }
    }
}
