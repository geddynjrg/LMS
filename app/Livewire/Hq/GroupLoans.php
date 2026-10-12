<?php

namespace App\Livewire\Hq;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\GroupLoan;
use App\Models\HistoricalCorrectionRequest;
use App\Services\Group\GroupDetailEditService;
use App\Services\Historical\HistoricalCorrectionService;
use Illuminate\Validation\ValidationException;
use App\Services\Group\GroupsService;
use App\Services\Loan\LoanPhaseService;
use App\Services\Cache\AppCacheService;
use App\Services\LocationService;

class GroupLoans extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    protected GroupsService $groupsService;
    protected LoanPhaseService $loanPhaseService;
    protected AppCacheService $cacheService;
    protected LocationService $locationService;

    public $search;
    public $dateFrom;
    public $dateTo;
    public $statusFilter;
    public $wardFilter;
    public $constituencyFilter;
    public $countyFilter;
    public $phaseFilter;

    public $countyOptions = [];
    public $constituencyOptions = [];
    public $wardsOptions = [];
    public $phaseOptions = [];

    public $perPage = 15;

    #[Url(except: '')]
    public $sortField = 'disbursement_date';
    public $sortDirection = 'desc';

    public $selectedGroupLoan;

    public ?int $correctingGroupLoanId = null;
    public ?string $editGfsCode = null;
    public ?string $editAmountApproved = null;

    public ?int $promptingGroupLoanId = null;
    public array $promptFields = [];
    public string $promptMessage = '';
    public ?string $promptGroupLabel = null;

    public function boot(
        GroupsService $groupsService,
        LoanPhaseService $loanPhaseService,
        AppCacheService $appCacheService,
        LocationService $locationService
    )
    {
        $this->groupsService = $groupsService;
        $this->loanPhaseService = $loanPhaseService;
        $this->cacheService = $appCacheService;
        $this->locationService = $locationService;
    }

    public function mount ()
    {
        $this->loadCountyOptions();
    }

    private function getFilters(): array
    {
        return [
            'search' => $this->search,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'status' => $this->statusFilter,
            'ward' => $this->wardFilter,
            'constituency_code' => $this->constituencyFilter,
            'county_code' => $this->countyFilter,
            'phase' => $this->phaseFilter,
        ];
    }

    #[Computed]
    public function perPageOptions()
    {
        return [
            ['id' => 10, 'name' => '10 per page'],
            ['id' => 15, 'name' => '15 per page'],
            ['id' => 25, 'name' => '25 per page'],
            ['id' => 50, 'name' => '50 per page'],
            ['id' => 100, 'name' => '100 per page'],
        ];
    }

    #[Computed]
    public function statusOptions()
    {
        return [
            ['value' => 'active', 'name' => 'Performing'],
            ['value' => 'defaulted', 'name' => 'Defaulted'],
            ['value' => 'completed', 'name' => 'Cleared'],
        ];
    }

    #[Computed]
    public function getStats()
    {
        return $this->groupsService->getGroupLoansDashboardStats();
    }

    #[Computed]
    public function groupLoans()
    {
        $hasActiveFilters = collect($this->getFilters())->filter()->isNotEmpty();

        if ($hasActiveFilters){
            return $this->fetchGroupLoans();
        }

        return $this->cacheService->rememberGroupLoans(
            fn() => $this->fetchGroupLoans(),
            null,
            $this->getPage(),
            (int)$this->perPage,
            $this->sortField,
            $this->sortDirection
        );
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    private function fetchGroupLoans()
    {
        return $this->groupsService->getGroupLoansQuery(
            null,
            $this->getFilters(),
            $this->sortField,
            $this->sortDirection
        )->paginate((int)$this->perPage);
    }

    #[Computed]
    private function loadCountyOptions()
    {
        $this->countyOptions = $this->locationService->getAllCounties()
            ->map(fn($c) => [
                'id' => $c->county_code,
                'name' => $c->county_name
            ])
            ->toArray();
    }


    public function updatedCountyFilter($value)
    {
        $this->constituencyFilter = '';
        $this->wardFilter = '';
        $this->phaseFilter = '';

        $this->reset('constituencyOptions');
        $this->reset('wardsOptions');
        $this->reset('phaseOptions');

        if (!empty($value)) {
            $this->loadConstituencies($value);
        }

        $this->resetPage();
    }

    public function updatedConstituencyFilter($value)
    {
        $this->wardFilter = '';
        $this->phaseFilter = '';

        $this->reset('wardsOptions');

        if (!empty($value)) {
            $this->loadWards($value);
            $this->loadPhases($value);
        }

        $this->resetPage();
    }

    private function loadConstituencies($countyCode = null)
    {
        if (!$countyCode) {
            $this->constituencyOptions = [];
            return;
        }

        $this->constituencyOptions = $this->locationService->getAllConstituencies()
            ->where('county_code', $countyCode)
            ->map(fn($c) => [
                'id' => $c->constituency_code,
                'name' => Str::ucfirst(Str::lower($c->constituency_name))
            ])
            ->values()
            ->toArray();
    }

    private function loadWards($constituencyCode = null)
    {
        if (!$constituencyCode) {
            $this->wardsOptions = [];
            return;
        }

        $this->wardsOptions = $this->locationService->mapWardsForSelect($constituencyCode);
    }

    private function loadPhases($constituencyCode = null)
    {
        if (!$constituencyCode) {
            $this->phaseOptions = [];
            return;
        }

        $this->phaseOptions = $this->loanPhaseService->getPhasesForConstituencyByStatus(
            $constituencyCode,
            'disbursed'
        );
    }

    public function refreshData()
    {
        $this->cacheService->flushGroupLoansCache();

        unset($this->groupLoans);

        $this->resetPage();
    }


    public function resetFilters()
    {
        $this->reset(['search', 'statusFilter', 'wardFilter', 'constituencyFilter', 'countyFilter', 'phaseFilter', 'dateFrom', 'dateTo']);
    }

    public function updatingSearch(){$this->resetPage();}
    public function updatingWardFilter() { $this->resetPage(); }
    public function updatingConstituencyFilter() { $this->resetPage(); }
    public function updatingCountyFilter() { $this->resetPage(); }
    public function updatingPhaseFilter() { $this->resetPage(); }
    public function updatingPerPage() { $this->resetPage(); }
    public function updatingDateFrom() { $this->resetPage(); }
    public function updatingDateTo() { $this->resetPage(); }
    public function updatingStatusFilter() { $this->resetPage(); }

    public function updatedPerPage ($value) {if ($value === null) $this->perPage = 15;}

    public function viewGroupLoan($groupLoanId)
    {
        $this->selectedGroupLoan = $this->groupsService->getGroupLoanHistory($groupLoanId);
        $this->dispatch('open-modal', name: 'loan-info-modal');
    }

    public function closeGroupLoanModal()
    {
        $this->reset(['selectedGroupLoan']);
    }

    public function downloadStatement($loanId)
    {
        $loan = GroupLoan::with(['group', 'loanPhase', 'loanReceipts'])
            ->where('loan_status', 'financed')
            ->findOrFail($loanId);

        $pdf = Pdf::loadView('exports.loan-statement', [
            'loan' => $loan
        ])->setPaper('a4', 'portrait')->output();

        return response()->streamDownload(
            fn () => print($pdf),
            "Statement_{$loan->id}.pdf"
        );
    }


    public function openHqCorrectModal(int $groupLoanId): void
    {
        $groupLoan = GroupLoan::with(['group', 'loanPhase'])->findOrFail($groupLoanId);
        $service = app(GroupDetailEditService::class);

        if (! $service->canEditGroupLoan($groupLoan, auth()->user())) {
            $this->dispatch('swal:toast', title: 'Permission Denied', message: 'You cannot correct this historical loan.', type: 'error');
            return;
        }

        $this->correctingGroupLoanId = $groupLoan->id;
        $this->editGfsCode = $groupLoan->gfs_code;
        $this->editAmountApproved = $groupLoan->amount_approved !== null ? (string) $groupLoan->amount_approved : null;
        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'hq-correct-historical-modal');
    }

    public function saveHqHistoricalCorrection(): void
    {
        if (! $this->correctingGroupLoanId) {
            return;
        }

        $groupLoan = GroupLoan::with(['group', 'loanPhase'])->findOrFail($this->correctingGroupLoanId);

        try {
            app(GroupDetailEditService::class)->updateDetails($groupLoan, auth()->user(), [
                'gfsCode' => $this->editGfsCode,
                'amountApproved' => $this->editAmountApproved,
            ]);

            $this->reset(['correctingGroupLoanId', 'editGfsCode', 'editAmountApproved']);
            $this->dispatch('close-modal', name: 'hq-correct-historical-modal');
            $this->dispatch('swal:toast', title: 'Corrected', message: 'Approved amount / GFS code updated.', type: 'success');
            $this->refreshData();
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError($field, $messages[0]);
            }
            $this->dispatch('swal:toast', title: 'Validation Error', message: collect($e->errors())->flatten()->first(), type: 'error');
        } catch (\Throwable $e) {
            $this->dispatch('swal:toast', title: 'Update Failed', message: $e->getMessage(), type: 'error');
        }
    }

    public function openRequestCorrectionModal(int $groupLoanId): void
    {
        $groupLoan = GroupLoan::with(['group', 'loanPhase'])->findOrFail($groupLoanId);

        if (! app(HistoricalCorrectionService::class)->hqCanCorrectHistorical(auth()->user(), $groupLoan)) {
            $this->dispatch('swal:toast', title: 'Permission Denied', message: 'Correction prompts apply to historical group loans only.', type: 'error');
            return;
        }

        $this->promptingGroupLoanId = $groupLoan->id;
        $this->promptGroupLabel = ($groupLoan->group?->name ?? 'Group').' / '.($groupLoan->loanPhase?->phase ?? '');
        $this->promptFields = ['disbursement_date', 'amount_issued', 'amount_repaid'];
        $this->promptMessage = '';
        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'hq-request-correction-modal');
    }

    public function sendCorrectionPrompt(): void
    {
        if (! $this->promptingGroupLoanId) {
            return;
        }

        $groupLoan = GroupLoan::with(['group', 'loanPhase'])->findOrFail($this->promptingGroupLoanId);

        try {
            app(HistoricalCorrectionService::class)->requestGroupLoanCorrection(
                auth()->user(),
                $groupLoan,
                $this->promptFields,
                $this->promptMessage !== '' ? $this->promptMessage : null,
            );

            $this->reset(['promptingGroupLoanId', 'promptFields', 'promptMessage', 'promptGroupLabel']);
            $this->dispatch('close-modal', name: 'hq-request-correction-modal');
            $this->dispatch('swal:toast', title: 'Prompt sent', message: 'Field officers were notified by email and will see a login modal.', type: 'success');
        } catch (ValidationException $e) {
            $this->dispatch('swal:toast', title: 'Could not send', message: collect($e->errors())->flatten()->first(), type: 'error');
        } catch (\Throwable $e) {
            $this->dispatch('swal:toast', title: 'Could not send', message: $e->getMessage(), type: 'error');
        }
    }

    public function render()
    {
        return view('livewire.hq.group-loans');
    }
}
