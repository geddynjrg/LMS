<?php

namespace App\Livewire\Hq;

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Attributes\Computed;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Services\Loan\LoanPhaseService;
use App\Services\Loan\TempApplicationService;
use App\Services\Loan\LoanAnalysisService;
use App\Services\Loan\DefaultAnalysisService;
use App\Models\TempApplication;
use App\Models\PhaseApplication;
use App\Exports\ApplicationInstitutionalDataExport;
use App\Jobs\SyncApplicationStatusToDrupal;
use App\Services\WorkflowNotificationService;

class ApplicationView extends Component
{
    protected LoanPhaseService $phaseService;
    protected TempApplicationService $appService;
    protected DefaultAnalysisService $defaultService;
    protected LoanAnalysisService $analysisService;

    public $applicationId;

    // next application summary
    public $nextSummary = null;
    public $phaseCompleted = false;
    public $submissionUrl;

    public $selectedRejectionKey;
    public $rejectionComment;
    public $rejectionReasons = [
        'incomplete_information' => 'Incomplete or incorrect information',
        'missing_documents'      => 'Required documents missing or invalid',
        'eligibility_criteria'   => 'Does not meet eligibility criteria',
        'duplicate_application'  => 'Duplicate application',
        'insufficient_collateral'=> 'Insufficient collateral',
        'poor_credit_history'    => 'Poor credit history',
        'member_declined'        => 'Group member(s) declined participation',
        'other'                  => 'Other reason',
    ];

    #[Url(except: 'all-details')]
    public $activeTab = 'all-details';

    public $selectedMember;

    public function boot(LoanPhaseService $phaseService, LoanAnalysisService $analysisService, TempApplicationService $appService, DefaultAnalysisService $defaultService)
    {
        $this->phaseService = $phaseService;
        $this->appService = $appService;
        $this->defaultService = $defaultService;
        $this->analysisService = $analysisService;
    }

    public function mount($applicationId)
    {
        $this->applicationId = $applicationId;
    }

    #[Computed]
    public function application()
    {
        // $cacheKey = "app_enrichment_{$this->applicationId}";

        // return Cache::remember($cacheKey, now()->addMinutes(2), function () {

        // });

        try {
            return $this->appService->getEnrichedApplication(
                $this->applicationId
            );
        } catch (\Throwable $th) {
            abort(404, 'Application not found.');
        }
    }

    #[Computed]
    public function processedExpenses()
    {
        return $this->appService->getProcessedExpenses($this->application);
    }

    #[Computed]
    public function riskCategory(): string
    {
        return $this->riskAssessmentData['overall_category'] ?? 'Low Risk';
    }

    #[Computed]
    public function riskFlags()
    {
        return $this->riskAssessmentData['reasons'] ?? [];
    }

    #[Computed]
    public function riskAssessmentData(): array
    {
        return (array) $this->defaultService->calculateGroupRiskLevel(
            $this->application->phaseApplication?->linkedGroup,
            $this->application->groupMembers
        );
    }

    #[Computed]
    public function processedFiles()
    {
        return $this->appService->getHqProcessedFiles(
            $this->application,
        );
    }

    public function viewMember($memberId)
    {
        // Find the member within the already loaded collection
        $this->selectedMember = $this->application->groupMembers
            ->where('id', $memberId)
            ->first();

        // dd($this->selectedMember);
        // 2. Dispatch the event to open the modal
        $this->dispatch('open-modal', name: 'memberModal');
    }

    public function refreshData()
    {
        $applicationId = $this->applicationId;

        Cache::forget("app_enrichment_{$applicationId}");

        unset($this->application);

        $this->appService->invalidateApplicationCache($applicationId, null);

        $this->dispatch('refreshAppData');
    }

    public function getMemberConflictCountProperty()
    {
        if (!$this->application || !$this->application->groupMembers) {
            return 0;
        }
        return $this->application->groupMembers->filter(
            fn ($member) => $member->is_defaulter || ($member->has_other_group_loan ?? false)
        )->count();
    }

    public function getMemberFileData($data, $key = 'url')
    {
        if (empty($data)) {
            return null;
        }

        $decoded = is_array($data) ? $data : json_decode($data, true);

        return $decoded[$key] ?? null;
    }

