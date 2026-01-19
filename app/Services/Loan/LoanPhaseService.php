<?php

namespace App\Services\Loan;

use App\Models\LoanPhase;
use App\Models\PhaseApplication;
use App\Models\TempApplication;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LoanPhaseService
{
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
                ->select(['id', 'group_name', 'loan_amount', 'group_registration_number'])
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

                activity('phase-assignment')
                    ->performedOn($phase)
                    ->withProperties(['added' => count($dataToInsert), 'errors' => $errors])
                    ->log("Assigned applications with " . count($errors) . " skips.");
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

        DB::transaction(function () use ($phaseApplication, $phase) {
            $phaseApplication->delete();
            $phase->calculateStats();

            $app = TempApplication::findOrFail($phaseApplication->temp_application_id);

            if ($app) {
                $app->update([
                    'link_status' => 'not_attempted',
                ]);
            }
        });


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

    public function getAllPhasesForConstituency(string $constituencyCode)
    {
        return LoanPhase::where('constituency_code', $constituencyCode)
            ->select('id', 'phase', 'status', 'slug')
            ->orderBy('created_at', 'desc')
            ->get();
    }


    public function getDetailedPhases(string $constituencyCode, ?string $search = null, int $perPage = 15, ?string $status = 'draft', int $page = 1)
    {
        $phasesTag = config('cache.tags.phases.constituency_detailed') . $constituencyCode;
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

    public function getDetailedPhase($phase, int $perPage = 15, array $filters = [], int $page = 1): array
    {
        $phaseId = ($phase instanceof LoanPhase) ? $phase->id : $phase;

        $phaseTag = config('cache.tags.phases.individual_detailed');

        if (empty($filters)) {
            $cacheKey = "phase_details_{$phaseId}_page_{$page}";
            return Cache::tags([$phaseTag])->remember($cacheKey, now()->addMinutes(15), function () use ($phase, $perPage) {
                return $this->fetchPhaseFromDb($phase, $perPage);
            });
        }

        return $this->fetchPhaseFromDb($phase, $perPage, $filters);
    }

    public function fetchPhaseFromDb($phase, int $perPage = 15, array $filters = [], ) : array
    {
        if (!$phase instanceof LoanPhase) {
            $phase = LoanPhase::findOrFail($phase);
        }

        $phase->loadExists(['phaseApplicationDocuments as has_minutes' => function ($query) {
            $query->where('document_type', 'minutes');
        }]);

        $phase->loadCount([
            'phaseApplications as total_applications',
            'phaseApplications as pending_count'   => fn($q) => $q->where('status', 'pending'),
            'phaseApplications as pending_submission_to_hq_count'  => fn($q) => $q->where('status', 'pending_submission_to_hq'),
            'phaseApplications as pending_hq_approval_count'  => fn($q) => $q->where('status', 'pending_hq_approval'),
            'phaseApplications as rejected_count'  => fn($q) => $q->where('status', 'rejected'),
            'phaseApplications as approved_count'   => fn($q) => $q->where('status', 'approved'),
            'phaseApplications as disbursed_count'  => fn($q) => $q->where('status', 'disbursed'),
        ]);

        $phase->loadSum([
            'phaseApplications as total_requested' => fn($q) => $q
        ], 'amount_applied');

        // Summing the 'Approved' column (different column name)
        $phase->loadSum([
            'phaseApplications as total_approved' => fn($q) => $q->where('status', '!=', 'pending')
        ], 'amount_approved');

        $phase->load(['phaseApplicationDocuments' => fn($q) => $q->select(
            'id',
            'loan_phase_id',
            'title',
            'document_type',
            'original_filename',
            'filename',
            'path',
            'size',
            'mime_type',
            'created_at')
            ->latest()]);

        $applicationsQuery = $phase->phaseApplications()
            ->select([
                'phase_applications.id',
                'phase_applications.temp_application_id',
                'phase_applications.status',
                'phase_applications.status',
                'phase_applications.gfs_code',
                'phase_applications.amount_approved',
                'phase_applications.updated_at',
            ])
            ->join('temp_applications', 'phase_applications.temp_application_id', '=', 'temp_applications.id')
            ->with([
                'tempApplication' => function ($q) {
                    $q->select([
                        'temp_applications.id',
                        'temp_applications.application_id',
                        'temp_applications.group_name',
                        'temp_applications.ward_id',
                        'temp_applications.loan_amount',
                        'temp_applications.risk_level',
                        'temp_applications.status',
                        'temp_applications.loan_type',
                        'temp_applications.nature_of_group',
                        'temp_applications.business_type'
                    ]);
                }
            ])
            ->latest('phase_applications.updated_at');

        // filters
        if (!empty($filters['status'])) {
            $applicationsQuery->where('phase_applications.status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $applicationsQuery->where(function($q) use ($filters) {
                $q->where('temp_applications.group_name', 'like', "%{$filters['search']}%")
                ->orWhere('temp_applications.application_id', 'like', "%{$filters['search']}%");
            });
        }

        if (!empty($filters['risk'])) {
            $applicationsQuery->where('temp_applications.risk_level', 'like', "%{$filters['risk']}%");
        }

        $applications = $applicationsQuery->latest('phase_applications.updated_at')->paginate($perPage);

        return [
            'phase' => $phase,
            'applications' => $applications,
        ];

    }



    /**
     * Get detailed phases for a constituency with pre-calculated aggregates.
     *
     * @param string $constituencyCode
     * @param string|null $search Optional search term
     * @param int $perPage Pagination size (default 15)
     * @return \Illuminate\Pagination\LengthAwarePaginator
    */

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

    /**
     * Get summary statistics for a constituency
     *
     * @param string $constituencyCode
     * @return array
    */
    public function getPhasesSummary(string $constituencyCode): array
    {
        $summaryTag = config('cache.tags.phases.constituency_summary') . $constituencyCode;
        $cacheKey = "phases_summary_{$constituencyCode}";

        return Cache::tags([$summaryTag])->remember($cacheKey, now()->addMinutes(10), function () use ($constituencyCode) {
            $phases = LoanPhase::where('constituency_code', $constituencyCode)->get();

            return [
                'total_phases' => $phases->count(),
                'pending_submission' => $phases->where('status', 'draft')->count(),
                'processing_hq' => $phases->where('status', 'submitted_to_hq')->count(),
                'approved_ready' => $phases->where('status', 'approved')->count(),
                'declined' => $phases->where('status', 'rejected')->count(),
                'disbursed' => $phases->where('status', 'disbursed')->count(),
            ];
        });
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

    public function invalidatePhasesCache(string $constituencyCode): void
    {
        $phases_detailed = config('cache.tags.phases.constituency_detailed') . $constituencyCode;
        $phases_summary = config('cache.tags.phases.constituency_summary') . $constituencyCode;

        // Clear all cache entries associated with this tags
        Cache::tags([$phases_detailed])->flush();
        Cache::tags([$phases_summary])->flush();
    }

    public function uploadPhaseMinutes($phase, $minutesFile) : void
    {
        if (!$phase instanceof LoanPhase) {
            $phase = LoanPhase::with(['phaseApplicationDocuments'])->findOrFail($phase);
        }

        // if the file size is greater than 2MB, throw error
        if ($minutesFile->getSize() > 2 * 1024 * 1024) {
            throw ValidationException::withMessages([
                'minutesFile' => 'The minutes file must not exceed 2MB in size.',
            ]);
        }

        $directory = "phases/{$phase->slug}";
        $filename = "vetting-minutes-" . time() . "." . $minutesFile->getClientOriginalExtension();

        $path = $minutesFile->storeAs($directory, $filename, 'public');

        DB::beginTransaction();

        try {
            $existingMinutes = $phase->phaseApplicationDocuments()
                ->where('document_type', 'minutes')
                ->first();

            if ($existingMinutes) {
                Storage::disk('public')->delete($existingMinutes->path);
                $existingMinutes->delete();
            }
            $phase->phaseApplicationDocuments()->create([
                'loan_phase_id'          => $phase->id, // Added based on our new migration
                'title'             => 'Signed Committee Minutes',
                'document_type'     => 'minutes',
                'original_filename' => $minutesFile->getClientOriginalName(),
                'filename'          => $filename,
                'path'              => $path,
                'mime_type'         => $minutesFile->getMimeType(),
                'size'              => $minutesFile->getSize(),
                'uploaded_by'       => auth()->id(),
            ]);

            DB::commit();

        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error("Failed to upload phase minutes: " . $th->getMessage());

            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
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

        $path = $file->storeAs($directory, $filename, 'public');

        DB::beginTransaction();

        try {
            $phase->phaseApplicationDocuments()->create([
                'loan_phase_id'          => $phase->id, // Added based on our new migration
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

            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }

            throw $th;
        }
    }

    public function deleteApplicationPhaseDocument($documentId) : void
    {
        $document = \App\Models\PhaseApplicationDocument::findOrFail($documentId);

        DB::beginTransaction();

        try {
            Storage::disk('public')->delete($document->path);
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
            ->findOrFail($id);
    }

    public function invalidatePhaseCache(): void
    {
        $phase_detaileds = config('cache.tags.phases.individual_detailed');
        Cache::tags([$phase_detaileds])->flush();
    }
}
