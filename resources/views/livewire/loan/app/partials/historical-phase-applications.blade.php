{{-- Group loans listed as applications for historical upload phases --}}
<div class="row mb-3">
    <div class="col-sm col-6">
        <label class="small font-weight-bold text-muted ml-1">REPAYMENT STATUS</label>
        <x-dropdown-search
            model="statusFilter"
            :options="$this->historicalStatusOptions"
            optionLabel="name"
            optionValue="value"
            placeholder="select"
            icon="fas fa-shield-alt"
        />
    </div>

    <div class="col-sm col-6">
        <label class="small font-weight-bold text-uppercase text-muted ml-1">Category</label>
        <x-dropdown-search
            model="riskFilter"
            :options="[
                ['value' => 'youth', 'name' => 'Youth'],
                ['value' => 'women', 'name' => 'Women'],
                ['value' => 'pwd', 'name' => 'PWD'],
            ]"
            optionLabel="name"
            optionValue="value"
            placeholder="select"
            icon="far fa-surprise"
        />
    </div>

    <div class="col-sm-3 col-6">
        <label class="small font-weight-bold text-uppercase text-muted">Display</label>
        <x-dropdown-search
            model="perPage"
            :options="$this->perPageOptions"
            icon="fas fa-list-ol"
            optionLabel="name"
            optionValue="id"
            placeholder="10 per page"
        />
    </div>
</div>

<div class="position-relative">
    <div wire:loading.delay.shortest
        wire:target="nextPage, previousPage, gotoPage, updatedSearch, updatedFilter, updatedRisk, sortBy"
        class="position-absolute w-100 h-100 d-flex justify-content-center align-items-center"
        style="background: rgba(255, 255, 255, 0.6);
                backdrop-filter: blur(4px);
                z-index: 1050;
                top: 0;
                left: 0;
                transition: all 0.3s ease;
                display: none !important;">
        <div class="text-center bg-white p-4 rounded-lg shadow-lg border" style="min-width: 180px;">
            <div class="mb-3">
                <i class="fas fa-circle-notch fa-spin fa-2x text-primary"></i>
            </div>
            <p class="m-0 text-uppercase tracking-wider font-weight-bold text-dark" style="font-size: 0.75rem; letter-spacing: 1px;">
                Loading...
            </p>
            <small class="text-gray-500">Updating Data</small>
        </div>
    </div>

    <div class="table-responsive p-0"
        style="max-height: 550px; transition: opacity 0.3s ease;"
        wire:key="{{ $this->detailedPhase->slug }}-historical">
        <table id="table-data" class="table align-middle mb-0 p-0 table-head-fixed">
            <thead>
                <tr>
                    <x-table-header field="group_name" :sortField="$sortField" :sortDirection="$sortDirection">
                        Group Details
                    </x-table-header>
                    <x-table-header field="category" :sortField="$sortField" :sortDirection="$sortDirection">
                        Demographic
                    </x-table-header>
                    <x-table-header field="amount_approved" :sortField="$sortField" :sortDirection="$sortDirection">
                        Financials
                    </x-table-header>
                    <x-table-header field="status" :sortField="$sortField" :sortDirection="$sortDirection">
                        Status
                    </x-table-header>
                    <x-table-header :sortable="false">
                        Action
                    </x-table-header>
                </tr>
            </thead>
            <tbody>
                @forelse($this->applications as $loan)
                    <tr wire:key="hist-row-{{ $loan->id }}" class="animate__animated animate__fadeIn" style="animation-delay: {{ $loop->index * 0.05 }}s;">
                        <td class="px-4 py-3">
                            <span class="text-sm text-gray-900">{{ $loan->group_name }}</span>
                            <div class="text-xs text-gray-600">
                                @if($loan->group_identifier)
                                    <span class="d-block font-mono">{{ $loan->group_identifier }}</span>
                                @endif
                                <span>
                                    <i class="fas fa-map-marker-alt mr-1"></i>{{ $loan->ward_name ?? 'Unallocated' }}
                                </span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-sm text-gray-900">
                                Category: {{ $loan->category ?? 'N/A' }}
                            </div>
                            @if($loan->sector)
                                <div class="text-xs text-gray-500">Sector: {{ $loan->sector }}</div>
                            @endif
                            @if($loan->loan_level)
                                <div class="text-xs text-gray-500">Loan Level: {{ $loan->loan_level }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="d-flex flex-column">
                                @if($loan->amount_applied > 0)
                                    <div class="text-sm text-gray-900">
                                        <span>Applied:</span>
                                        <span class="whitespace-nowrap">KES {{ number_format($loan->amount_applied, 2) }}</span>
                                    </div>
                                @endif
                                <div class="text-xs text-gray-500">
                                    <span class="whitespace-nowrap">Approved: KES {{ number_format($loan->amount_approved ?? 0, 2) }}</span>
                                </div>
                                <div class="text-xs text-gray-500">
                                    <span class="whitespace-nowrap">Disbursed: KES {{ number_format($loan->amount_issued ?? 0, 2) }}</span>
                                </div>
                                <div class="text-xs text-gray-500">
                                    <span class="whitespace-nowrap">Repaid: KES {{ number_format($loan->amount_repaid ?? 0, 2) }}</span>
                                    <span class="mx-1">|</span>
                                    <span class="whitespace-nowrap">Balance: KES {{ number_format($loan->balance ?? 0, 2) }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            @if($loan->loan_status === 'cancelled')
                                <span class="bg-red-50 border-red-200 text-red-700 border d-inline-flex align-items-center font-medium px-2.5 py-1 rounded-lg text-xs whitespace-nowrap">
                                    Cancelled
                                </span>
                            @elseif($loan->status === 'completed')
                                <span class="bg-blue-50 border-blue-200 text-blue-700 border d-inline-flex align-items-center font-medium px-2.5 py-1 rounded-lg text-xs whitespace-nowrap">
                                    Cleared
                                </span>
                            @elseif($loan->status === 'active')
                                <span class="bg-amber-50 border-amber-200 text-amber-700 border d-inline-flex align-items-center font-medium px-2.5 py-1 rounded-lg text-xs whitespace-nowrap">
                                    Performing
                                </span>
                            @else
                                <span class="bg-red-50 border-red-200 text-red-700 border d-inline-flex align-items-center font-medium px-2.5 py-1 rounded-lg text-xs whitespace-nowrap">
                                    Defaulted
                                </span>
                            @endif
                            @if($loan->disbursement_date)
                                <div class="text-xs text-muted mt-1">
                                    <i class="far fa-calendar-alt mr-1"></i>{{ \Carbon\Carbon::parse($loan->disbursement_date)->format('M d, Y') }}
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($loan->group_slug)
                                <a href="{{ route('const.groups.group', [$constituency->county->slug, $constituency->slug, $loan->group_slug]) }}"
                                   class="btn btn-white btn-sm border shadow-sm">
                                    <i class="fas fa-eye text-primary mr-1"></i> View Group
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4">
                            <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                            <h5 class="text-sm text-gray-500">No groups found in this phase</h5>
                            @if($search || $statusFilter || $riskFilter)
                                <x-loading-button
                                    target="resetFilters"
                                    class="btn-refresh-premium text-xs"
                                    icon="fas fa-filter"
                                    label="Refresh Data"
                                />
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card-footer clearfix bg-transparent px-0 border-0">
    <div class="pagination-sm">
        {{ $this->applications->links() }}
    </div>
</div>