    public function getStatusOptionsProperty()
    {
        $statusMap = [
            'submitted'                 => ['label' => 'Submitted'],
            'under_constituency_review' => ['label' => 'Pending Constituency Review'],
            'pending'                   => ['label' => 'Under Constituency Review'],
            'pending_submission_to_hq'  => ['label' => 'Pending Submission to HQ'],
            'pending_hq_approval'       => ['label' => 'Pending HQ Approval'],
            'approved'                  => ['label' => 'Approved',],
            'rejected'                  => ['label' => 'Rejected',],
            'disbursed'                 => ['label' => 'Disbursed',],
            'in_training'               => ['label' => 'In Training',]
        ];

        return collect($statusMap)->map(fn($data, $value) => [
            'value'    => $value,
            'name'  => $data['label'],
        ])->values()->all();
    }

    public function exportInstitutionalInfo(){
        try {
            return Excel::download(new ApplicationInstitutionalDataExport($this->application, $this->application->constituency), $this->application->group_name.'_institutional_information.xlsx');
        } catch (\Throwable $th) {
            $this->dispatch('swal:modal', [
                'type'  => 'error',
                'title' => "Invalid Action",
                'text'  => $th->getMessage(),
            ]);
        }
    }

    public function getStatusLabel($value)
    {
        return collect($this->statusOptions)->firstWhere('value', $value)['name'] ?? $value;
    }

    public function getStatusBadgeText($status)
    {
        return $this->appService->getStatusText($status);
    }

    public function getStatusClasses($status)
    {
        return $this->appService->getStatusClasses($status);
    }

    public function getFormattedRejectionReasonsProperty()
    {
        return collect($this->rejectionReasons)->map(function ($label, $key) {
            return [
                'id' => $key,
                'name' => $label,
                'color' => '#9f646a'
            ];
        })->values()->toArray();
    }

    public function rejectApplication()
    {
        $this->reset(['selectedRejectionKey', 'rejectionComment']);

        $this->resetErrorBag();

        $this->dispatch('open-modal', name: 'modal-reject-application');
    }

    public function confirmRejectApplication()
    {
        $this->validate([
            'selectedRejectionKey' => 'required',
            'rejectionComment' => [
                'required',
                'string',
                'min:20',
                'max:500',
                function ($attribute, $value, $fail) {
                    $cleanValue = strtolower(str_replace(' ', '', $value));
                    if (preg_match('/(.)\1{3,}/', $cleanValue)) {
                        $fail('The explanation contains too many repetitive characters. Please provide a real reason.');
                    }

                    $uniqueChars = count(count_chars($cleanValue, 1));
                    if ($uniqueChars < 6) {
                        $fail('This reason looks too generic. Please provide more specific details.');
                    }
                    $words = explode(' ', strtolower(trim($value)));
                    $uniqueWords = array_unique($words);
                    if (count($words) > 4 && (count($uniqueWords) / count($words)) < 0.5) {
                        $fail('Please provide a constructive sentence rather than repeating words.');
                    }
                },
            ],
        ], [
            'selectedRejectionKey.required' => 'Please select a primary category for this rejection.',
            'rejectionComment.required' => 'You must provide a detailed explanation for the applicant.',
            'rejectionComment.min' => 'The explanation is too short. Please provide at least 20 characters of detail.',
        ]);

        $reasonLabel = $this->rejectionReasons[$this->selectedRejectionKey] ?? 'Unknown';

        $debugData = [
            'application_id' => $this->application->id,
            'selected_key'   => $this->selectedRejectionKey,
            'reason_label'   => $reasonLabel,
            'comment'        => $this->rejectionComment,
            'full_payload'   => "[{$reasonLabel}] : {$this->rejectionComment}",
            'timestamp'      => now()->toDateTimeString(),
        ];

        try {
            $phaseApplication = null;

            $result = DB::transaction(function () use (&$phaseApplication) {

                $phaseApplication = PhaseApplication::where('temp_application_id', $this->application->id)->first();

                if ($phaseApplication) {
                    $phaseApplication->update([
                        'status' => 'rejected',
                        'user_id' => auth()->id(),
                        'level' => 'hq',
                        'action' => 'declined_at_hq',
                        'rejection_reason' => $this->selectedRejectionKey,
                        'rejection_message' => $this->rejectionComment,
                    ]);

                    $this->application->update([
                        'status' => 'rejected',
                        'lms_to_loan_app_sync_status'  => 'pending',
                    ]);

                    activity()
                        ->performedOn($this->application->phaseApplication) // The specific phase record
                        ->causedBy(auth()->user())
                        ->createdAt(now())
                        ->withProperties([
                            'type' => 'application_rejection',
                            'attributes' => [
                                'status' => 'rejected',
                                'rejection_category' => $this->selectedRejectionKey, // e.g., 'missing_documents'
                                'rejection_label' => $this->rejectionReasons[$this->selectedRejectionKey], // e.g., 'Required documents missing'
                                'comment' => $this->rejectionComment,
                                'application_number' => $this->application->application_number,
                            ],
                            'context' => [
                                'ip_address' => request()->ip(),
                                'user_agent' => request()->userAgent(),
                            ]
                        ])
                        ->log("Application rejected due to: " . $this->rejectionReasons[$this->selectedRejectionKey]);

                    SyncApplicationStatusToDrupal::pushAfterCommit($this->application->id);

                    return true;

                }

                return false;
            });
        } catch (Exception $e) {
            $this->dispatch('swal:toast',
                title: 'Invalid Action 😕',
                message: 'Internal Server Error.',
                type: 'error'
            );
            return false;
        }

        if ($result){
            if ($phaseApplication) {
                app(WorkflowNotificationService::class)->notifyConstituencyOfficersHqApplicationAction(
                    $phaseApplication->fresh(['tempApplication', 'loanPhase.constituency.county']),
                    'rejected',
                    "Reason: {$reasonLabel}. {$this->rejectionComment}",
                );
            }

            $this->reset(['selectedRejectionKey', 'rejectionComment']);

            $this->resetErrorBag();

            $this->dispatch('close-modal', name: 'modal-reject-application');

            $this->notify('success', 'Rejection', 'Application has been declined.');

            $this->refreshData();

            $this->navigateToNextAfterReview();


        } else {

            $this->notify('error', 'Invalid Application', 'ERROR: You have no access to this application.');
            return;
        }
    }

