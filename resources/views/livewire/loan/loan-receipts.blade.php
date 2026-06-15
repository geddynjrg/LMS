<section class="content">
    <div class="container-fluid">
        <div class="card shadow-none border-left-info mb-4" style="border-left: 5px solid #17a2b8;">
            <div class="card-body py-3">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-0 font-bold text-3xl text-gray-900">
                            <i class="fas fa-clipboard-list text-info mr-2"></i>
                            {{ ucwords(strtolower($constituency->constituency_name)) }} Constituency
                        </h1>
                        <p class="mt-1 text-gray-600">Manage loan Repayment Reciepts.</p>
                    </div>
                    <div class="col-md-4 text-md-right mt-3 mt-md-0">
                        <x-loading-button
                            target="refreshData"
                            class="btn-refresh-premium"
                            icon="fas fa-sync-alt"
                            label="Refresh Data"
                        />
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            @foreach($this->getStats as $key => $kpi)
                <div class="col-lg-3 col-6 mb-3 mb-lg-0">
                    <div class="card card-hover shadow-sm border-left-success h-100 py-2">
                        <div class="card-body py-1">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="text-xs font-weight-bold  d-block text-primary text-uppercase mb-1">{{ $kpi['title'] }}</span>
                                    <span class="h5 mb-1 font-weight-bold text-gray-900">KES {{ number_format($kpi['val']) }}</span>
                                </div>
                                <div class="icon-circle bg-light-info text-primary">
                                    <i class="fas {{ $kpi['icon'] }}"></i>
                                </div>
                            </div>
                            <hr class="my-2">

                            <div class="col">
                                <small class="text-xs text-gray-500">{{ $kpi['desc'] }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="card card-outline card-info shadow-sm border-0 mb-4" style="border-radius: 12px;">
            <div class="card-header bg-white border-bottom-0 py-3">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">

                    <h3 class="card-title mb-3 mb-md-0 mr-md-4">
                        <i class="fas fa-list text-primary mr-2"></i> Repayment Receipts
                    </h3>
                    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-md-end flex-md-grow-1" style="gap: 15px;">

                        <div class="input-group premium-input-group order-2 transition-all"
                            x-data="{ focused: false }"
                            :class="{ 'premium-focus': focused }"
                            style="min-width: 250px; max-width: 350px;">

                            <input type="text"
                                wire:model.live.debounce.500ms="search"
                                @focus="focused = true"
                                @blur="focused = false"
                                class="form-control border-right-0 shadow-none text-sm"
                                placeholder="Search Applications..."
                                style="height: 32px; border-radius: 10px 0 0 10px; font-size: 0.8rem; border-color: #ced4da; padding-left: 12px;">

                            <div class="input-group-append">
                                <span class="input-group-text bg-white border-left-0 py-0"
                                    style="border-radius: 0 10px 10px 0; height: 32px; width: 35px; justify-content: center; transition: 0.2s;">
                                    <i class="fas fa-search text-muted"
                                    :class="focused ? 'text-brand animate__animated animate__pulse' : ''"
                                    style="font-size: 0.8rem;"></i>
                                </span>
                            </div>
                        </div>

                        <div class="d-flex order-1 order-sm-2" style="gap: 8px;">
                            <div class="btn-group">
                                <x-loading-button
                                    title="refresh data"
                                    target="refreshData"
                                    class="btn-default"
                                    icon="fas fa-sync-alt"
                                    label=""
                                />
                                <x-loading-button
                                    title="clear filters"
                                    target="resetFilters"
                                    class="btn-default"
                                    icon="fas fa-filter"
                                    label=""
                                />
                            </div>

                            {{-- <x-loading-button
                                target="exportReceipts"
                                class="btn-outline-dark"
                                icon="fas fa-file-excel"
                                label="Export Data"
                                loadingLabel="..."
                            /> --}}

                            <x-loading-button
                                target="addReceipt"
                                class="btn-outline-primary"
                                icon="fas fa-plus"
                                label="New Receipt"
                                loadingLabel="..."
                            />
                        </div>
                    </div>

                </div>
            </div>

            <div class="card-body">
                {{-- Filters --}}
                <div class="row mb-3">
                    {{-- County Filter --}}
                    {{-- <div class="col-sm-2 col-6">
                        <label class="small font-weight-bold text-muted ml-1">COUNTY</label>
                        <x-dropdown-search
                            wire:key="dropdown-n-countyFilter-{{ count($countyOptions) }}"
                            model="countyFilter"
                            :options="$countyOptions"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Select County"
                            icon="fas fa-map-marker-alt"
                        />
                    </div>
                    <div class="col-sm-2 col-6" wire:key="constituency-n-wrapper-{{ $countyFilter }}">
                        @if(!empty($constituencyOptions))
                            <label class="small font-weight-bold text-muted ml-1">CONSTITUENCY</label>
                            <x-dropdown-search
                                wire:key="dropdown-{{ $countyFilter }}-{{ count($constituencyOptions) }}"
                                model="constituencyFilter"
                                :options="$constituencyOptions"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="Select Constituency"
                                icon="fas fa-map-signs"
                            />
                        @else
                            <label class="small text-muted ml-1 opacity-50">CONSTITUENCY</label>
                            <div class="form-control bg-light border-0 d-flex align-items-center" style="height: 48px; border-radius: 12px;">
                                <div wire:loading wire:target="updatedCountyFilter" class="spinner-border spinner-border-sm text-primary mr-2"></div>
                                <small class="text-muted" wire:loading.remove wire:target="updatedCountyFilter">Select a county first...</small>
                                <small class="text-muted" wire:loading wire:target="updatedCountyFilter">Loading constituencies...</small>
                            </div>
                        @endif
                    </div> --}}
                    <div class="col-sm-3 col-6">
                        <div class="form-group">
                            <label class="small font-weight-bold text-uppercase text-muted">Payment Method</label>

                            <x-dropdown-search
                                model="methodsFilter"
                                :options="$this->methodsOptions"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="All methods"
                                icon="fas fa-clipboard-check"
                            />
                        </div>
                    </div>
                    <div class="col-sm-3 col-6">
                        <div class="form-group">
                            <label class="small font-weight-bold text-uppercase text-muted">Status</label>

                            <x-dropdown-search
                                model="statusFilter"
                                :options="$this->statusOptions"
                                optionLabel="name"
                                optionValue="value"
                                placeholder="select"
                                icon="fas fa-check-circle"
                            />
                        </div>
                    </div>
                    <div class="col-sm col-6">
                        <label class="small font-weight-bold text-uppercase text-muted">From Date</label>
                        <div class="position-relative">
                            {{-- Slate Icon --}}
                            <div class="position-absolute d-flex align-items-center justify-content-center"
                                style="left: 12px; top: 0; bottom: 0; width: 25px; z-index: 5; pointer-events: none;">
                                <i class="fas fa-calendar-alt icon-premium" style="font-size: 0.9rem;"></i>
                            </div>

                            <input type="date"
                                wire:model.live="dateFrom"
                                onclick="this.showPicker()"
                                max="{{ date('Y-m-d') }}"
                                class="form-control shadow-none border-light-subtle custom-date-input"
                                style="height: 48px; border-radius: 12px; padding-left: 45px; cursor: pointer; transition: 0.2s;">
                        </div>
                    </div>
                    <div class="col-sm col-6">
                        <label class="small font-weight-bold text-uppercase text-muted">To Date</label>
                        <div class="position-relative">
                            {{-- Slate Icon --}}
                            <div class="position-absolute d-flex align-items-center justify-content-center"
                                style="left: 12px; top: 0; bottom: 0; width: 25px; z-index: 5; pointer-events: none;">
                                <i class="fas fa-calendar-alt icon-premium" style="font-size: 0.9rem;"></i>
                            </div>

                            <input type="date"
                                wire:model.live="dateTo"
                                onclick="this.showPicker()"
                                max="{{ date('Y-m-d') }}"
                                min="{{ $dateFrom }}"
                                class="form-control shadow-none border-light-subtle custom-date-input"
                                style="height: 48px; border-radius: 12px; padding-left: 45px; cursor: pointer; transition: 0.2s;">
                        </div>
                    </div>
                    <div class="col-sm-2 col-6">
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
                        wire:target="activeTab, nextPage, previousPage, gotoPage, updatedSearch, refreshData, sortBy, updatedMethodsFilter, updatedDateFrom, updatedDateTo, updatedStatusFilter"
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
                            <small class="text-gray-500">Updating Registry</small>
                        </div>
                    </div>

                    <div class="table-responsive p-0"
                        style="max-height: 550px; transition: opacity 0.3s ease;"
                        wire:key="hq-applications">
                        <table id="table-data" class="table align-middle mb-0 p-0 table-head-fixed">
                            <thead>
                                <tr>
                                    <x-table-header
                                        field="receipt"
                                        :sortField="$sortField"
                                        :sortDirection="$sortDirection"
                                    >
                                        Receipt No.
                                    </x-table-header>
                                    <x-table-header
                                        field="transaction_date"
                                        :sortField="$sortField"
                                        :sortDirection="$sortDirection"
                                    >
                                        Trans. Date
                                    </x-table-header>
                                    <x-table-header
                                        field="group"
                                        :sortField="$sortField"
                                        :sortDirection="$sortDirection"
                                    >
                                        Group Name
                                    </x-table-header>
                                    <x-table-header
                                        field="amount"
                                        :sortField="$sortField"
                                        :sortDirection="$sortDirection"
                                    >
                                        Amount
                                    </x-table-header>
                                    <x-table-header
                                        field="method"
                                        :sortField="$sortField"
                                        :sortDirection="$sortDirection"
                                    >
                                        Payment Method
                                    </x-table-header>
                                    <x-table-header
                                        field="reference"
                                        :sortField="$sortField"
                                        :sortDirection="$sortDirection"
                                    >
                                        Reference No
                                    </x-table-header>
                                    <x-table-header
                                        field="status"
                                        :sortField="$sortField"
                                        :sortDirection="$sortDirection"
                                    >
                                        Status
                                    </x-table-header>
                                    <x-table-header :sortable="false">
                                        Action
                                    </x-table-header>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($this->receipts as $receipt)
                                    <tr wire:key="app-row-{{ $receipt->id }}" class="animate__animated animate__fadeIn" style="animation-delay: {{ $loop->index * 0.1 }}s;">
                                        <td class="px-4 py-3">
                                            <span class="text-sm text-gray-900">{{ $receipt->receipt_number }}</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="text-sm text-gray-900">{{ $receipt->transaction_date }}</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="text-sm text-gray-900">{{ $receipt->group_name }}</span>
                                        </td>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="text-sm text-gray-900">{{ number_format($receipt->amount, 2) }}</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="text-sm text-gray-900">{{ $receipt->payment }}</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="text-sm text-gray-900">{{ $receipt->reference_number }}</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            @if($receipt->status === 'active')
                                                <span class="align-items-center bg-gray-50 border border-gray-200 border-green- d-inline-flex font-medium px-2.5 py-1 rounded-lg text-gray-700 text-xs whitespace-nowrap">
                                                    Posted
                                                </span>
                                            @else
                                                <span class="bg-red-50 border-red-200 text-red-700 border d-inline-flex align-items-center font-medium px-2.5 py-1 rounded-lg text-xs whitespace-nowrap">
                                                    Canceled
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            @if($receipt->status === 'active')
                                                <div class="d-flex flex-row btn-group-sm">
                                                    @can('update receipt')
                                                    <x-modal-trigger
                                                            target="editReceipt({{ $receipt->id }})"
                                                            label=""
                                                            title="Edit Receipt"
                                                            icon="fas fa-edit"
                                                            variant="outline-primary"
                                                            defaultLabel=""
                                                        />
                                                    @endcan
                                                    @can('cancel receipt')
                                                        <x-modal-trigger
                                                            target="cancelReceipt({{ $receipt->id }})"
                                                            label=""
                                                            title="Cancel Receipt"
                                                            icon="fas fa-ban"
                                                            variant="outline-success"
                                                            defaultLabel=""
                                                            class="ml-1"
                                                        />
                                                    @endcan
                                                </div>
                                            @else
                                                <span class="text-gray-500 text-xs">
                                                    {{ $receipt->void_reason ?? 'N/A'}}
                                                </span>
                                            @endif

                                        </td>
                                    </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                        <h5 class="text-sm text-gray-500">No Receipts found</h5>
                                        @if($search || $methodsFilter || $dateFrom || $dateTo)
                                            <x-loading-button
                                                target="resetFilters"
                                                class="btn-refresh-premium"
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
            </div>
            <div class="card-footer clearfix bg-transparent">
                <div class="pagination-sm">
                    {{ $this->receipts()->links() }}
                </div>
            </div>
        </div>
    </div>

    <!--Edit receipt Modal -->
    <x-action-modal
        id="edit-receipt-modal"
        title="Edit Receipt"
        type="primary"
        icon="fas fa-money-check-alt"
        size="modal-lg"
        x-on:open-modal.window="if($event.detail.name === 'edit-receipt-modal') $($el).modal('show')"
        x-on:close-modal.window="if($event.detail.name === 'edit-receipt-modal') $($el).modal('hide')"
    >
        <div class="row">
            <div class="form-group col-md-6">
                <label class="text-uppercase font-bold text-gray-600 mb-2">Receipt Number</label>
                <div class="input-group premium-input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-hashtag text-info"></i></span>
                    </div>
                    <input type="text"
                        required
                        wire:model.live="receiptNumber"
                        class="form-control text-uppercase"
                        x-data
                        x-on:input="$el.value = $el.value.toUpperCase()"
                        placeholder="D68489101">
                </div>
                @error('receiptNumber') <small class="d-block text-success font-bold">{{ $message }}</small> @enderror
            </div>

            <div class="form-group col-md-6">
                <label class="text-uppercase font-bold text-gray-600 mb-2">Transaction Date</label>
                <div class="input-group premium-input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-calendar text-info"></i></span>
                    </div>

                    <input type="date"
                        wire:model.live="transactionDate"
                        onclick="this.showPicker()"
                        max="{{ date('Y-m-d') }}"
                        class="form-control custom-date-input">
                </div>
                @error('transactionDate') <small class="d-block text-success font-weight-bold">{{ $message }}</small> @enderror
            </div>

            <div class="form-group col-md-6">
                <label class="text-uppercase font-bold text-gray-600 mb-2">Amount </label>
                <div class="input-group premium-input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-money-bill-wave text-info"></i></span>
                    </div>
                    <input type="number"
                        required
                        wire:model.live="amountRepaid"
                        class="form-control"
                        placeholder="0.0">
                </div>
                @error('amountRepaid') <small class="d-block text-success font-weight-bold">{{ $message }}</small> @enderror
            </div>
            <div class="form-group col-md-6">
                <label class="text-uppercase font-bold text-gray-600 mb-2">Repayment Method </label>
                    <x-dropdown-search
                            model="repaymentMethod"
                            :options="$this->methodsOptions"
                            icon="fas fa-credit-card"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="select"
                        />
                @error('repaymentMethod') <small class="d-block text-success font-weight-bold">{{ $message }}</small> @enderror
            </div>
            <div class="form-group col-md-6">
                <label class="text-uppercase font-bold text-gray-600 mb-2">Reference Number </label>
                <div class="input-group premium-input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-fingerprint text-info"></i></span>
                    </div>

                    <input type="text"
                        required
                        wire:model.live="referenceNumber"
                        class="form-control text-uppercase"
                        x-data
                        x-on:input="$el.value = $el.value.toUpperCase()"
                        placeholder="TRHABHANKE6">
                </div>
                @error('referenceNumber') <small class="d-block text-success font-weight-bold">{{ $message }}</small> @enderror
            </div>
        </div>

        <x-slot name="footer">
            <div class="d-flex justify-content-center w-100">
                <x-pill-button
                    label="Cancel"
                    variant="outline"
                    color="info"
                    data-dismiss="modal"
                    icon="fas fa-times"
                    class="mx-1 shadow-sm btn-refresh-premium"
                />

                <x-pill-button
                    label="Confirm Edit"
                    target="confirmEditReceipt"
                    color="primary"
                    icon="fas fa-edit"
                    class="mx-1 shadow-sm btn-refresh-premium"
                />
            </div>
        </x-slot>
    </x-action-modal>

    <x-action-modal
        id="cancel-receipt-modal"
        title="Confirm Cancel Receipt"
        type="reject"
        icon="fa-times-circle"
        size="modal-md"
        x-on:open-modal.window="if($event.detail.name === 'cancel-receipt-modal') $($el).modal('show')"
        x-on:close-modal.window="if($event.detail.name === 'cancel-receipt-modal') $($el).modal('hide')"
        >
        <div class="text-center">
            <p class="mb-3 text-gray-900 font-weight-bold">Are you sure you want to cancel this Receipt: ({{$selectedReceipt?->receipt_number}})?</p>

            <div class="form-group mb-0 text-left">
                <label class="text-uppercase text-gray-600 font-weight-black small tracking-wider mb-2">
                    Detailed Explanation <span class="text-danger">*</span>
                </label>
                <div class="input-group premium-input-group" style="border-radius: 16px !important; overflow: hidden; border: 1px solid #ced4da;">
                    <div class="input-group-prepend align-items-start pt-3 pl-3">
                        <span class="input-group-text bg-transparent border-0 p-0">
                            <i class="fas fa-comment-dots text-danger"></i>
                        </span>
                    </div>
                    <textarea wire:model="reason"
                        class="form-control border-0 shadow-none py-3"
                        rows="4"
                        placeholder="Provide specific details (e.g. duplicate entry)..."
                        style="min-height: 120px; font-size: 0.95rem;"></textarea>
                </div>
                <div class="d-flex justify-content-between mt-1">
                    @error('reason')
                        <span class="text-danger small font-weight-bold d-block">{{ $message }}</span>
                    @enderror
                    <small class="text-gray-600 ml-auto">Min. 20 characters</small>
                </div>
            </div>

            <p class="text-gray-600">
                Confirming this action will mark the receipt as <strong>Cancelled</strong>. Use this only for data entry errors or bounced payments. The system will automatically recalculate the group's total collection.
            </p>
        </div>

        <x-slot name="footer">
            <x-pill-button
                label="No, keep."
                color="light"
                icon=""
                data-dismiss="modal"
                size="btn-lg"
                class="px-4 mr-2"
            />

            <x-pill-button
                target="confirmCancelReceipt"
                label="Yes, Confirm"
                color="danger"
                icon="fas fa-check"
                size="btn-lg"
                class="px-4 shadow-sm"
            />
        </x-slot>
    </x-action-modal>

    <x-action-modal
        id="add-receipt-modal"
        title="Add Receipt"
        type="primary"
        icon="fas fa-money-check-alt"
        size="modal-lg"
        x-on:open-modal.window="if($event.detail.name === 'add-receipt-modal') $($el).modal('show')"
        x-on:close-modal.window="if($event.detail.name === 'add-receipt-modal') $($el).modal('hide')"
    >
        <div class="row">
            <div class="form-group col-md-12">
                <label class="text-uppercase font-bold text-gray-600 mb-2">Group</label>

                <x-dropdown-search
                    model="groupId"
                    :options="$this->groupsOptions"
                    optionLabel="name"
                    optionValue="id"
                    placeholder="select group"
                    icon="fas fa-users"
                />
            </div>
            <div class="form-group col-md-6">
                <label class="text-uppercase font-bold text-gray-600 mb-2">Receipt Number</label>
                <div class="input-group premium-input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-hashtag text-info"></i></span>
                    </div>
                    <input type="text"
                        required
                        wire:model.live="receiptNumber"
                        class="form-control text-uppercase"
                        x-data
                        x-on:input="$el.value = $el.value.toUpperCase()"
                        placeholder="D68489101">
                </div>
                @error('receiptNumber') <small class="d-block text-success font-bold">{{ $message }}</small> @enderror
            </div>

            <div class="form-group col-md-6">
                <label class="text-uppercase font-bold text-gray-600 mb-2">Transaction Date</label>
                <div class="input-group premium-input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-calendar text-info"></i></span>
                    </div>

                    <input type="date"
                        wire:model.live="transactionDate"
                        onclick="this.showPicker()"
                        max="{{ date('Y-m-d') }}"
                        class="form-control custom-date-input">
                </div>
                @error('transactionDate') <small class="d-block text-success font-weight-bold">{{ $message }}</small> @enderror
            </div>

            <div class="form-group col-md-6">
                <label class="text-uppercase font-bold text-gray-600 mb-2">Amount </label>
                <div class="input-group premium-input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-money-bill-wave text-info"></i></span>
                    </div>
                    <input type="number"
                        required
                        wire:model.live="amountRepaid"
                        class="form-control"
                        placeholder="0.0">
                </div>
                @error('amountRepaid') <small class="d-block text-success font-weight-bold">{{ $message }}</small> @enderror
            </div>
            <div class="form-group col-md-6">
                <label class="text-uppercase font-bold text-gray-600 mb-2">Repayment Method </label>
                    <x-dropdown-search
                            model="repaymentMethod"
                            :options="$this->methodsOptions"
                            icon="fas fa-credit-card"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="select"
                        />
                @error('repaymentMethod') <small class="d-block text-success font-weight-bold">{{ $message }}</small> @enderror
            </div>
            <div class="form-group col-md-6">
                <label class="text-uppercase font-bold text-gray-600 mb-2">Reference Number </label>
                <div class="input-group premium-input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-fingerprint text-info"></i></span>
                    </div>

                    <input type="text"
                        required
                        wire:model.live="referenceNumber"
                        class="form-control text-uppercase"
                        x-data
                        x-on:input="$el.value = $el.value.toUpperCase()"
                        placeholder="TRHABHANKE6">
                </div>
                @error('referenceNumber') <small class="d-block text-success font-weight-bold">{{ $message }}</small> @enderror
            </div>
        </div>

        <x-slot name="footer">
            <div class="d-flex justify-content-center w-100">
                <x-pill-button
                    label="Cancel"
                    variant="outline"
                    color="info"
                    data-dismiss="modal"
                    icon="fas fa-times"
                    class="mx-1 shadow-sm btn-refresh-premium"
                />

                <x-pill-button
                    label="Add"
                    target="confirmAddReceipt"
                    color="primary"
                    icon="fas fa-check"
                    class="mx-1 shadow-sm btn-refresh-premium"
                />
            </div>
        </x-slot>
    </x-action-modal>

</section>
