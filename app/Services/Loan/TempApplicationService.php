<?php

namespace App\Services\Loan;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use App\Models\TempApplication;
use App\Models\Group;
use App\Models\GroupLoan;
use App\Models\PhaseApplication;
use App\Services\Loan\DefaultAnalysisService;
use App\Services\IdempotencyService;
use App\Services\Cache\ApplicationCacheService;
use App\Services\Cache\AppCacheService;
use App\Jobs\SyncApplicationStatusToDrupal;
use App\Jobs\SendDisbursementNotifications;

class TempApplicationService
{
    protected DefaultAnalysisService $analysisService;
    protected IdempotencyService $idempotencyService;
    protected ApplicationCacheService $appCache;
    protected AppCacheService $cacheService;

    // Laravel automatically resolves this for you
    public function __construct(
        DefaultAnalysisService $analysisService,
        IdempotencyService $idempotencyService,
        ApplicationCacheService $applicationCacheService,
        AppCacheService $cacheService,
    )
    {
        $this->analysisService = $analysisService;
        $this->idempotencyService = $idempotencyService;
        $this->appCache = $applicationCacheService;
        $this->cacheService = $cacheService;
    }

    /**
     * Invalidate cache when application is updated/assigned/etc.
    */
    public function clearCache(string $applicationId, string $constituencyCode): void
    {
        Cache::forget("temp_app_detail_{$applicationId}");
    }

    /**
     * Clear stats cache when something changes
    */
    public function invalidateStatsCache(string $constituencyCode): void
    {
        Cache::forget("temp_app_stats_{$constituencyCode}");
    }

    /**
     * Invalidate cache for this application
    */
    public function invalidateApplicationCache($applicationId, $groupId = null): void
    {
        Cache::forget("temp_app_{$applicationId}");
        Cache::forget("temp_app_detail_{$applicationId}");
        Cache::forget("temp_app_default_members_{$applicationId}");
        Cache::forget("temp_app_linked_group_{$groupId}");
        Cache::forget("app_enrichment_{$applicationId}");

        if ($groupId) {
            Cache::forget("temp_app_linked_group_{$groupId}");
        }
    }

    /**
     * Update status (with validation & logging)
    */
    public function updateStatus(TempApplication $application, string $newStatus, ?string $reason = null): void
    {
        $allowedTransitions = [
            'submitted'     => ['under_review', 'rejected'],
            'under_review'  => ['approved', 'rejected', 'returned'],
            // add more as needed
        ];

        if (!isset($allowedTransitions[$application->status]) ||
            !in_array($newStatus, $allowedTransitions[$application->status])) {
            throw new \InvalidArgumentException("Invalid status transition: {$application->status} → {$newStatus}");
        }

        $application->update([
            'status' => $newStatus,
            'updated_at' => now(),
        ]);

        // log or create history record
        activity()
            ->performedOn($application)
            ->causedBy(auth()->user())
            ->withProperties(['status' => $newStatus, 'reason' => $reason])
            ->log('status-updated');

        $this->clearCache($application->application_id, $application->constituency_code);
    }

