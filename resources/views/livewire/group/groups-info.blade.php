<section class="content">
    <div class="container-fluid">
        <h5 class="text-muted font-weight-bold mb-3">Groups Summary (Women, Youth, and PWDs)</h5>
        <div class="row mt-4">
            @foreach($this->demographics as $d)
                @php
                    $totalIssued = $this->stats['totalAmountIssued'] ?? 0;
                    // Calculate share of money, capped at 100%
                    $percentage = ($totalIssued > 0 && $d['cat'])
                        ? min(100, round(($d['amount'] / $totalIssued) * 100))
                        : 0;
                @endphp

                <div class="col-lg-3 col-6">
                    <a href=""
                    class="text-decoration-none">
                        <div class="card card-hover shadow-sm border-0 mb-3">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="bg-light-{{ $d['color'] }} p-2 rounded-circle mr-3 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                                        <i class="fas {{ $d['icon'] }} text-{{ $d['color'] }} fa-lg"></i>
                                    </div>
                                    <div class="overflow-hidden">
                                        {{-- Primary Value: Money for demographics, Count for Registry --}}
                                        <h4 class="mb-0 font-weight-bold text-dark">
                                            {{ number_format($d['val']) }}
                                        </h4>
                                        <p class="text-muted text-xs text-uppercase mb-0 truncate-text">{{ $d['label'] }}</p>
                                    </div>
                                </div>

                                @if($d['cat'])
                                    <div class="mt-2">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="text-xs font-weight-bold text-muted">Capital Share</span>
                                            <span class="text-xs font-weight-bold text-{{ $d['color'] }}">{{ $percentage }}%</span>
                                        </div>
                                        <div class="progress rounded-pill" style="height: 4px;">
                                            <div class="progress-bar bg-{{ $d['color'] }}" role="progressbar" style="width: {{ $percentage }}%"></div>
                                        </div>
                                        <small class="text-xs text-muted mt-1 d-block font-weight-bold">
                                            KES {{ number_format($d['amount']) }}/=
                                        </small>
                                    </div>
                                @else
                                    <div class="mt-2 pt-1">
                                        <span class="text-xs text-primary font-weight-bold">
                                            <i class="fas fa-database mr-1"></i> Total Registry
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>

        <div class="card card-outline card-pink shadow">
            <div class="card-header border-0 py-3">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                    <h3 class="card-title font-weight-bold mb-3 mb-md-0 mr-md-4">
                        Application List
                    </h3>

                    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-md-end flex-md-grow-1" style="gap: 15px;">

                        <div class="input-group input-group-sm order-2 order-sm-1"
                            style="min-width: 250px; width: 100%; max-width: 450px;">
                            <input type="text"
                                wire:model.live.debounce.500ms="search"
                                class="form-control"
                                placeholder="Search by group name, gfs code, or group identifier...">
                            <div class="input-group-append">
                                <button class="btn btn-default" wire:loading.attr="disabled">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>

                        <div class="d-flex order-1 order-sm-2" style="gap: 8px;">
                            <div class="btn-group">
                                <x-loading-button
                                    title="refresh"
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

                            <x-loading-button
                                target="exportGroupList"
                                class="btn-dark"
                                icon="fas fa-file-excel"
                                label="Export Data" {{-- Added text back for desktop clarity --}}
                                loadingLabel="..."
                            />
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                @if ($search || $wardFilter || $categoryFilter || $sectorFilter)
                    <div class="d-flex flex-wrap align-items-center mb-3 p-2 bg-light rounded border shadow-sm" style="gap: 8px;">
                        <small class="text-muted font-weight-bold text-uppercase mr-2" style="font-size: 0.7rem;">
                            <i class="fas fa-filter mr-1"></i> Active Filters:
                        </small>

                        {{-- Search Chip --}}
                        @if($search)
                            <span class="badge badge-pill badge-primary py-2 px-3 shadow-sm">
                                Search: <strong>{{ $search }}</strong>
                                <i class="fas fa-times ml-2 cursor-pointer" wire:click="$set('search', '')" title="Remove search"></i>
                            </span>
                        @endif

                        {{-- Ward Chip --}}
                        @if($wardFilter)
                            <span class="badge badge-pill badge-success py-2 px-3 shadow-sm">
                                Ward: <strong>{{ $wardFilter }}</strong>
                                <i class="fas fa-times ml-2 cursor-pointer" wire:click="clearFilter('wardFilter')" title="Remove ward"></i>
                            </span>
                        @endif

                        {{-- Category Chip --}}
                        @if($categoryFilter)
                            <span class="badge badge-pill badge-warning py-2 px-3 shadow-sm">
                                Category: <strong>{{ $categoryFilter }}</strong>
                                <i class="fas fa-times ml-2 cursor-pointer" wire:click="clearFilter('categoryFilter')" title="Remove category"></i>
                            </span>
                        @endif

                        {{-- Sector Chip --}}
                        @if($sectorFilter)
                            <span class="badge badge-pill badge-info py-2 px-3 shadow-sm">
                                Sector: <strong>{{ $sectorFilter }}</strong>
                                <i class="fas fa-times ml-2 cursor-pointer" wire:click="clearFilter('sectorFilter')" title="Remove sector"></i>
                            </span>
                        @endif

                        {{-- Clear All Link --}}
                        <button type="button"
                                class="btn btn-link btn-sm text-danger font-weight-bold ml-auto"
                                wire:click="resetFilters">
                            Clear All
                        </button>
                    </div>
                @endif

                <div class="row mb-3">
                    <div class="col-md-3">
                        <x-select2
                            model="wardFilter"
                            label="Ward"
                            :options="$wards"
                            placeholder="All Wards"
                            optionLabel="ward_name"
                            optionValue="ward_name"
                        />
                    </div>
                    <div class="col-md-3">
                        @php
                            $category = [
                                'Women' => 'Women',
                                'Youth' => 'Youth',
                                'Pwd' => 'PWD',
                            ]
                        @endphp
                        <x-select2
                            model="categoryFilter"
                            label="Category"
                            :options="$category"
                            placeholder="All Categories"
                        />
                    </div>
                    <div class="col-md-3">
                        @php
                            $sector = [
                                'Trade' => 'Trade',
                                'Service' => 'Servie',
                                'Manufacturing' => 'Manufacturing',
                                'Agriculture' => 'Agriculture',
                                'Others' => 'Others',
                            ]
                        @endphp
                        <x-select2
                            model="sectorFilter"
                            label="Sector"
                            :options="$sector"
                            placeholder="All Sectors"
                        />
                    </div>
                    <div class="col-md-1">
                        <label for="perpage">Per Page</label>
                        <select wire:model.live="perPage" class="form-control form-control-sm">
                            <option value="10">10</option>
                            <option value="15">15</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                </div>
                <div class="position-relative">
                    <div wire:loading.delay.shortest
                        wire:target="nextPage, previousPage, gotoPage, refreshData, perPage, wardFilter, categoryfilter, sectorfilter, search"
                        class="w-100 h-100 position-absolute justify-content-center align-items-center"
                        style="background: rgba(255,255,255,0.7); z-index: 1000; top: 0; left: 0; min-height: 200px;">
                        <div class="text-center">
                            <i class="fas fa-circle-notch fa-spin fa-3x text-navy"></i>
                            <p class="text-navy font-weight-bold mt-2">Loading Data...</p>
                        </div>
                    </div>
                    <div class="table-responsive" style="overflow-x: auto; -webkit-overflow-scrolling: touch; max-height: 550px;" >
                        <table id="groups-table" class="table table-hover text-wrap table-head-fixed" style="min-width: 800px;">
                            <thead class="table-primary">
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th wire:click="sortBy('group_name')" class="cursor-pointer" style="user-select: none;">
                                        @include('partials.sort-icon', ['field' => 'group_name', 'label' => 'Group Name'])
                                    </th>
                                    <th wire:click="sortBy('gfs_code')" class="cursor-pointer" style="user-select: none;">
                                        @include('partials.sort-icon', ['field' => 'gfs_code', 'label' => 'GFS Code'])
                                    </th>
                                    <th wire:click="sortBy('ward_name')" class="cursor-pointer" style="user-select: none;">
                                        @include('partials.sort-icon', ['field' => 'ward_name', 'label' => 'Ward'])
                                    </th>
                                    <th>Category</th>
                                    <th>Sector</th>
                                    <th>Identifier</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="text-uppercase">
                                @forelse($groups as $index => $group)
                                    <tr>
                                        <td class="text-muted font-weight-normal" style="width: 50px;">
                                            {{ ($groups->currentPage() - 1) * $groups->perPage() + $loop->iteration }}
                                        </td>
                                        <td style="width: 300px; min-width: 280px; max-width: 300px; white-space: normal;">
                                            <div class="d-flex align-items-start">
                                                <div class="mr-3 bg-light rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                                    style="width: 35px; height: 35px;">
                                                    <i class="fas fa-users text-primary"></i>
                                                </div>

                                                <div style="overflow-wrap: break-word; word-wrap: break-word;">
                                                    <span class="d-block font-weight-bold text-dark mb-1" style="line-height: 1.2;">
                                                        {{ ucwords($group->name) }}
                                                    </span>

                                                    <div class="d-block">
                                                        <small class="text-muted d-block mb-1">
                                                            <i class="fas fa-stamp mr-1" style="font-size: 0.75rem;"></i>
                                                            REG #: <span class="font-weight-600">{{ $group->reg_number ?? 'N/A' }}</span>
                                                        </small>
                                                        <small class="text-muted d-block">
                                                            <i class="far fa-calendar-alt mr-1" style="font-size: 0.75rem;"></i>
                                                            {{ $group->date_registered ? \Carbon\Carbon::parse($group->date_registered)->format('d M Y') : 'Date N/A' }}
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-light border px-3 py-2 text-monospace"
                                                style="font-size: 0.8rem; letter-spacing: 1px; color: #2c3e50; font-weight: 700;">
                                                {{ $group->currentLoan->gfs_code ?? '---' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-dark"><i class="fas fa-map-marker-alt text-muted mr-1"></i> {{ $group->ward->ward_name }}</span>
                                        </td>
                                        <td>
                                            <span class="badge badge-outline-info" style="border: 1px solid #17a2b8; color: #17a2b8; background: transparent; padding: 4px 8px; border-radius: 4px; font-size: 10px;">
                                                {{ $group->currentLoan->category ?? 'N/A' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="text-truncate" style="max-width: 120px;" title="{{ $group->currentLoan->sector ?? '' }}">
                                                <span class="font-weight-600 text-secondary">{{ $group->currentLoan->sector ?? 'N/A' }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <small style="font-size: 0.9rem;" class="font-weight-bold text-dark">{{ $group->identifier ?? '---' }}</small>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group shadow-sm">
                                                <x-table-action-btn
                                                    type="view"
                                                    route="const.groups.group"
                                                    :params="[
                                                        'county' => $constituency->county->slug,
                                                        'constituency' => $constituency->slug,
                                                        'group' => $group->slug
                                                    ]"
                                                />
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    @endforelse
                            </tbody>
                            <tfoot>
                                <tr>
                                    {{--  --}}
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            <div class="card-footer clearfix">
                <div class="pagination-sm">
                    {{ $groups->links() }}
                </div>
            </div>
        </div>
    </div>
</section>

@push('styles')
<style>
    th.cursor-pointer {
        cursor: pointer;
        transition: background 0.2s ease;
    }

    th.cursor-pointer:hover {
        background-color: rgba(0,0,0,0.05); /* Subtle highlight on hover */
    }

    /* Ensures the flex container takes full width of the cell */
    th .d-flex {
        display: flex !important;
    }
    .badge-pill i:hover {
        color: #ff0000;
        transform: scale(1.2);
    }
    .badge-pill {
        display: inline-flex;
        align-items: center;
        transition: all 0.2s ease;
    }
</style>
<style>
    #groups-table {
        border-collapse: separate;
        border-spacing: 0;
    }
    /* Soften the table header */
    #groups-table thead th {
        background-color: #f8f9fa;
        color: #495057;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        border-top: none;
        border-bottom: 2px solid #dee2e6;
        vertical-align: middle;
    }
    /* Row styling */
    #groups-table tbody tr {
        transition: all 0.2s ease;
    }
    #groups-table tbody tr:hover {
        background-color: rgba(0, 123, 255, 0.03) !important;
        box-shadow: inset 4px 0 0 #007bff; /* Visual indicator on the left */
    }
    /* Cell padding */
    #groups-table td {
        padding: 12px 8px;
        vertical-align: middle;
        font-size: 0.85rem;
        border-bottom: 1px solid #f2f2f2;
    }
    /* Custom Scrollbar for the container */
    .table-responsive::-webkit-scrollbar {
        height: 6px;
    }
    .table-responsive::-webkit-scrollbar-thumb {
        background: #ced4da;
        border-radius: 10px;
    }
</style>

@endpush

@push('scripts')
    <script>
        document.addEventListener('livewire:init', function () {
            Livewire.on('flash-success', ({ message }) => { toastr.success(message); });
            Livewire.on('flash-error', ({ message }) => { toastr.error(message); });

            Livewire.on('clear-selects', () => {
                $('select.select2').val(null).trigger('change');
            });

            Livewire.on('clear-select-single', (event) => {
                const targetId = `#select2-${event.target}`;
                $(targetId).val(null).trigger('change');
            });
        });
    </script>
@endpush
