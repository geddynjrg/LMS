<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Constituency;
use App\Services\ReceiptsService;
use App\Services\Cache\AppCacheService;
use App\Services\IdempotencyService;
use App\Services\Group\GroupsService;


class LoanReceipts extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    protected ReceiptsService $receiptsService;
    protected AppCacheService $cacheService;
    protected IdempotencyService $idempotencyService;
    protected GroupsService $groupService;

    public $constituency;

    public $search;
    public $methodsFilter;
    public $statusFilter;
    public $dateFrom;
    public $dateTo;

    public $perPage = 15;

    #[Url(except: '')]
    public $sortField = 'transaction_date';
    public $sortDirection = 'desc';

    public $selectedReceipt;
    public $receiptNumber;
    public $transactionDate;
    public $amountRepaid;
    public $repaymentMethod;
    public $referenceNumber;
    public $reason;

    public $groupId;

    public $receipt = [
        'id' => '',
        'receipt_number' => '',
        'transaction_date' => '',
        'amount' => '',
        'payment' => '',
        'reference_number' => '',
    ];

    public function boot(
        ReceiptsService $receiptsService,
        AppCacheService $appCacheService,
        IdempotencyService $idempotencyService,
        GroupsService $groupsService
        )
    {
        $this->receiptsService = $receiptsService;
        $this->cacheService = $appCacheService;
        $this->idempotencyService = $idempotencyService;
        $this->groupsService = $groupsService;
    }

    public function mount(Constituency $constituency)
    {
        $this->constituency = $constituency;
    }

    #[Computed]
    public function getStats()
    {
        return $this->receiptsService->getDashboardStats($this->constituency->constituency_code);
    }

    #[Computed]
    public function receipts()
    {
        $hasActiveFilters = collect($this->getFilters())->filter()->isNotEmpty();

        if ($hasActiveFilters){
            return $this->fetchReceipts();
        }

        $cacheKey = md5(json_encode([
            'page' => $this->getPage(),
            'perPage' => (int)$this->perPage,
            'sortField' => $this->sortField,
            'sortDirection' => $this->sortDirection,
        ]));

        return $this->cacheService->rememberReceipts(
            $cacheKey,
            'constituency_trends',
            fn() => $this->fetchReceipts(),
            $this->constituency->constituency_code,
        );
    }

    private function fetchReceipts()
    {
        return $this->receiptsService->getRecentReceiptsQuery(
            $this->constituency->constituency_code,
            $this->getFilters(),
            $this->sortField,
            $this->sortDirection
        )->paginate((int)$this->perPage);
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
    public function methodsOptions()
    {
        return [
            ['id' => 1, 'name' => 'Mpesa'],
            ['id' => 2, 'name' => 'Bank'],
        ];
    }

    #[Computed]
    public function statusOptions()
    {
        return [
            ['value' => 'active', 'name' => 'Posted'],
            ['value' => 'voided', 'name' => 'Canceled'],
        ];
    }

    #[Computed]
    public function groupsOptions()
    {
        return $this->populateGroups()->map(fn($g) => [
                'id' => $g->currentLoan->id,
                'name' => $g->name.'-('.$g->code.')-('.$g->identifier.')-('.$g->currentLoan->loanPhase->phase.')',
            ])
            ->toArray();
    }

    #[Computed]
    private function populateGroups()
    {
        return $this->cacheService->rememberReceipts(
            'receipt_groups_' . $this->constituency->constituency_code,
            'constituency_groups',
            fn() => $this->receiptsService->getGroups($this->constituency->constituency_code),
            $this->constituency->constituency_code,
        );
    }

    private function getFilters(): array
    {
        return [
            'search' => $this->search,
            'payment_method' => $this->methodsFilter,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'status' => $this->statusFilter,
        ];
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

    public function refreshData()
    {
        $this->cacheService->flashCache(
            'receipts',
            'constituency_trends',
            $this->constituency->constituency_code
        );
        $this->cacheService->flashCache(
            'receipts',
            'constituency_stats',
            $this->constituency->constituency_code
        );
        $this->cacheService->flashCache(
            'receipts',
            'constituency_groups',
            $this->constituency->constituency_code
        );

        unset($this->getStats);
        unset($this->receipts);
        unset($this->groupsOptions);
        unset($this->populateGroups);

       $this->resetPage();
    }

    public function resetFilters()
    {
        $this->reset(['search', 'methodsFilter', 'dateFrom', 'dateTo', 'statusFilter']);
        $this->resetPage();
    }

    public function updatingSearch(){$this->resetPage();}
    public function updatingMethodsFilter() { $this->resetPage(); }
    public function updatingPerPage() { $this->resetPage(); }
    public function updatingDateFrom() { $this->resetPage(); }
    public function updatingDateTo() { $this->resetPage(); }
    public function updatingStatusFilter() { $this->resetPage(); }
    public function updatedPerPage ($value) {if ($value === null) $this->perPage = 15;}

    public function updatedDateFrom($value)
    {
        $today = now()->toDateString();

        if ($value > $today) {
            $this->dateFrom = $today;
        }
    }

    public function updatedDateTo($value)
    {
        $today = now()->toDateString();

        if ($value > $today) {
            $this->dateTo = $today;
        }
    }

    private function canEditReceipt() : bool
    {
        $user = auth()->user();
        return $user->hasPermissionTo('update receipt');
    }

    private function canCancelreceipt() : bool
    {
        $user = auth()->user();
        return $user->hasPermissionTo('cancel receipt');
    }

    public function canCreateReceipt() : bool
    {
        $user = auth()->user();
        return $user->hasPermissionTo('create receipt');
    }

    public function validateReceipt()
    {
        $validMethodIds = collect($this->methodsOptions())->pluck('id')->toArray();

        return $this->validate([
            'receiptNumber' => [
                'required',
                'string',
                'max:50',
                Rule::unique('loan_receipts', 'receipt_number')
                    ->when($this->selectedReceipt, function ($rule) {
                        return $rule->ignore($this->selectedReceipt->id);
                    })
                    ->where('status', 'active')
                    ->where(function ($query) {
                        return $query->whereExists(function ($q) {
                            $q->select(DB::raw(1))
                                ->from('group_loans')
                                ->join('loan_phases', 'group_loans.loan_phase_id', '=', 'loan_phases.id')
                                ->whereColumn('group_loans.id', 'loan_receipts.group_loans_id')
                                ->where('loan_phases.constituency_code', $this->constituency->constituency_code);
                        });
                    }),
            ],
            'transactionDate' => [
                'required',
                'date',
                'before_or_equal:today',
            ],
            'amountRepaid' => [
                'required',
                'numeric',
                'gt:0',
                'max:999999999.99',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
            'repaymentMethod' => [
                'required',
                Rule::in($validMethodIds),
            ],
            'referenceNumber' => [
                'required',
                'string',
                'max:50',
                function ($attribute, $value, $fail) {
                    if ($this->repaymentMethod === 'Mpesa') {
                        if (!preg_match('/^[A-Z0-9]{10,15}$/i', $value)) {
                            $fail('The M-Pesa reference number must be 10-15 alphanumeric characters.');
                        }
                    } elseif ($this->repaymentMethod === 'Bank') {
                        if (!preg_match('/^[A-Z0-9\-]{5,30}$/i', $value)) {
                            $fail('The bank reference number must be 5-30 alphanumeric characters with hyphens allowed.');
                        }
                    }
                },
            ],
        ]);
    }

    public function validationAttributes()
    {
        return [
            'receiptNumber' => 'receipt number',
            'transactionDate' => 'transaction date',
            'amountRepaid' => 'amount',
            'repaymentMethod' => 'payment method',
            'referenceNumber' => 'reference number',
        ];
    }

    public function messages()
    {
        return [
            'receiptNumber.required' => 'Receipt number is required.',
            'receiptNumber.unique' => 'This receipt number already exists.',
            'receiptNumber.max' => 'Receipt number cannot exceed 50 characters.',

            'transactionDate.required' => 'Transaction date is required.',
            'transactionDate.date' => 'Please enter a valid date.',
            'transactionDate.before_or_equal' => 'Transaction date cannot be in the future.',
            // 'transactionDate.after_or_equal' => 'Transaction date cannot be more than 1 year old.',

            'amountRepaid.required' => 'Amount is required.',
            'amountRepaid.numeric' => 'Amount must be a number.',
            'amountRepaid.gt' => 'Amount must be greater than 0.',
            'amountRepaid.max' => 'Amount is too large.',
            'amountRepaid.regex' => 'Amount must have up to 2 decimal places.',

            'repaymentMethod.required' => 'Payment method is required.',
            'repaymentMethod.in' => 'Please select a valid payment method.',

            'referenceNumber.required' => 'Reference number is required.',
            'referenceNumber.max' => 'Reference number cannot exceed 50 characters.',
        ];
    }

    public function isProcessing($process, $idempotencyKey, $receiptNumber) : bool
    {
        $payload = [
            'receipt_number' => $receiptNumber,
            'remarks'  => $process
        ];

        if (!$this->idempotencyService->acquire($idempotencyKey, $payload)) {
            $this->dispatch('swal:toast',
                title: 'Action in progress: 😕',
                message: "This Receipt is already being processed.",
                type: 'error'
            );

            return true;
        }

        return false;
    }

    public function editReceipt($receiptId)
    {
        $receipt = $this->receiptsService->getBaseQuery($this->constituency->constituency_code)
            ->where('lr.id', $receiptId)
            ->select([
                'lr.id',
                'lr.receipt_number',
                'lr.transaction_date',
                'lr.amount',
                'lr.payment',
                'lr.reference_number',
                'lr.status',
                'g.name as group_name',
                'gl.loan_type'
            ])
            ->first();

        if (!$receipt) {
            $this->dispatch('swal:toast',
                title: 'Invalid Receipt 😕',
                message: 'The selected receipt is invalid. Selecet from collection.',
                type: 'error'
            );
            return;
        }

        $this->selectedReceipt = $receipt;

        $this->generateReceipt($receipt);

        $this->resetErrorBag();

        $this->dispatch('open-modal', name: 'edit-receipt-modal');
    }

    public function confirmEditReceipt()
    {
        // can edit
        if (!$this->canEditReceipt()) {
            $this->dispatch('swal:toast',
                title: 'Permission Denied 😕',
                message: 'You don\'t have permissions to perform this action. ',
                type: 'error'
            );

            $this->dispatch('close-modal', name: 'edit-receipt-modal');

            $this->reset('selectedReceipt', 'receiptNumber', 'transactionDate', 'amountRepaid', 'repaymentMethod', 'referenceNumber');

            return;
        }

        // selected receipt is not null
        if (empty($this->selectedReceipt)) {
            $this->dispatch('swal:toast',
                title: 'Invalid Receipt 😕',
                message: 'Invalid receipt. Please try again. ',
                type: 'error'
            );

            $this->dispatch('close-modal', name: 'edit-receipt-modal');

            $this->reset('selectedReceipt', 'receiptNumber', 'transactionDate', 'amountRepaid', 'repaymentMethod', 'referenceNumber');

            return;
        }

        if (!$this->validateReceipt()) {
            return;
        }

        if ($this->selectedReceipt->status !== 'active') {
            $this->dispatch('swal:toast',
                title: 'Canceled Receipt 😕',
                message: 'Cannot edit a cenceled receipt. ',
                type: 'error'
            );

            $this->dispatch('close-modal', name: 'edit-receipt-modal');

            $this->reset('selectedReceipt', 'receiptNumber', 'transactionDate', 'amountRepaid', 'repaymentMethod', 'referenceNumber');

            return;
        }

        $idempotencyKey = "update_receipt_{$this->selectedReceipt->receipt_number}";

        if ($this->isProcessing('Editing', $idempotencyKey, $this->selectedReceipt->receipt_number)) {

            $this->dispatch('close-modal', name: 'edit-receipt-modal');

            $this->reset('selectedReceipt', 'receiptNumber', 'transactionDate', 'amountRepaid', 'repaymentMethod', 'referenceNumber');

            return;
        }

        try {
            $data = [
                'receiptNumber' => $this->receiptNumber,
                'transactionDate' => $this->transactionDate,
                'amountRepaid' => $this->amountRepaid,
                'repaymentMethod' => collect($this->methodsOptions)->firstWhere('id', $this->repaymentMethod)['name'] ?? null,
                'referenceNumber' => $this->referenceNumber,
            ];

            $this->receiptsService->editReceipt($this->selectedReceipt->id, $data, $idempotencyKey);

            $this->dispatch('close-modal', name: 'edit-receipt-modal');

            $this->reset('selectedReceipt', 'receiptNumber', 'transactionDate', 'amountRepaid', 'repaymentMethod', 'referenceNumber');

            $this->refreshData();

            $this->dispatch('swal:toast',
                title: 'Updated Successfully 😊',
                message: 'Receipt Has Been Updated successfully.',
                type: 'success'
            );
        } catch (\Throwable $th) {
            Log::error("Failed to update Receipt : " . $th->getMessage());

            $this->dispatch('swal:toast',
                title: 'Update Error: 😕',
                message: "Failed to update Receipt. Please Try again later",
                type: 'error'
            );

            $this->idempotencyService->release($idempotencyKey);
        }
    }

    public function cancelReceipt($receiptId)
    {
        $this->resetErrorBag('reason');

        $receipt = $this->receiptsService->getBaseQuery($this->constituency->constituency_code)
            ->where('lr.id', $receiptId)
            ->select([
                'lr.id',
                'lr.receipt_number',
                'lr.transaction_date',
                'lr.amount',
                'lr.payment',
                'lr.reference_number',
                'lr.status',
            ])
            ->first();


        if (!$receipt) {
            $this->dispatch('swal:toast',
                title: 'Invalid Receipt 😕',
                message: 'The selected receipt is invalid. Selecet from collection.',
                type: 'error'
            );
            return;
        }

        $this->selectedReceipt = $receipt;

        $this->generateReceipt($receipt);


        $this->dispatch('open-modal', name: 'cancel-receipt-modal');
    }

    public function confirmCancelReceipt()
    {
        if (!$this->canCancelreceipt()) {
            $this->dispatch('swal:toast',
                title: 'Permission Denied 😕',
                message: 'You don\'t have permissions to perform this action. ',
                type: 'error'
            );

            $this->dispatch('close-modal', name: 'cancel-receipt-modal');

            $this->reset('reason');

            return;
        }

        if (empty($this->selectedReceipt)) {
            $this->dispatch('swal:toast',
                title: 'Invalid Receipt 😕',
                message: 'Invalid receipt. Please try again. ',
                type: 'error'
            );

            $this->dispatch('close-modal', name: 'cancel-receipt-modal');

            $this->reset('reason');

            return;
        }

        $this->validate([
            'reason' => [
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
            'reason.required' => 'You must provide a detailed explanation for the applicant.',
            'reason.min' => 'The explanation is too short. Please provide at least 20 characters of detail.',
        ]);

        $idempotencyKey = "update_receipt_{$this->selectedReceipt->receipt_number}";

        if ($this->isProcessing('Canceling', $idempotencyKey, $this->selectedReceipt->receipt_number)) {

            $this->dispatch('close-modal', name: 'cancel-receipt-modal');

            $this->reset('reason');

            return;
        }

        try {
            $this->receiptsService->cancelReceipt($this->selectedReceipt->id, $idempotencyKey, $this->reason);

            $this->dispatch('close-modal', name: 'cancel-receipt-modal');

            $this->reset('reason');

            $this->refreshData();

            $this->dispatch('swal:toast',
                title: 'Updated Successfully 😊',
                message: 'Receipt Has Been Updated successfully.',
                type: 'success'
            );

        } catch (\Throwable $th) {

            dd($th->getMessage());
            Log::error("Failed to update Receipt 1this->selectedReceipt->id: " . $th->getMessage());

            $this->dispatch('swal:toast',
                title: 'Update Error: 😕',
                message: "Failed to cancel Receipt. Please Try again later",
                type: 'error'
            );

            $this->idempotencyService->release($idempotencyKey);
        }

    }

    private function generateReceipt($receipt): void
    {
        $this->receiptNumber = $receipt->receipt_number;
        $this->transactionDate = $receipt->transaction_date;
        $this->amountRepaid = $receipt->amount;
        $this->repaymentMethod = collect($this->methodsOptions())->where('name', $receipt->payment)->first()['id'] ?? null;
        $this->referenceNumber = $receipt->reference_number;
    }

    public function addReceipt()
    {
        $this->resetErrorBag();

        $this->dispatch('open-modal', name: 'add-receipt-modal');
    }

    public function confirmAddReceipt()
    {
        if (!$this->canCreateReceipt()) {
            $this->dispatch('swal:toast',
                title: 'Permission Denied 😕',
                message: 'You don\'t have permissions to perform this action. ',
                type: 'error'
            );

            $this->dispatch('close-modal', name: 'add-receipt-modal');

            $this->reset('receiptNumber', 'transactionDate', 'amountRepaid', 'repaymentMethod', 'referenceNumber');

            return;
        }

        if (!$this->validateReceipt()) {
            return;
        }

        $validGroupIds = collect($this->groupsOptions())->pluck('id')->toArray();
        $this->validate([
            'groupId' => [
                'required',
                Rule::in($validGroupIds),
            ],
        ]);

        $idempotencyKey = "update_receipt_{$this->receiptNumber}";

        if ($this->isProcessing('Creating', $idempotencyKey, $this->receiptNumber)) {

            $this->dispatch('close-modal', name: 'add-receipt-modal');

            $this->reset('receiptNumber', 'transactionDate', 'amountRepaid', 'repaymentMethod', 'referenceNumber');

            return;
        }

        try {
            $data = [
                'receiptNumber' => $this->receiptNumber,
                'transactionDate' => $this->transactionDate,
                'amountRepaid' => $this->amountRepaid,
                'repaymentMethod' => collect($this->methodsOptions)->firstWhere('id', $this->repaymentMethod)['name'] ?? null,
                'referenceNumber' => $this->referenceNumber,
            ];

            $this->receiptsService->createReceipt($this->groupId, $data, $idempotencyKey);

            $this->dispatch('close-modal', name: 'add-receipt-modal');

            $this->reset('groupId', 'receiptNumber', 'transactionDate', 'amountRepaid', 'repaymentMethod', 'referenceNumber');

            $this->refreshData();

            $this->dispatch('swal:toast',
                title: 'Updated Successfully 😊',
                message: 'Receipt Has Been Updated successfully.',
                type: 'success'
            );
        } catch (\Throwable $th) {
            Log::error("Failed to Add Receipt: " . $th->getMessage());

            $this->dispatch('swal:toast',
                title: 'Add Error: 😕',
                message: "Failed to Add Receipt. Please Try again later",
                type: 'error'
            );

            $this->idempotencyService->release($idempotencyKey);
        }
    }

    public function render()
    {
        return view('livewire.loan.loan-receipts', [
            // 'receipts' => $this->receipts(),
        ]);
    }
}