    /**
     * Get stats for a constituency (cached)
    */
    public function getStats(?string $constituencyCode = null): array
    {

        $appStats = DB::table('temp_applications')
            ->leftJoin('phase_applications', 'temp_applications.id', '=', 'phase_applications.temp_application_id')
            ->when($constituencyCode, function ($query, $code) {
                return $query->where('temp_applications.constituency_code', $code);
            })
            ->selectRaw("
                COUNT(temp_applications.id) as total,
                SUM(temp_applications.loan_amount) as total_amount,
                COUNT(phase_applications.id) as in_phase,

                /* Status Counts */
                SUM(CASE WHEN phase_applications.status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN phase_applications.status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN phase_applications.status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                SUM(CASE WHEN phase_applications.status = 'pending_hq_approval' THEN 1 ELSE 0 END) as pending_hq,

                /* Financial Summaries */
                SUM(CASE WHEN phase_applications.status = 'approved' THEN temp_applications.amount_approved ELSE 0 END) as amount_approved,
                SUM(CASE WHEN phase_applications.status = 'rejected' THEN temp_applications.amount_approved ELSE 0 END) as amount_declined,

                /* Demographic Category Counts */
                SUM(CASE WHEN LOWER(temp_applications.nature_of_group) = 'youth' THEN 1 ELSE 0 END) as youth_count,
                SUM(CASE WHEN LOWER(temp_applications.nature_of_group) = 'women' THEN 1 ELSE 0 END) as women_count,
                SUM(CASE WHEN LOWER(temp_applications.nature_of_group) = 'pwd' THEN 1 ELSE 0 END) as pwd_count
            ")
            ->first();



        $memberStats = DB::table('temp_group_members')
            ->join('temp_applications', 'temp_group_members.temp_application_id', '=', 'temp_applications.id')
            ->when($constituencyCode, function ($query, $code) {
                return $query->where('temp_applications.constituency_code', $code);
            })
            ->selectRaw("
                SUM(gender = 'male') as total_males,
                SUM(gender = 'female') as total_females,
                SUM(person_with_disability = 1) as total_pwds
            ")
            ->first();

        return [
            'total'        => (int)$appStats->total,
            'total_amount' => (float)$appStats->total_amount,
            'in_phase'     => (int)$appStats->in_phase,
            'not_in_phase' => (int)($appStats->total - $appStats->in_phase),
            'approved'     => (int)$appStats->approved,
            'pending_hq'   => (int)$appStats->pending_hq,
            'approved_amount' => (float)$appStats->amount_approved,
            'declined_amount' => (float)$appStats->amount_declined,
            'rejected'     => (int)$appStats->rejected,

            // Member Stats
            'total_males'   => (int)$memberStats->total_males,
            'total_females' => (int)$memberStats->total_females,
            'total_pwds'    => (int)$memberStats->total_pwds,

            // category counts
            'total_youth'   => (int)$appStats->youth_count,
            'total_women'   => (int)$appStats->women_count,
            'total_pwd'     => (int)$appStats->pwd_count,
        ];
    }

    /**
     * Build the filtered query (reusable, eager-loaded)
    */
    public function getFilteredQuery(?string $constituencyCode = null, array $filters = [], ?string $sortField, ?string $sortDirection): Builder
    {
        return TempApplication::query()
            ->select([
                'id',
                'application_id',
                'group_name',
                'owner_identifier',
                'loan_amount',
                'group_registration_number',
                'status',
                'created_at',
                'constituency_code'
            ])
            ->when($constituencyCode, function ($query, $code) {
                return $query->where('constituency_code', $code);
            })
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('application_id', 'like', "%{$search}%")
                        ->orWhere('group_name', 'like', "%{$search}%")
                        ->orWhere('owner_identifier', 'like', "%{$search}%")
                        ->orWhere('group_registration_number', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, function ($q, $status) {
                $q->where(function ($query) use ($status) {
                    $query->whereHas('phaseApplication', function ($pa) use ($status) {
                        $pa->where('status', $status);
                    })
                    ->orWhere(function ($sub) use ($status) {
                        $sub->whereDoesntHave('phaseApplication')
                            ->where('status', $status);
                    });
                });
            })
            ->when($filters['phase'] ?? null, function ($q, $phase) {
                if ($phase === 'not_in_phase') {
                    $q->whereDoesntHave('phaseApplication');
                } elseif ($phase) {
                    $q->whereHas('phaseApplication', fn($pa) =>
                        $pa->whereHas('reviewPhase', fn($rp) => $rp->where('slug', $phase))
                    );
                }
            })
            ->when($filters['county_code' ] ?? null, function ($q, $county_code) {
                $q->where('county_code', $county_code);
            })
            ->when($filters['constituency_code'] ?? null, function ($q, $constituency_code){
                $q->where('constituency_code', $constituency_code);
            })
            ->when($filters['date_from'] ?? null, fn($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($filters['date_to'] ?? null, fn($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->with([
                'phaseApplication' => function ($query) {
                    $query->select(
                        'id',
                        'phase_applications.temp_application_id',
                        'phase_applications.loan_phase_id',
                        'phase_applications.status',
                        'phase_applications.is_trained',
                        'phase_applications.training_certificate_path'
                    );
                },
                'phaseApplication.reviewPhase:id,phase,status,slug',
            ])
            ->when($sortField, function ($q) use ($sortField, $sortDirection) {
                switch($sortField) {
                    case 'applicationId':
                        $q->orderBy('application_id', $sortDirection);
                        break;

                    case 'user':
                        $q->orderBy('owner_identifier', $sortDirection);
                        break;

                    case 'groupName':
                        $q->orderBy('group_name', $sortDirection);
                        break;

                    case 'applied':
                        $q->orderBy('loan_amount', $sortDirection);
                        break;

                    case 'status':
                        $q->orderBy('status', $sortDirection);
                        break;

                    case 'created':
                        $q->orderBy('created_at', $sortDirection);
                        break;
                }
            }, function ($query) {
                $query->orderBy('created_at', 'desc');
            });

    }

    public function getPendingApplicationsProperty(string $constituencyCode, ?string $search) : Builder
    {
        return TempApplication::query()
            ->select([
                'id',
                'application_id',
                'group_name',
                'owner_identifier',
                'loan_amount',
                'group_registration_number',
                'status',
                'created_at',
                'constituency_code'
            ])
            ->whereDoesntHave('phaseApplication')
            ->where('constituency_code', $constituencyCode)
            ->when($search, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('application_id', 'like', "%{$search}%")
                        ->orWhere('group_name', 'like', "%{$search}%")
                        ->orWhere('owner_identifier', 'like', "%{$search}%")
                        ->orWhere('group_registration_number', 'like', "%{$search}%");
                });
            });
    }

    /**
     * Load a specific application with relations (cached)
     *
     * @param string $applicationId
     * @param string $constituencyCode (for security - only own constituency)
     * @return TempApplication
    */
    public function loadApplication(string $applicationId, string $constituencyCode): TempApplication
    {
        $cacheKey = "temp_app_{$applicationId}";

        try {
            return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($applicationId, $constituencyCode) {
                $app = TempApplication::where('application_id', $applicationId)
                    ->where('constituency_code', $constituencyCode)
                    ->with(['groupMembers', 'expenses', 'files', 'user'])
                    ->first();

                if (!$app) {
                    Log::error("Application not found: ID {$applicationId} in Const {$constituencyCode}");
                    throw new ModelNotFoundException("Application {$applicationId} not found.");
                }
                return $app;
            });
        } catch (\Exception $e) {
            Cache::forget($cacheKey);
            throw $e;
        }
    }

    /**
     * Get a single application with all important relations pre-loaded
    */
    public function getDetailedApplication(string $applicationId, ?string $constituencyCode = null): TempApplication
    {
        $phaseId = null;

        $application = TempApplication::query()
                ->where('application_id', $applicationId)
                ->when($constituencyCode, function ($query, $code) {
                    return $query->where('constituency_code', $code);
                })
                ->with([
                    'groupMembers',
                    'activities' => function($query) {
                        $query->with('causer')->latest();
                    },
                    'expenses',
                    'phaseApplication',
                    'phaseApplication.linkedGroup' => function($query) {
                        $query->withSum('groupLoans as total_issued', 'amount_issued')
                            ->withSum('groupLoans as total_repaid', 'amount_repaid')
                            ->withSum('groupLoans as total_arrears', 'total_arrears')
                            ->withCount(['groupLoans as defaulted_count' => function ($q) {
                                $q->where('is_defaulted', true);
                            }])
                            ->with([
                                'groupLoans' => function($q) {
                                    $q->where('loan_status', 'financed');
                                },
                                'groupLoans.loanReceipts',
                                'currentLoan',
                                'groupLoans.loanPhase' => function($q) {
                                    $q->select('id', 'phase');
                                }
                            ]);
                    },
                    'ward',
                    'constituency'
                ])
                ->firstOrFail();

            $phaseId = $application->phaseApplication->reviewPhase->id ?? null;

            $application->append(['current_phase', 'approval_progress']);

            return $application;

        // return $this->appCache->rememberApp(
        //     $applicationId,
        //     'temp_app_detail',
        //     function () use ($applicationId, $constituencyCode, &$phaseId) {
        //         $application = TempApplication::query()
        //         ->where('application_id', $applicationId)
        //         ->when($constituencyCode, function ($query, $code) {
        //             return $query->where('constituency_code', $code);
        //         })
        //         ->with([
        //             'groupMembers',
        //             'activities' => function($query) {
        //                 $query->with('causer')->latest();
        //             },
        //             'expenses',
        //             'phaseApplication',
        //             'phaseApplication.linkedGroup' => function($query) {
        //                 $query->withSum('groupLoans as total_issued', 'amount_issued')
        //                     ->withSum('groupLoans as total_repaid', 'amount_repaid')
        //                     ->withSum('groupLoans as total_arrears', 'total_arrears')
        //                     ->withCount(['groupLoans as defaulted_count' => function ($q) {
        //                         $q->where('is_defaulted', true);
        //                     }])
        //                     ->with([
        //                         'groupLoans' => function($q) {
        //                             $q->where('loan_status', 'financed');
        //                         },
        //                         'groupLoans.loanReceipts',
        //                         'currentLoan',
        //                         'groupLoans.loanPhase' => function($q) {
        //                             $q->select('id', 'phase');
        //                         }
        //                     ]);
        //             },
        //             'ward',
        //             'constituency'
        //         ])
        //         ->firstOrFail();

        //     $phaseId = $application->phaseApplication->reviewPhase->id ?? null;

        //     $application->append(['current_phase', 'approval_progress']);

        //     return $application;
        //     },
        //     $constituencyCode,
        //     $phaseId ?? null,
        //     10
        // );
    }

    private function loadDefaultMembers(TempApplication $application): Collection
    {
        if ($application->groupMembers->isEmpty()) {
            return Collect();
        }

        $appMemberIds = $application->groupMembers->pluck('id_number')->toArray();

        $phaseId = $application->phaseApplication->reviewPhase->id ?? null;

        $defaulterData =  $this->analysisService->getApplicationDefaulterMembers(
            $appMemberIds,
            $application->application_id,
            $application->group_id,
            $application->constituency_code,
            $phaseId
        )->keyBy('member_Id');

        $app = $application->groupMembers->transform(function ($appMember) use ($defaulterData) {
            $history = $defaulterData->get($appMember->id_number);

            $appMember->is_defaulter = !is_null($history);

            $appMember->default_history = $history ? $history->groupMembers->filter(function ($gm) {
                return $gm->groupLoan && $gm->groupLoan->is_defaulted;
            }) : collect();

            return $appMember;
        });

        return $app;
    }

    /**
     * Enrich application with default member data
     */
    public function getEnrichedApplication(string $applicationId, ?string $constituencyCode = null): TempApplication
    {
        $application = $this->getDetailedApplication($applicationId, $constituencyCode);

        $this->loadDefaultMembers($application);

        return $application;
    }


    /**
     * Approve an application
     *
     * @param TempApplication $application
     * @param string $remarks
     * @throws ValidationException
    */
    public function approve(TempApplication $application, string $remarks): void
    {
        if ($application->status !== 'under_review') {
            throw ValidationException::withMessages([
                'status' => 'Can only approve applications under review.',
            ]);
        }

        DB::transaction(function () use ($application, $remarks) {
            $application->update([
                'status' => 'approved',
                'reviewer_remarks' => $remarks,
                'reviewed_at' => now(),
                'reviewed_by' => auth()->id(),
            ]);

            // Log action (Spatie or custom)
            activity()
                ->performedOn($application)
                ->causedBy(auth()->user())
                ->withProperties(['remarks' => $remarks])
                ->log('Application approved');
        });
    }

    /**
     * Reject an application
     *
     * @param TempApplication $application
     * @param string $remarks
     * @throws ValidationException
    */
    public function reject(TempApplication $application, string $remarks): void
    {
        if ($application->status !== 'under_review') {
            throw ValidationException::withMessages([
                'status' => 'Can only reject applications under review.',
            ]);
        }

        if (empty(trim($remarks))) {
            throw ValidationException::withMessages([
                'remarks' => 'Remarks are required for rejection.',
            ]);
        }

        DB::transaction(function () use ($application, $remarks) {
            $application->update([
                'status' => 'rejected',
                'reviewer_remarks' => $remarks,
                'reviewed_at' => now(),
                'reviewed_by' => auth()->id(),
            ]);

            activity()
                ->performedOn($application)
                ->causedBy(auth()->user())
                ->withProperties(['remarks' => $remarks])
                ->log('Application rejected');
        });
    }

    /**
     * Link application to a master group
     *
     * @param TempApplication $application
     * @param int $groupId
     * @throws ValidationException
    */
    public function linkToMasterGroup(TempApplication $application, int $groupId): void
    {
        if (is_null($application->phaseApplication)){
            throw ValidationException::withMessages([
                'group' => 'You Must Assigng the application to a phase first',
            ]);
        }

        $group = Group::with(['currentLoan', 'ward'])
              ->findOrFail($groupId);

        //  Check if group is in same constituency
        if ($group->ward->constituency_code !== $application->constituency_code) {
            throw ValidationException::withMessages([
                'group' => 'Group must be in the same constituency.',
            ]);
        }

        // Check if already linked
        if ($application->phaseApplication->group_id) {
            throw ValidationException::withMessages([
                'group' => 'Application is already linked to a group.',
            ]);
        }

        // check if another application is already linked to group in the same phase
        $existsInPhase = PhaseApplication::where('loan_phase_id', $application->phaseApplication->loan_phase_id)
            ->where('group_id', $groupId)
            ->exists();

        if ($existsInPhase) {
            throw ValidationException::withMessages([
                'group' => 'APPLICATION NOT LINKED: This Group has already been assigned to a different application in this funding phase.',
            ]);
        }

        DB::transaction(function () use ($application, $group) {
            $application->phaseApplication->update([
                'group_id' => $group->id,
                'group_code' => $group->code,
                'loan_level' => $group->currentLoan->loan_level+1,
            ]);

            $application->update([
                'link_status' => 'linked',
                'loan_type' => 'Endeleza',
            ]);
        });
    }

    public function markAsNoMatch(TempApplication $application): void
    {
        if (is_null($application->phaseApplication)) {
            throw ValidationException::withMessages([
                'application' => 'Assign the application to a phase before verification.',
            ]);
        }

        $application->update([
            'link_status' => 'attempted_no_match',
            'loan_type' => 'Wezesha',
        ]);
    }

    public function unLinkFromMasterGroup(TempApplication $application): void
    {
        if (is_null($application->phaseApplication->group_id)) {
            throw ValidationException::withMessages([
                'application' => 'This application is not linked to any group.',
            ]);
        }

        DB::transaction(function () use ($application) {
            $application->phaseApplication->update([
                'group_id' => null,
                'group_code' => null,
                'loan_level' => 1,
            ]);

            $application->update([
                'link_status' => 'not_attempted',
                'loan_type' => 'Wezesha',
            ]);
        });

    }


    /**
     * Process expenses into clean array format
    */
    public function getProcessedExpenses(TempApplication $application): array
    {
        $expenses = is_string($application->expenses)
            ? json_decode($application->expenses, true)
            : $application->expenses;

        return collect($expenses)->flatMap(function ($item) {
            if (!isset($item['name'])) {
                return collect($item)->filter(fn($amt) => $amt > 0)
                    ->map(fn($amt, $name) => [
                        'name' => ucfirst(str_replace('_', ' ', $name)),
                        'amount' => $amt
                    ]);
            }
            return [$item];
        })->values()->all();
    }

    /**
     * Process all files with URLs, icons, etc.
    */
    public function getProcessedFiles(TempApplication $application, string $countySlug, string $constituencySlug): array
    {
        $processedFiles = [];

        foreach ($application->all_files ?? [] as $file) {
            $encodedPath = $file['data']['path'] ?? null ? base64_encode($file['data']['path']) : null;

            if (!$encodedPath) continue;

            $viewUrl = route('const.app.application.file.download', [
                'county' => $countySlug,
                'constituency' => $constituencySlug,
                'applicationId' => $application->application_id,
                'action' => 'view',
            ]) . '?path=' . $encodedPath;

            $downloadUrl = route('const.app.application.file.download', [
                'county' => $countySlug,
                'constituency' => $constituencySlug,
                'applicationId' => $application->application_id,
                'action' => 'download',
            ]) . '?path=' . $encodedPath;

            $fileType = $this->getFileTypeIcon($file['data']['original_filename'] ?? '');

            $processedFiles[] = [
                'field_name'        => $file['field_name'],
                'original_filename' => $file['data']['original_filename'] ?? 'Unknown',
                'view_url'          => $viewUrl,
                'download_url'      => $downloadUrl,
                'type'              => $fileType['icon'],
                'can_view'          => $fileType['can_view'],
                'size'              => $this->formatFileSize($file['data']['size'] ?? 0),
                'google_view_url'   => $this->getGoogleDocsViewerUrl($viewUrl, $file['data']['original_filename'] ?? ''),
            ];
        }

        return $processedFiles;
    }

    public function getHqProcessedFiles(TempApplication $application): array
    {
        $processedFiles = [];

        foreach ($application->all_files ?? [] as $file) {
            $encodedPath = $file['data']['path'] ?? null ? base64_encode($file['data']['path']) : null;

            if (!$encodedPath) continue;

            $viewUrl = route('hq.submissions.app.file.download', [
                'applicationId' => $application->application_id,
                'action' => 'view',
            ]) . '?path=' . $encodedPath;

            $downloadUrl = route('hq.submissions.app.file.download', [
                'applicationId' => $application->application_id,
                'action' => 'download',
            ]) . '?path=' . $encodedPath;

            $fileType = $this->getFileTypeIcon($file['data']['original_filename'] ?? '');

            $processedFiles[] = [
                'field_name'        => $file['field_name'],
                'original_filename' => $file['data']['original_filename'] ?? 'Unknown',
                'view_url'          => $viewUrl,
                'download_url'      => $downloadUrl,
                'type'              => $fileType['icon'],
                'can_view'          => $fileType['can_view'],
                'size'              => $this->formatFileSize($file['data']['size'] ?? 0),
                'google_view_url'   => $this->getGoogleDocsViewerUrl($viewUrl, $file['data']['original_filename'] ?? ''),
            ];
        }

        return $processedFiles;
    }

    /**
     * Get icon & viewability for file extension
    */
    public function getFileTypeIcon(string $filename): array
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        $icons = [
            'pdf'   => ['icon' => 'fa-file-pdf text-danger', 'can_view' => true],
            'jpg'   => ['icon' => 'fa-file-image text-success', 'can_view' => true],
            'jpeg'  => ['icon' => 'fa-file-image text-success', 'can_view' => true],
            'png'   => ['icon' => 'fa-file-image text-success', 'can_view' => true],
            'gif'   => ['icon' => 'fa-file-image text-success', 'can_view' => true],
            'doc'   => ['icon' => 'fa-file-word text-primary', 'can_view' => false],
            'docx'  => ['icon' => 'fa-file-word text-primary', 'can_view' => false],
            'xls'   => ['icon' => 'fa-file-excel text-success', 'can_view' => false],
            'xlsx'  => ['icon' => 'fa-file-excel text-success', 'can_view' => false],
            'txt'   => ['icon' => 'fa-file-alt text-secondary', 'can_view' => true],
        ];

        return $icons[$extension] ?? ['icon' => 'fa-file text-secondary', 'can_view' => false];
    }

    /**
     * Generate Google Docs Viewer URL if supported
    */
    public function getGoogleDocsViewerUrl(string $viewUrl, string $filename): string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $supported = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];

        if (in_array($extension, $supported)) {
            $encodedUrl = urlencode($viewUrl);
            return "https://docs.google.com/viewer?url={$encodedUrl}&embedded=true";
        }

        return $viewUrl;
    }

