<?php

namespace App\Services\Loan;

use App\Models\TempApplication;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use App\Models\Group;
use Illuminate\Support\Collection;
use App\Models\GroupLoan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Loan\DefaultAnalysisService;
use App\Models\PhaseApplication;

class TempApplicationService
{
    protected $analysisService;

    // Laravel automatically resolves this for you
    public function __construct(DefaultAnalysisService $analysisService)
    {
        $this->analysisService = $analysisService;
    }

    /**
     * Invalidate cache when application is updated/assigned/etc.
    */
    public function clearCache(string $applicationId, string $constituencyCode): void
    {
        Cache::forget("temp_app_detail_{$applicationId}_{$constituencyCode}");
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
    public function invalidateApplicationCache(TempApplication $application): void
    {
        $applicationId = $application->application_id;
        $constituencyCode = $application->constituency_code;
        $groupId = $application->group_id;

        Cache::forget("temp_app_{$applicationId}_{$constituencyCode}");
        Cache::forget("temp_app_detail_{$applicationId}_{$constituencyCode}");
        cache::forget("temp_app_default_members_{$applicationId}_{$constituencyCode}");
        cache::forget("temp_app_linked_group_{$groupId}");
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
    public function getStats(string $constituencyCode): array
    {
        return Cache::remember("temp_app_stats_{$constituencyCode}", now()->addMinutes(10),
            function () use ($constituencyCode) {
                $appStats = DB::table('temp_applications')
                    ->leftJoin('phase_applications', 'temp_applications.id', '=', 'phase_applications.temp_application_id')
                    ->where('temp_applications.constituency_code', $constituencyCode)
                    ->selectRaw("
                        COUNT(temp_applications.id) as total,
                        SUM(temp_applications.loan_amount) as total_amount,
                        COUNT(phase_applications.id) as in_phase,
                        SUM(CASE WHEN phase_applications.status = 'pending' THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN phase_applications.status = 'approved' THEN 1 ELSE 0 END) as approved,
                        SUM(CASE WHEN phase_applications.status = 'rejected' THEN 1 ELSE 0 END) as rejected
                    ")
                    ->first();

                $memberStats = DB::table('temp_group_members')
                    ->join('temp_applications', 'temp_group_members.temp_application_id', '=', 'temp_applications.id')
                    ->where('temp_applications.constituency_code', $constituencyCode)
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
                    'rejected'     => (int)$appStats->rejected,

                    // Member Stats
                    'total_males'   => (int)$memberStats->total_males,
                    'total_females' => (int)$memberStats->total_females,
                    'total_pwds'    => (int)$memberStats->total_pwds,
                ];
            }
        );
    }

    /**
     * Build the filtered query (reusable, eager-loaded)
    */
    public function getFilteredQuery(string $constituencyCode, array $filters = []): Builder
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
            ->where('constituency_code', $constituencyCode)
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
            ->when($filters['date_from'] ?? null, fn($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($filters['date_to'] ?? null, fn($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->with([
                'phaseApplication:id,temp_application_id,loan_phase_id,status',
                'phaseApplication.reviewPhase:id,phase,status,slug' // Load only needed columns
            ]);
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
        $cacheKey = "temp_app_{$applicationId}_{$constituencyCode}";

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($applicationId, $constituencyCode) {
            return TempApplication::where('application_id', $applicationId)
                ->where('constituency_code', $constituencyCode) // Security check
                ->with([
                    'groupMembers',
                    'expenses',
                    'files',
                    'user',
                ])
                ->firstOrFail();
        });
    }

    /**
     * Get a single application with all important relations pre-loaded
    */
    public function getDetailedApplication(string $applicationId, string $constituencyCode): TempApplication
    {
        $cacheKey = "temp_app_detail_{$applicationId}_{$constituencyCode}";

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($applicationId, $constituencyCode) {
            $application = TempApplication::query()
                ->where('application_id', $applicationId)
                ->where('constituency_code', $constituencyCode)
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
                            ->with('groupLoans', 'groupLoans.loanReceipts', 'currentLoan');
                    },
                    'ward',
                ])
                ->firstOrFail();

            // Optional: append useful computed data
            $application->append(['current_phase', 'approval_progress']);
            return $application;
        });
    }