    private function navigateToNextAfterReview()
    {
        $nextApp = $this->getNext();

        if ($nextApp) {
            $this->phaseCompleted = false;

            $this->nextSummary = [
                'group_name' => $nextApp->tempApplication->group_name ?? 'N/A',
                'app_id' => $nextApp->tempApplication->application_id ?? 'N/A',
                'amount' => number_format($nextApp->loan_amount ?? 0),
                'url' => route('hq.submissions.app.view', [
                    'applicationId' => $nextApp->tempApplication->application_id
                ])
            ];
        } else {
            $this->phaseCompleted = true;
            $this->nextSummary = null;
            $this->submissionUrl = route('hq.submissions.tasks.phases');
        }

        $this->dispatch('open-modal', name: 'modal-next-step');
    }

    public function notify($type, $title, $message)
    {
        $this->dispatch('swal:toast', [
            'type'    => $type, // 'success', 'error', 'warning', 'info'
            'title'   => $title,
            'message' => $message,
        ]);
    }

    private function getNext()
    {
        return PhaseApplication::select(['id', 'loan_phase_id', 'temp_application_id', 'status', 'amount_applied'])
            ->with(['tempApplication' => function ($query) {
                $query->select(['id', 'group_name', 'application_id']); // Include PK/FK for the link to work
            }])
            ->where('loan_phase_id', $this->application->phaseApplication->loan_phase_id)
            ->where('status', 'pending_hq_approval')
            ->where('id', '!=', $this->application->phaseApplication->id)
            ->first();
    }

    public function getMemberIdViewUrl($path)
    {
        if (empty($path)) {
            return null;
        }

        if (is_array($path)) {
            $path = $path['path'] ?? $path['url'] ?? null;
        }

        if (empty($path)) {
            return null;
        }

        $path = str_replace('storage/storage/', 'storage/', $path);
        $path = str_replace('storage/app/public/', '', $path);
        $path = preg_replace('#^/?storage/#', '', $path);
        $path = ltrim($path, '/');

        $encodedPath = base64_encode($path);

        return route('hq.submissions.app.file.member.download', [
                'applicationId' => $this->application->application_id,
                'action' => 'view',
            ]) . '?path=' . $encodedPath;
    }

    public function render()
    {
        return view('livewire.hq.application-view');
    }
}