    /**
     * Format bytes to human-readable size
    */
    public function formatFileSize(int $bytes): string
    {
        if ($bytes == 0) return '0 Bytes';

        $k = 1024;
        $sizes = ['Bytes', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes) / log($k));

        return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    }

    /**
     * Get the three key group officials (chairperson, secretary, treasurer)
     *
     * @param TempApplication $application
     * @return array
    */
    public function getGroupOfficials(TempApplication $application): array
    {
        $members = $application->groupMembers ?? collect();

        $chairperson = $members->firstWhere('position', 'Chairperson');
        $secretary = $members->firstWhere('position', 'Secretary');
        $treasurer = $members->firstWhere('position', 'Treasurer');

        return [
            'chairperson' => $chairperson,
            'secretary'   => $secretary,
            'treasurer'   => $treasurer,
        ];
    }

    /**
     * Normalize phone numbers to the last 9 digits to ignore country codes/prefixes.
    */
    private function normalizePhone(?string $phone): string
    {
        if (!$phone) return '';
        $phone = preg_replace('/[^0-9]/', '', $phone);
        return substr($phone, -9);
    }

    /**
     * Advanced normalization for Group Names and Reg Numbers.
    */
    private function normalizeString(?string $str): string
    {
        if (!$str) return '';
        $str = strtolower($str);

        // Handle variations of common abbreviations
        $replacements = [
            's.h.g' => '', 'shg' => '', 'self help group' => '',
            'w.g'   => '', 'wg'  => '', 'women group' => '',
            'y.g'   => '', 'yg'  => '', 'youth group' => '',
            'f.f.s' => '', 'ffs' => '', 'farmers field school' => '',
            'c.b.o' => '', 'cbo' => '', 'community based organization' => '',
        ];

        $str = str_replace(array_keys($replacements), array_values($replacements), $str);

        // Remove all non-alphanumeric characters (removes spaces, dots, slashes)
        return preg_replace('/[^a-z0-9]/', '', $str);
    }

    public function findMatchingGroups(TempApplication $application): Collection
    {
        $const_code = $application->constituency_code;

        $groups = Group::select(['id', 'ward_id', 'reg_number', 'name', 'code'])
            ->whereHas('ward.constituency', function ($query) use ($const_code) {
                $query->where('constituency_code', $const_code);
            })
            ->where(function($q) use ($application) {
                $q->where('name', 'like', '%' . substr($application->group_name, 0, 3) . '%')
                ->orWhere('reg_number', 'like', '%' . substr($application->group_registration_number, -4));
            })
            ->with([
                'ward:id,ward_name',
                'currentLoan:id,group_loans.group_id,group_loans.loan_phase_id,group_loans.gfs_code',
                'currentLoan.groupMembers:id,group_loans_id,member_Id',
                'currentLoan.groupMembers.member:id,member_Id,number',
                'currentLoan.loanPhase:id,phase',
            ])
            ->get();


        $tempMemberData = $application->groupMembers()
            ->get(['id_number', 'phone_number']);

        $tempMemberIds = $tempMemberData->pluck('id_number')->filter()->toArray();
        $tempMemberPhones = $tempMemberData->map(fn($m) => $this->normalizePhone($m->phone_number))
            ->filter()
            ->toArray();

        return $groups->map(function ($group) use ($application, $tempMemberIds, $tempMemberPhones) {
            // 1. Calculate Scores
            $regScore = $this->calculateSimilarity(
                $application->group_registration_number,
                $group->reg_number
            );

            $nameScore = $this->calculateSimilarity(
                $application->group_name,
                $group->name
            );

            // 2. Check Phone (Last 9 digits)
            $idMatchCount = 0;
            $phoneMatchCount = 0;

            $phoneMatch = false;

            $appPhone = $this->normalizePhone($application->phone_number);

            if ($group->currentLoan) {
                $existingMembers = $group->currentLoan->groupMembers->map(fn($gm) => $gm->member);

                $existingIds = $existingMembers->pluck('member_Id')->filter()->map(fn($id) => (string)$id)->toArray();
                $existingPhones = $existingMembers->map(fn($m) => $this->normalizePhone($m->number))
                    ->filter()
                    ->toArray();
                // dd($tempMemberIds, $existingIds, $existingMembers, $group->currentLoan);

                $idMatchCount += count(array_intersect($tempMemberIds, $existingIds));
                $phoneMatchCount += count(array_intersect($tempMemberPhones, $existingPhones));
            }

            $totalScore = ($regScore * 0.6) + ($nameScore * 0.4);

            // If 3+ people have the same ID number, it's definitely the same group
            if ($idMatchCount >= 4) {
                $totalScore = 100; // Hard override - 3 ID matches is the same group
            } elseif ($idMatchCount > 0) {
                $totalScore += ($idMatchCount * 15); // Dynamic boost per member
            }

            // If phone numbers match but IDs didn't (maybe they entered IDs wrong)
            if ($phoneMatchCount >= 2) {
                $totalScore += 20;
            }

            $group->match_reasons = [];
            $reasons = [];

            if ($regScore > 90) {
                $reasons[] = ['icon' => 'fa-id-card', 'text' => 'Registration Number Match', 'class' => 'text-success'];
            } elseif ($regScore > 50) {
                $reasons[] = ['icon' => 'fa-id-card', 'text' => 'Partial Reg. Match', 'class' => 'text-warning'];
            }

            if ($nameScore > 80) {
                $reasons[] = ['icon' => 'fa-users', 'text' => 'Name similarity', 'class' => 'text-success'];
            }

            if ($idMatchCount > 0) {
                $reasons[] = ['icon' => 'fa-fingerprint', 'text' => "$idMatchCount Member(s) ID Match", 'class' => 'text-info'];
            }

            if ($phoneMatchCount > 0) {
                $reasons[] = ['icon' => 'fa-phone', 'text' => "$phoneMatchCount Phone Number Match", 'class' => 'text-primary'];
            }

            $finalScore = min(100, $totalScore);

            if (empty($reasons)) {
                $reasons[] = [
                    'icon' => 'fa-search',
                    'text' => 'General Name Similarity',
                    'class' => 'text-muted'
                ];
            }



            // Threshold: Only return if score is above 50%
            if ($finalScore >= 45) {
                $group->match_score = $finalScore;
                $group->phone_matched = $phoneMatch;
                $group->id_matches = $idMatchCount;
                $group->reg_matched = ($regScore > 90);
                $group->match_reasons = $reasons;
                return $group;
            }

            return null;
        })->filter()->values()->sortByDesc('match_score');
    }

    private function calculateSimilarity(?string $str1, ?string $str2): float
    {
        $s1 = substr($this->normalizeString($str1), 0, 255);
        $s2 = substr($this->normalizeString($str2), 0, 255);

        // dd($str1, $str2, $s1, $s2);

        if (empty($s1) || empty($s2)) return 0;
        if ($s1 === $s2) return 100;

        $lev = levenshtein($s1, $s2);
        $maxLen = max(strlen($s1), strlen($s2));
        $levScore = (1 - ($lev / $maxLen)) * 100;

        similar_text($s1, $s2, $simScore);
        return max($levScore, $simScore);
    }

    public function manualSearchGroups(TempApplication $application, string $term): Collection
    {
        $const_code = $application->constituency_code;

        return Group::whereHas('ward.constituency', function ($query) use ($const_code) {
                $query->where('constituency_code', $const_code);
            })
            ->where(function($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                ->orWhere('code', 'like', "%{$term}%")
                ->orWhere('reg_number', 'like', "%{$term}%");
            })
            ->with(['ward', 'currentLoan.groupMembers.member', 'currentLoan.loanPhase'])
            ->get()
            ->map(function($group) {
                // Give manual matches a fixed 100% "Rank" so they look consistent in your table
                $group->match_score = 100;
                $group->match_reasons = [['icon' => 'fa-search', 'text' => 'Manual Search Match', 'class' => 'text-primary']];
                return $group;
            });
    }

    // generate gfscode
    public function generateGfsCode($application, $constituency)
    {
        $maxGroupCode = Group::whereHas('ward', function($query) use ($constituency){
                $query->where('constituency_code', $constituency->constituency_code);
            })
            ->max(DB::raw('CAST(code AS UNSIGNED)')) ?? 0;

        $maxPhaseCode = PhaseApplication::whereHas('reviewPhase', function($query) use ($constituency){
                $query->where('constituency_code', $constituency->constituency_code);
            })
            ->max(DB::raw('CAST(group_code AS UNSIGNED)')) ?? 0;

        $maxCode = max($maxGroupCode, $maxPhaseCode);

        $nextNumber = ($maxCode) ? $maxCode + 1 : 1;

        $groupCode = str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        $county_code = $constituency->county->county_code;
        $constituency_code = $constituency->constituency_code;

        $categories = ['Youth' => '1', 'Women' => '2', 'PWD' => '3'];
        $sectors = ['Trade' => '1', 'Service' => '2', 'Manufacturing' => '3', 'Agriculture' => '4', 'Other' => '5'];

        $catId = $categories[ucfirst((strtolower($application->nature_of_group)))] ?? '0';
        $secId = $sectors[ucfirst(strtolower($application->business_type[0]))] ?? '0';

        // 5. Build the prefix and attach the padded code
        $gfscode = "4-{$county_code}-{$constituency_code}-411020{$catId}-{$secId}0{$catId}-";

        return [
            'groupCode' => $groupCode,
            'gfscode' => $gfscode,
        ];
    }

    // Get status text
    public function getStatusText($status)
    {
        $texts = [
            'submitted' => 'Submitted',
            'under_constituency_review' => 'Pending Review',
            'pending' => 'Under Review',
            'pending_submission_to_hq' => 'Pending Submission to HQ',
            'pending_hq_approval' => 'Pending HQ Approval',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'disbursed' => 'Disbursed',
            'closed' => 'Closed',
            'in_training' => 'In Training',
        ];
        return $texts[$status] ?? ucfirst($status);
    }

    public function getStatusClasses($value): string
    {
        return match ($value) {
            'approved', 'disbursed' => 'bg-green-50 border-green-200 text-green-700',

            'pending', 'pending_submission_to_hq' => 'bg-blue-50 border-blue-200 text-blue-700',

            'pending_hq_approval', 'in_training' => 'bg-amber-50 border-amber-200 text-amber-700',

            'rejected' => 'bg-red-50 border-red-200 text-red-700',

            'submitted' => 'bg-gray-50 border-gray-200 text-gray-700',

            default => 'bg-gray-50 border-gray-200 text-gray-700',
        };
    }

    public function getHqStatusOptionsProperty()
    {
        $statusMap = [
            'submitted'                 => ['label' => 'Submitted'],
            'under_constituency_review' => ['label' => 'Pending Constituency Review'],
            'pending'                   => ['label' => 'Under Constituency Review'],
            'pending_submission_to_hq'  => ['label' => 'Pending Submission to HQ'],
            'pending_hq_approval'       => ['label' => 'Pending HQ Approval'],
            'approved'                  => ['label' => 'Approved'],
            'rejected'                  => ['label' => 'Rejected'],
            'in_training'               => ['label' => 'In Training'],
            'pending_disbursement'      => ['label' => 'Pending Disbursement'],
            'disbursed'                 => ['label' => 'Disbursed'],
        ];

        return collect($statusMap)->map(fn($data, $value) => [
            'value'    => $value,
            'name'  => $data['label'],
        ])->values()->all();
    }

    public function getConsStatusOptionsProperty()
    {
        $statusMap = [
            'submitted'                 => ['label' => 'Submitted'],
            'under_constituency_review' => ['label' => 'Unassigned'],
            'pending'                   => ['label' => 'Under Review'],
            'pending_submission_to_hq'  => ['label' => 'Pending Submission to HQ'],
            'pending_hq_approval'       => ['label' => 'Under HQ Review'],
            'approved'                  => ['label' => 'Approved'],
            'rejected'                  => ['label' => 'Rejected'],
            'in_training'               => ['label' => 'In Training'],
            'pending_disbursement'      => ['label' => 'Pending Disbursement'],
            'disbursed'                 => ['label' => 'Disbursed'],
        ];

        return collect($statusMap)->map(fn($data, $value) => [
            'value'    => $value,
            'name'  => $data['label'],
        ])->values()->all();
    }

    public function rejectApplication($selectedAppId, $idempotencyKey, $data)
    {
        DB::transaction(function() use ($selectedAppId, $idempotencyKey, $data){
            $app = PhaseApplication::with([
                'linkedGroup:id,name,code,identifier',
                'groupLoan:id,group_id',
                'tempApplication:id,application_id'
                ])
                ->findOrFail($selectedAppId, ['id', 'group_id', 'group_loan_id', 'temp_application_id']);

            if ($app->groupLoan) {
                $app->groupLoan->update([
                    'amount_issued' => 0,
                    'comments' => $data['rejectionComment'] ?? '',
                    'loan_status' => 'cancelled',
                    'status' => 'cancelled'
                ]);
            }

            $app->tempApplication->update([
                'status' => 'rejected',
                'lms_to_loan_app_sync_status' => 'pending',
                'updated_at' => now()
            ]);

            $app->update([
                'status' => 'rejected',
                'user_id' => auth()->id(),
                'level' => $data['level'] ?? '',
                'action' => $data['action'] ?? '',
                'rejection_reason' => $data['rejectionKey'],
                'rejection_message' => $data['rejectionComment'],
            ]);

            activity()
                ->performedOn($app)
                ->causedBy(auth()->user())
                ->createdAt(now())
                ->withProperties([
                    'type' => 'application_rejection',
                    'attributes' => [
                        'old_status' => $data['status'],
                        'status' => 'rejected',
                        'rejection_category' => $data['rejectionKey'],
                        'rejection_label' => $data['rejection_label'],
                        'comment' => $data['rejectionComment'],
                        'application_id' => $app->tempApplication->application_id,
                    ],
                    'context' => [
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                    ]
                ])
                ->log("Application rejected due to: " . $data['rejection_label']);

            SyncApplicationStatusToDrupal::dispatch($app->tempApplication->temp_application_id)->afterCommit();

            $this->idempotencyService->release($idempotencyKey);
        });

    }

    public function approveApplicationDisbursement($selectedAppId, $idempotencyKey, $data)
    {
        $notifiableApplicationId = null;

        DB::transaction(function() use ($selectedAppId, $idempotencyKey, $data, &$notifiableApplicationId){
            $app = PhaseApplication::with([
                'linkedGroup:id,name,code,identifier',
                'groupLoan:id,group_id',
                'tempApplication:id,application_id'
                ])
                ->findOrFail($selectedAppId, ['id', 'group_id', 'group_loan_id', 'temp_application_id']);

            if (!$app->linkedGroup->identifier) {
                $app->linkedGroup->update([
                    'identifier' => strtoupper($data['app_group_identifier'])
                ]);

                activity()
                    ->performedOn($app->linkedGroup)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'group' => $app->linkedGroup->name,
                        'old_identifier' => null,
                        'new_identifier' => strtoupper($data['app_group_identifier']),
                        'reason' => 'identifier_was_null'
                    ])
                    ->event('group_identifier_updated')
                    ->log("Group identifier set from Null to '" . strtoupper($data['app_group_identifier']) . "'");
            }

            if ($app->groupLoan) {
                $app->groupLoan->update([
                    'amount_issued' => $data['app_amount_issued'],
                    'cheque_no' => strtoupper($data['app_cheque_number']),
                    'comments' => $data['app_disbursement_comment'] ?? '',
                    'disbursement_date' => now(),
                    'loan_status' => 'financed',
                    'status' => 'active'
                ]);
            }

            $app->update([
                'status' => 'disbursed',
                'amount_issued' => $data['app_amount_issued'],
                'cheque_number' => strtoupper($data['app_cheque_number']),
                'disbursement_date' => now(),
            ]);

            $app->tempApplication->update([
                'status' => 'disbursed',
                'lms_to_loan_app_sync_status' => 'pending',
                'updated_at' => now()
            ]);

            activity()
                ->performedOn($app)
                ->causedBy(auth()->user())
                ->withProperties([
                    'changes' => 'pending disbursement',
                    'disbursement_details' => [
                        'amount' => $data['app_amount_issued'],
                        'cheque_number' => strtoupper($data['app_cheque_number']),
                        'date' => now()->toDateTimeString()
                    ]
                ])
                ->event('application_disbursed')
                ->log("Application status changed from 'Pending Disbursement' to 'disbursed'");

            SyncApplicationStatusToDrupal::dispatch($app->tempApplication->temp_application_id)->afterCommit();

            $notifiableApplicationId = $app->id;

            $this->idempotencyService->release($idempotencyKey);
        });

        if ($notifiableApplicationId) {
            SendDisbursementNotifications::dispatchSync(
                $notifiableApplicationId,
                SendDisbursementNotifications::TYPE_DISBURSED
            );
        }
    }

    /**
     * Mark all trained, pending-disbursement groups in a phase as disbursed.
     *
     * Each group is disbursed at its approved amount using a shared cheque prefix.
     */
    public function disburseEligibleApplicationsInPhase(
        int $phaseId,
        string $chequePrefix,
        ?string $comment = null,
    ): array {
        $applications = PhaseApplication::with([
            'linkedGroup:id,name,code,identifier',
            'tempApplication:id,application_id',
        ])
            ->where('loan_phase_id', $phaseId)
            ->where('is_trained', true)
            ->whereIn('status', ['in_training', 'pending_disbursement'])
            ->orderBy('id')
            ->get();

        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        $normalizedPrefix = strtoupper(trim($chequePrefix));

        foreach ($applications as $application) {
            $applicationId = $application->tempApplication?->application_id ?? (string) $application->id;
            $idempotencyKey = "application_action_{$application->id}";

            if (!$this->idempotencyService->acquire($idempotencyKey, ['action' => 'batch_disburse'])) {
                $results['failed']++;
                $results['errors'][] = "{$applicationId}: already processing";
                continue;
            }

            try {
                $group = $application->linkedGroup;
                if (!$group) {
                    throw new \RuntimeException('Linked group not found');
                }

                $identifier = $group->identifier
                    ?: $this->generateIdentifier($group->name, $group->code);
                $groupCode = strtoupper((string) ($group->code ?: $application->id));
                $chequeNumber = $normalizedPrefix . '-' . $groupCode;

                $this->approveApplicationDisbursement($application->id, $idempotencyKey, [
                    'app_amount_issued' => $application->amount_approved,
                    'app_cheque_number' => $chequeNumber,
                    'app_group_identifier' => $identifier,
                    'app_disbursement_comment' => $comment ?? '',
                ]);

                $results['success']++;
            } catch (\Throwable $e) {
                $this->idempotencyService->release($idempotencyKey);
                $results['failed']++;
                $results['errors'][] = "{$applicationId}: {$e->getMessage()}";
            }
        }

        return $results;
    }

    public function confirmTraining(int $id)
    {
        $application = PhaseApplication::with('tempApplication')->findOrFail($id);

        DB::transaction(function () use ($application) {
            $phaseUpdates = [
                'is_trained' => true,
                'training_completed_at' => now(),
            ];

            if ($application->status === 'in_training') {
                $phaseUpdates['status'] = 'pending_disbursement';
            }

            $application->update($phaseUpdates);

            if ($application->tempApplication) {
                $application->tempApplication->update([
                    'status' => 'pending_disbursement',
                    'lms_to_loan_app_sync_status' => 'pending',
                    'updated_at' => now(),
                ]);

                SyncApplicationStatusToDrupal::dispatch($application->tempApplication->id)->afterCommit();
            }

            activity()
                ->performedOn($application)
                ->causedBy(auth()->user())
                ->log('Application marked as training complete; pending disbursement');

            try {
                app(TrainingCertificateService::class)->generateOnTrainingComplete(
                    $application->fresh(['loanPhase', 'tempApplication'])
                );
            } catch (\Throwable $certificateError) {
                \Illuminate\Support\Facades\Log::warning('Training certificate generation skipped', [
                    'phase_application_id' => $application->id,
                    'error' => $certificateError->getMessage(),
                ]);
            }

            \App\Jobs\SendTrainingCompletionNotifications::dispatchSync($application->id);
        });
    }

    /**
     * Mark every in-training group in a phase as training complete.
     *
     * @return array{success:int, failed:int, errors:list<string>}
     */
    public function confirmAllTrainingInPhase(int $phaseId): array
    {
        $applications = PhaseApplication::query()
            ->where('loan_phase_id', $phaseId)
            ->where('status', 'in_training')
            ->where('is_trained', false)
            ->orderBy('id')
            ->pluck('id');

        $results = ['success' => 0, 'failed' => 0, 'errors' => []];

        foreach ($applications as $applicationId) {
            try {
                $this->confirmTraining($applicationId);
                $results['success']++;
            } catch (\Throwable $e) {
                $results['failed']++;
                $results['errors'][] = "Application {$applicationId}: {$e->getMessage()}";
            }
        }

        return $results;
    }

    public function recordDisbursementFailure(int $selectedAppId, string $idempotencyKey, array $data): void
    {
        $notifiableApplicationId = null;

        DB::transaction(function () use ($selectedAppId, $idempotencyKey, $data, &$notifiableApplicationId) {
            $app = PhaseApplication::with('tempApplication:id,application_id')
                ->findOrFail($selectedAppId);

            $app->update([
                'status' => 'disbursement_failed',
                'disbursement_failure_reason' => $data['failure_reason'],
                'disbursement_failure_notes' => $data['failure_notes'] ?? null,
            ]);

            if ($app->tempApplication) {
                $app->tempApplication->update([
                    'status' => 'disbursement_failed',
                    'lms_to_loan_app_sync_status' => 'pending',
                    'updated_at' => now(),
                ]);

                SyncApplicationStatusToDrupal::dispatch($app->tempApplication->id)->afterCommit();
            }

            activity()
                ->performedOn($app)
                ->causedBy(auth()->user())
                ->withProperties([
                    'failure_reason' => $data['failure_reason'],
                    'failure_notes' => $data['failure_notes'] ?? null,
                ])
                ->event('disbursement_not_completed')
                ->log('Constituency officer marked disbursement as not completed');

            $notifiableApplicationId = $app->id;

            $this->idempotencyService->release($idempotencyKey);
        });

        if ($notifiableApplicationId) {
            SendDisbursementNotifications::dispatchSync(
                $notifiableApplicationId,
                SendDisbursementNotifications::TYPE_NOT_DISBURSED
            );
        }
    }

    public function generateIdentifier(string $name, string $code): string
    {
        $letters = preg_replace('/[^A-Z]/', '', strtoupper($name));

        if (strlen($letters) >= 3) {
            $initials = substr($letters, 0, 3);
        } else {
            $words = preg_split('/\s+/', trim($name));
            $initials = '';

            foreach ($words as $word) {
                if (empty($word)) continue;

                $firstLetter = preg_filter('/[^A-Z]/', '', strtoupper(substr($word, 0, 1)));
                if (!empty($firstLetter)) {
                    $initials .= $firstLetter;
                }

                if (strlen($initials) >= 3) break;
            }

            $initials = substr($initials, 0, 3);

            while (strlen($initials) < 3) {
                $initials .= $this->getRandomLetter();
            }
        }

        return $initials . $code;
    }

    protected function getRandomLetter(): string
    {
        return chr(random_int(65, 90));
    }

    public function forgetApplication(string $applicationId): void
    {
        $this->appCache->forgetApplication($applicationId);
    }
}