    private function loadDefaultMembers(TempApplication $application): Collection
    {
        if ($application->groupMembers->isEmpty()) {
            return Collect();
        }

        // Cross-default members (optimized: only check application members)
        $appMemberIds = $application->groupMembers->pluck('id_number')->toArray();

        $defaulterData =  $this->analysisService->getApplicationDefaulterMembers(
            $appMemberIds,
            $application->application_id,
            $application->group_id,
            $application->constituency_code
        )->keyBy('member_Id');

        $app = $application->groupMembers->transform(function ($appMember) use ($defaulterData) {
            // Find if this specific member has a default record
            $history = $defaulterData->get($appMember->id_number);

            // Attach comprehensive data to the object
            $appMember->is_defaulter = !is_null($history);

            $appMember->default_history = $history ? $history->groupMembers->filter(function ($gm) {
                // Ensure groupLoan exists and is_defaulted is true
                return $gm->groupLoan && $gm->groupLoan->is_defaulted === 1;
            }) : collect();

            return $appMember;
        });

        return $app;
    }

    /**
     * Enrich application with default member data
     */
    public function getEnrichedApplication(string $applicationId, string $constituencyCode): TempApplication
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

            // Invalidate cache
            $this->invalidateApplicationCache($application);
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

            $this->invalidateApplicationCache($application);
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
        if (is_null($application->phaseApplication === null)){
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

        // dd($group);

        DB::transaction(function () use ($application, $group) {
            $application->phaseApplication->update([
                'group_id' => $group->id,
                'group_code' => $group->code,
                'loan_level' => $group->currentLoan->loan_level+1,
            ]);

            $application->update([
                'link_status' => 'linked',
            ]);
        });
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
            ]);
        });

    }


    /**
     * Process expenses into clean array format
    */
    public function getProcessedExpenses(TempApplication $application): array
    {
        $expenses = $application->expenses;

        // Handle JSON string
        if (is_string($expenses)) {
            $expenses = json_decode($expenses, true);
        }

        $processed = [];

        if (is_array($expenses)) {
            // Case 1: [{"cost_of_goods": 0, "rent": 1000}]
            if (isset($expenses[0]) && is_array($expenses[0])) {
                foreach ($expenses[0] as $name => $amount) {
                    if ($amount > 0) {
                        $processed[] = [
                            'name'   => ucfirst(str_replace('_', ' ', $name)),
                            'amount' => $amount
                        ];
                    }
                }
            }
            // Case 2: [{"name": "Rent", "amount": 1000}]
            else {
                foreach ($expenses as $expense) {
                    if (is_array($expense)) {
                        $processed[] = [
                            'name'   => $expense['name'] ?? 'Expense',
                            'amount' => $expense['amount'] ?? 0
                        ];
                    }
                }
            }
        }

        return $processed;
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
            'f.f.s' => '', 'ffs' => '', 'farmer field school' => '',
            'c.b.o' => '', 'cbo' => '', 'community based organization' => '',
        ];

        $str = str_replace(array_keys($replacements), array_values($replacements), $str);

        // Remove all non-alphanumeric characters (removes spaces, dots, slashes)
        return preg_replace('/[^a-z0-9]/', '', $str);
    }

    public function findMatchingGroups(TempApplication $application): Collection
    {
        $const_code = $application->constituency_code;

        $groups = Group::select(['id', 'ward_id', 'reg_number', 'name', 'code'])->whereHas('ward.constituency', function ($query) use ($const_code) {
                $query->where('constituency_code', $const_code);
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

                $existingIds = $existingMembers->pluck('member_Id')->filter()->toArray();
                $existingPhones = $existingMembers->map(fn($m) => $this->normalizePhone($m->number))
                    ->filter()
                    ->toArray();
                // dd($tempMemberIds, $existingIds, $existingMembers, $group->currentLoan);

                $idMatchCount += count(array_intersect($tempMemberIds, $existingIds));
                $phoneMatchCount += count(array_intersect($tempMemberPhones, $existingPhones));
            }

            /**
             * 3. WEIGHTED SCORING LOGIC
             * We give Reg Number 60% weight and Name 40% weight.
             * Exact Reg match + Phone match = Almost 100%
             */
            $totalScore = ($regScore * 0.6) + ($nameScore * 0.4);

            // 4. IDENTITY BOOSTS
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
        })->filter()->sortByDesc('match_score');
    }

    private function calculateSimilarity(?string $str1, ?string $str2): float
    {
        $s1 = $this->normalizeString($str1);
        $s2 = $this->normalizeString($str2);

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

        // 2. Increment
        $nextNumber = ($maxCode) ? $maxCode + 1 : 1;

        // 3. Format with leading zeros (001, 002, etc.)
        $groupCode = str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        // 4. Mapping for the GFS String
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
            'under_review' => 'Under Review',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'disbursed' => 'Disbursed',
            'closed' => 'Closed',
            'in_training' => 'In Training',
            'pending_submission_to_hq' => 'Pending Submission to HQ',
            'pending_hq_approval' => 'Pending HQ Approval',
        ];
        return $texts[$status] ?? ucfirst($status);
    }
}
