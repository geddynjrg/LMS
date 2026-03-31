<section class="content">
    <div class="container-fluid">
        <div class="row mb-3"
            x-data="{ show: false }"
            x-init="setTimeout(() => show = true, 50)"
            x-show="show"
            x-transition:enter="transition ease-out duration-500"
            x-transition:enter-start="opacity-0 transform translate-y-4"
            x-transition:enter-end="opacity-100 transform translate-y-0">

            <div class="col-12">
                <div class="card card-outline premium-summary-card shadow-sm border-0 mt-2">
                    <div class="card-header border-0 pb-0 bg-white">
                        <div class="d-flex justify-content-between align-items-center flex-wrap">
                            <div class="d-flex align-items-center mb-2">
                                {{-- Animated Icon Box --}}
                                <div class="icon-box-premium mr-3">
                                    <i class="fas fa-file-invoice-dollar fa-2x text-uwezo"></i>
                                    <div class="icon-pulse"></div>
                                </div>

                                <div>
                                    <h3 class="text-2xl">
                                        {{ $this->application->application_id }}
                                    </h3>
                                    <div class="d-flex align-items-center mt-1">
                                        @if(!is_null($this->application->phaseApplication))
                                                <span class="{{ $this->getStatusClasses($this->application->phaseApplication->status) }} border d-inline-flex align-items-center font-medium px-2.5 py-1 rounded-lg text-xs whitespace-nowrap">
                                                    {{ $this->getStatusLabel($this->application->phaseApplication->status) }}
                                                </span>
                                            @else
                                                <span class="{{ $this->getStatusClasses($this->application->status) }} border d-inline-flex align-items-center font-medium px-2.5 py-1 rounded-lg text-xs whitespace-nowrap">
                                                    {{ $this->getStatusLabel($this->application->status) }}
                                                </span>
                                            @endif

                                        <span class="text-gray-500 mx-2 opacity-5">|</span>

                                        <small class="text-gray-500 font-weight-bold phase-tag">
                                            <i class="fas fa-layer-group mr-1 text-primary"></i>
                                            {{ is_null($this->application->phaseApplication) ? 'Not Assigned' : $this->application->phaseApplication->reviewPhase->phase }}
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <div class="btn-group mb-2">
                                @if(!in_array($this->application->phaseApplication?->status, ['pending', 'rejected']))
                                <x-loading-button
                                    target="exportInstitutionalInfo"
                                    class="btn-refresh-premium"
                                    icon="fas fa-file-excel"
                                    label="Institutional Information"
                                />
                                @endif

                                <x-loading-button
                                    target="refreshData"
                                    class="btn-refresh-premium"
                                    icon="fas fa-sync-alt"
                                    label="Refresh Data"
                                />
                            </div>
                        </div>
                    </div>

                    {{-- Metrics Row with Hover Transitions --}}
                    <div class="card-body py-3 border-top mt-3 metrics-grid">
                        <div class="row text-center text-md-left">
                            <div class="col-sm col-6 metric-item border-right">
                                <label class="text-gray-900 text-uppercase text-xs">Submitted By</label>
                                <span class="text-gray-500 text-truncate">
                                    <i class="fas fa-user-circle mr-1 opacity-5"></i>
                                    {{ $this->application->identifier_type == 'EMAIL' ? $this->application->owner_identifier : $this->application->phone_number }}
                                </span>
                            </div>
                            <div class="col-sm col-6 metric-item border-right">
                                <label class="text-gray-900 text-uppercase text-xs">Group Name</label>
                                <span class="text-gray-500 text-truncate">{{ $this->application->group_name ?? 'N/A' }}</span>
                            </div>
                            <div class="col-sm col-6 metric-item border-right">
                                <label class="text-gray-900 text-uppercase text-xs">Amount Requested</label>
                                <span class="text-gray-500 text-truncate">KES {{ number_format($this->application->loan_amount, 2) }}</span>
                            </div>
                            <div class="col-sm col-6 metric-item">
                                <label class="text-gray-900 text-uppercase text-xs">Reporting Period</label>
                                <div class="mt-1 text-gray-500">
                                    <span class="fy-badge">{{ $currentFY->full }}</span>
                                    <span class="qtr-badge">{{ $currentFY->quarter_suffix }} Qtr</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-5">
            <div class="col-12 text-center">
                <div class="d-flex justify-content-center align-items-center text-gray-500">
                    <span class="mx-2"><i class="far fa-calendar-alt mr-1"></i> Submitted: {{ $this->application->created_at->format('M d, Y') }}</span>
                    <span class="mx-2 text-silver">|</span>
                    <span class="mx-2"><i class="far fa-user mr-1"></i> {{ $this->application->applicant_name ?? 'Individual Applicant' }}</span>
                </div>
            </div>
        </div>

        <div x-data="{
                activeTab: @entangle('activeTab').live,
                prevIndex: 0,
                tabs: ['all-details', 'members-details', 'loan-history', 'documents', 'audit-trail'],
                icons: {
                    'all-details': 'fas fa-list',
                    'members-details': 'fas fa-users',
                    'loan-history': 'fas fa-hand-holding-usd',
                    'documents': 'fas fa-folder-open',
                    'audit-trail': 'fas fa-history'
                },
                setActive(tab) {
                    this.prevIndex = this.tabs.indexOf(this.activeTab);
                    this.activeTab = tab;
                },
                get direction() {
                    let newIndex = this.tabs.indexOf(this.activeTab);
                    return newIndex > this.prevIndex ? 'forward' : 'backward';
                }
            }" class="w-100">
            <div class="row">
                <div class="col-12 d-flex justify-content-center">
                    <ul class="nav p-3 nav-pills custom-filament-tabs shadow-sm" id="applicationTabs" role="tablist">
                        <template x-for="(tab, index) in tabs" :key="index">
                            <li class="nav-item">
                                <a class="nav-link d-flex align-items-center"
                                :class="{ 'active': activeTab === tab }"
                                @click.prevent="setActive(tab)"
                                href="#">
                                    <i :class="icons[tab] + ' mr-2'"></i>

                                    <span x-text="tab.charAt(0).toUpperCase() + tab.slice(1).replace('-', ' ')"></span>

                                    <template x-if="tab === 'members-details'">
                                        <span class="badge badge-{{ $this->memberConflictCount > 0 ? 'danger' : 'secondary'}} ml-2">{{ $this->application->groupMembers->count() ?? 0 }}</span>
                                        @if($this->memberConflictCount > 0)
                                            <i class="fas fa-exclamation-circle text-danger ml-1" title="Duplicate members detected!"></i>
                                        @endif
                                    </template>
                                </a>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>

            <div class="row mt-3">
                <div class="{{ auth()->user()->can('approve application') ? 'col-md-9' : 'col-md-12' }}">
                    <div class="tab-content overflow-hidden">
                        <div x-show="activeTab === 'all-details'"
                            x-transition:enter="animate__animated animate__faster"
                            :class="direction === 'forward' ? 'animate__fadeInRight' : 'animate__fadeInLeft'"
                            class="card shadow-sm border-0">
                            <div class="card-body">
                                @include('partials.application.application-data')
                            </div>
                        </div>

                        <div x-show="activeTab === 'members-details'"
                            x-transition:enter="animate__animated animate__faster"
                            :class="direction === 'forward' ? 'animate__fadeInRight' : 'animate__fadeInLeft'"
                            style="display: none;"
                            class="card shadow-sm border-0">
                            <div class="card-body">
                                @include('partials.application.member-details')
                            </div>
                        </div>

                        <div x-show="activeTab === 'loan-history'"
                            x-transition:enter="animate__animated animate__faster"
                            :class="direction === 'forward' ? 'animate__fadeInRight' : 'animate__fadeInLeft'"
                            style="display: none;"
                            class="card shadow-sm border-0">
                            <div class="card-body">
                                @include('partials.application.loan-history')
                            </div>
                        </div>

                        <div x-show="activeTab === 'documents'"
                            x-transition:enter="animate__animated animate__faster"
                            :class="direction === 'forward' ? 'animate__fadeInRight' : 'animate__fadeInLeft'"
                            style="display: none;"
                            class="card shadow-sm border-0">
                            <div class="card-body">
                                @include('partials.application.documents')
                            </div>
                        </div>

                        <div x-show="activeTab === 'audit-trail'"
                            x-transition:enter="animate__animated animate__faster"
                            :class="direction === 'forward' ? 'animate__fadeInRight' : 'animate__fadeInLeft'"
                            style="display: none;"
                            class="card shadow-sm border-0">
                            <div class="card-body">
                                @include('partials.application.audit-trail')
                            </div>
                        </div>
                    </div>
                </div>


                <div class="col-md-3 order-first order-md-last d-flex flex-column">
                    <div class="sticky-top" style="top: 50px; z-index: 1020;">
                        @if(!is_null($this->application->phaseApplication))
                                <div class="card card-custom-hover shadow-sm mb-3"
                                    wire:ignore.self
                                    x-data="{ open: {{ is_null($this->application->phaseApplication) ? 'false' : 'true' }} }"
                                    @refresh-app-data="open = false">

                                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3"
                                        @click="open = !open" style="cursor: pointer;">
                                        <h6 class="font-weight-bold mb-0 text-gray-900 text-uppercase text-xs" style="letter-spacing: 0.5px;">
                                            <i class="fas fa-fingerprint mr-2 text-gray-500"></i> System Verification
                                        </h6>
                                        <div class="d-flex align-items-center">
                                            @if($this->application->phaseApplication && $this->application->phaseApplication->group_id)
                                                <span x-show="!open" class="badge badge-light border text-success mr-2" style="font-size: 0.7rem;">
                                                    <i class="fas fa-link mr-1"></i> LINKED
                                                </span>
                                            @endif
                                            <i class="fas fa-chevron-down text-gray-500 small transition-all" :class="open ? 'fa-rotate-180' : ''"></i>
                                        </div>
                                    </div>

                                    <div class="card-body" x-show="open" x-collapse>

                                            <div class="p-3 border rounded {{ $this->application->phaseApplication->group_id ? 'bg-light' : 'bg-white border-primary' }} mb-3">
                                                @if ($this->application->phaseApplication->group_id)
                                                    {{-- Display Linked Group Info --}}
                                                    <div class="d-flex align-items-start">
                                                        <div class="mr-3">
                                                            <i class="fas fa-users-cog text-dark fa-2x"></i>
                                                        </div>
                                                        <div class="flex-grow-1">
                                                            <small class="text-gray-900 d-block text-uppercase font-bold">Linked Master Record</small>
                                                            <p class="mb-0 text-gray-600">{{ $this->application->phaseApplication->linkedGroup->name }}</p>
                                                            <span class="text-gray-600 d-block text-xs">REG: {{$this->application->phaseApplication->linkedGroup->reg_number}}</span>
                                                            <code class="text-primary">GROUP CODE: {{ $this->application->phaseApplication->linkedGroup->code }}</code>
                                                        </div>
                                                    </div>
                                                @elseif($this->application->link_status === 'attempted_no_match')
                                                    <div class="text-center py-3 px-2 border rounded bg-light-info" style="border-style: dashed !important;">
                                                        {{-- Custom Icon with a "Plus" to signify NEW --}}
                                                        <div class="position-relative mb-2">
                                                            <i class="fas fa-search text-gray-500" style="font-size: 1.2rem;"></i>
                                                            <i class="fas fa-plus-circle text-info position-absolute" style="font-size: 0.8rem; bottom: -2px; right: 40%;"></i>
                                                        </div>

                                                        <h6 class="small font-weight-bold text-dark mb-1">New Group</h6>
                                                        <p class="x-small text-gray-500 mb-3 px-2">
                                                            No existing records found to match this identity. It will be treated as a <strong>new entry</strong>.
                                                        </p>
                                                    </div>
                                                @else
                                                    <div class="text-center py-3 px-2 border rounded bg-light-warning" style="border-style: dashed !important;">
                                                        <i class="fas fa-exclamation-triangle text-warning mb-2" style="font-size: 1.5rem;"></i>
                                                        <h6 class="small font-weight-bold text-dark mb-1">Linkage Pending</h6>
                                                        <p class="x-small text-gray-500 mb-3 px-2">
                                                            The system is still attempting to link this application to existing records. Please check back shortly.
                                                        </p>
                                                    </div>
                                                @endif
                                            </div>

                                            {{-- Decision Section --}}
                                            <div class="decision-zone border-top pt-3">
                                                @if($this->application->phaseApplication->status === 'pending_hq_approval' || $this->application->phaseApplication->status === 'rejected')
                                                    {{-- Historical Decision View --}}
                                                    <div class="text-center p-3 border rounded {{ in_array($this->application->phaseApplication->status, ['rejected']) ? 'border-success' : 'border-primary' }}">
                                                        <i class="fas {{ in_array($this->application->phaseApplication->status, ['rejected']) ? 'fa-times-circle text-success' : 'fa-check-circle text-primary' }} fa-2x mb-2"></i>
                                                        <h3 class=" text-sm font-bold mb-1">{{ strtoupper($this->application->phaseApplication->status) }}</h3>
                                                        <small class="text-gray-500 d-block">{{ $this->application->phaseApplication->updated_at->format('d M Y, H:i') }}</small>

                                                        <div class="mt-3 text-left">
                                                            <table class="table table-sm table-borderless mb-0">
                                                                <tr class="border-bottom">
                                                                    <td class="text-gray-600 pl-0">Vetted By:</td>
                                                                    <td class="text-right font-weight-bold pr-0">{{ $this->application->phaseApplication->user->name }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <td colspan="2" class="text-gray-600 pl-0 pt-2">Remarks:</td>
                                                                </tr>
                                                                <tr>
                                                                    <td colspan="2" class="bg-light p-2 rounded text-gray-900 italic">
                                                                        "{{ $this->application->phaseApplication->rejection_message ?? $this->application->phaseApplication->rejection_reason ?? 'No remarks provided.' }}"
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </div>


                                                    </div>
                                                @endif
                                            </div>

                                        @can('approve application')
                                        <div class="mt-4">
                                            @if($this->application->phaseApplication->status === 'pending_hq_approval')
                                            <x-modal-trigger
                                                class="pill-btn"
                                                icon='fas fa-times-circle'
                                                target="rejectApplication"
                                                label="Reject Application"
                                                variant="outline-primary"
                                                block="false" />
                                            @endif
                                        @endcan
                                        </div>
                                    </div>
                                </div>
                        @else
                            <div class="card shadow-sm border-0 border-top border-success mt-auto flex-shrink-0 animate__animated animate__fadeIn">
                                <div class="card-body">
                                    <span class="{{ $this->getStatusClasses($this->application->status) }} border align-items-center d-block text-center font-medium px-2.5 py-1 rounded-lg text-xs whitespace-nowrap">
                                        {{ $this->getStatusLabel($this->application->status) }}
                                    </span>
                                    <p class="text-xs text-gray-500 mb-0">No actions can be applied to this application at this stage.</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
        </div>
    </div>

    {{-- Member Profile Modal --}}
    <div
        wire:ignore.self
        class="modal fade"
        id="memberModal"
        tabindex="-1"
        role="dialog"
        aria-hidden="true"
        x-on:open-modal.window="if($event.detail.name === 'memberModal') $($el).modal('show')"
        x-on:close-modal.window="if($event.detail.name === 'memberModal') $($el).modal('hide')"
    >
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
                @if($selectedMember)
                    {{-- 1. MODAL HEADER: Minimalist --}}
                    <div class="modal-header border-bottom px-4 py-4 align-items-center bg-white">
                        <div class="d-flex align-items-center">
                            <div class="avatar-wrapper mr-4">
                                <div class="d-flex align-items-center justify-content-center shadow-sm"
                                    style="width: 65px; height: 65px; border-radius: 15px; font-size: 1.5rem; font-weight: 800; background: #f8fafc; color: #1e3a8a; border: 1px solid #e2e8f0;">
                                    {{ substr($selectedMember->name, 0, 1) }}
                                </div>
                            </div>
                            <div>
                                <h3 class="font-weight-bold text-dark mb-1" style="letter-spacing: -0.5px;">{{ $selectedMember->name }}</h3>
                                <div class="d-flex align-items-center">
                                    <span class="badge badge-light border text-primary px-2 py-1 mr-3" style="font-size: 0.7rem; font-weight: 700; text-uppercase: uppercase;">
                                        {{ $selectedMember->position ?? 'Member' }}
                                    </span>
                                    <span class="text-gray-500 small font-weight-medium">
                                        <i class="fas fa-fingerprint mr-1"></i> ID: {{ $selectedMember->id_number }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="close ml-auto outline-none" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true" class="text-gray-500">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body p-0">
                        <div class="row no-gutters">
                            {{-- Profile & Docs --}}
                            <div class="col-md-5 p-4 bg-light/30 border-right">
                                <label class="text-gray-500 small font-weight-bold text-uppercase tracking-widest mb-4 d-block">Personal Profile</label>

                                <div class="bg-white rounded-sm border p-3 mb-4 shadow-xs">
                                    <ul class="list-unstyled mb-0">
                                        <li class="d-flex justify-content-between mb-3 border-bottom pb-2">
                                            <span class="text-gray-500 small">Phone Number</span>
                                            <span class="font-weight-bold text-dark">{{ $selectedMember->phone_number }}</span>
                                        </li>
                                        <li class="d-flex justify-content-between mb-3 border-bottom pb-2">
                                            <span class="text-gray-500 small">Gender</span>
                                            <span class="font-weight-bold text-dark">{{ ucfirst($selectedMember->gender) }}</span>
                                        </li>
                                        <li class="d-flex justify-content-between">
                                            <span class="text-gray-500 small">Disability Status</span>
                                            <span class="font-weight-bold {{ $selectedMember->person_with_disability ? 'text-primary' : 'text-dark' }}">
                                                {{ $selectedMember->person_with_disability ? 'Registered PWD' : 'None' }}
                                            </span>
                                        </li>
                                    </ul>
                                </div>

                                <label class="text-gray-500 small font-weight-bold text-uppercase tracking-widest mb-3 d-block">Verified Documents</label>
                                <div class="d-flex flex-wrap" style="gap: 8px;">
                                    @if($selectedMember->photo_member_id)
                                        <a href="{{ $this->getMemberIdViewUrl($selectedMember->photo_member_id['path']) }}"
                                            target="_blank"
                                            class="align-items-center btn btn-outline-info d-inline-flex p-1 rounded-lg"
                                            title="View National ID">
                                                <i class="fas fa-eye mr-1"></i> View ID
                                        </a>
                                    @endif
                                    @if($selectedMember->pwd_certificate)
                                        <a href="{{ $this->getMemberIdViewUrl($selectedMember->pwd_certificate['path']) }}"
                                            target="_blank"
                                            class="align-items-center btn btn-outline-info d-inline-flex p-1 rounded-lg"
                                            title="View National ID">
                                                <i class="fas fa-eye mr-1"></i> View PWD Certificate
                                        </a>
                                    @endif
                                </div>
                            </div>

                            {{-- 3. RIGHT COLUMN: Risk Audit --}}
                            <div class="col-md-7 p-4 bg-white">
                                <label class="text-gray-500 small font-weight-bold text-uppercase tracking-widest mb-4 d-block">Credit Risk Assessment</label>

                                @if($selectedMember->is_defaulter)
                                    <div class="border overflow-hidden rounded-sm shadow-sm">
                                        {{-- Header: Professional & Urgent --}}
                                        <div class="bg-primary px-3 py-2 text-white small font-weight-bold text-uppercase tracking-wider d-flex justify-content-between align-items-center">
                                            <span><i class="fas fa-exclamation-triangle mr-2"></i> Critical: Active Default Records</span>
                                            <span class="badge badge-light px-2 py-1 text-gray-600">Exposure Detected</span>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table table-sm mb-0">
                                                <thead class="bg-light">
                                                    <tr class="text-gray-500" style="font-size: 0.6rem; text-transform: uppercase; letter-spacing: 1px;">
                                                        <th class="pl-3 py-2 border-0">Loan Entity & Location</th>
                                                        <th class="py-2 border-0 text-center">Role / Phase</th>
                                                        <th class="py-2 border-0 text-center">Disbursement</th>
                                                        <th class="py-2 border-0 text-right">Principal</th>
                                                        <th class="pr-3 py-2 border-0 text-right">Arrears</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($selectedMember->default_history as $history)
                                                        @php
                                                            $group = $history->groupLoan->group;
                                                            $loan = $history->groupLoan;
                                                        @endphp
                                                        <tr class="border-bottom">
                                                            {{-- 1. Entity & Location --}}
                                                            <td class="pl-3 py-3">
                                                                <div class="font-weight-bold text-dark mb-0" style="font-size: 0.9rem;">
                                                                    {{ $group->name }}
                                                                </div>
                                                                <div class="text-gray-500" style="font-size: 0.75rem;">
                                                                    <i class="fas fa-map-marker-alt mr-1"></i>
                                                                    {{ $group->ward->constituency->constituency_name ?? 'N/A' }}
                                                                    <span class="mx-1">•</span> {{ $group->ward->ward_name }}
                                                                </div>
                                                            </td>

                                                            {{-- 2. Role & Phase --}}
                                                            <td class="py-3 text-center align-middle">
                                                                <span class="badge badge-light border text-dark font-weight-bold d-block mb-1" style="font-size: 0.7rem;">
                                                                    {{ $history->role ?? 'N/A' }}
                                                                </span>
                                                                <span class="text-xs text-gray-500 font-weight-bold text-uppercase">{{ $loan->loanPhase->phase ?? 'N/A' }}</span>
                                                            </td>

                                                            {{-- 3. Disbursement Date --}}
                                                            <td class="py-3 text-center align-middle">
                                                                <div class="text-dark font-weight-medium" style="font-size: 0.8rem;">
                                                                    {{ $loan->disbursement_date ? \Carbon\Carbon::parse($loan->disbursement_date)->format('d M, Y') : 'N/A' }}
                                                                </div>
                                                                <div class="text-xs text-gray-500">Disbursed</div>
                                                            </td>

                                                            {{-- 4. Amount Issued --}}
                                                            <td class="py-3 text-right align-middle">
                                                                <div class="text-dark font-weight-bold" style="font-size: 0.85rem;">
                                                                    {{ number_format($loan->amount_issued, 2) }}
                                                                </div>
                                                                <div class="text-xs text-gray-500">Issued</div>
                                                            </td>

                                                            {{-- 5. Amount Defaulted --}}
                                                            <td class="pr-3 py-3 text-right align-middle">
                                                                <div class="font-weight-black" style="font-size: 1rem;">
                                                                    {{ number_format($loan->total_arrears, 2) }}
                                                                </div>
                                                                <div class="text-xs font-weight-bold">In Default</div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot class="bg-light">
                                                    <tr class="font-weight-bold">
                                                        <td colspan="4" class="pl-3 py-3 text-uppercase small tracking-wider text-gray-500">
                                                            Aggregated Financial Liability
                                                        </td>
                                                        <td class="text-right pr-3 py-3 text-danger h5 mb-0 font-weight-bold">
                                                            <small class="mr-1">KES</small>{{ number_format($selectedMember->default_history->sum(fn($h) => $h->groupLoan->total_arrears ?? 0), 2) }}
                                                        </td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                @else
                                    <div class="text-center py-5 border rounded-lg bg-light/20" style="border-style: dashed !important;">
                                        <div class="d-inline-flex align-items-center justify-content-center bg-white rounded-circle shadow-sm border mb-3" style="width: 60px; height: 60px;">
                                            <i class="fas fa-shield-alt text-primary fa-lg"></i>
                                        </div>
                                        <h6 class="font-weight-bold text-dark">Clear Credit History</h6>
                                        <p class="text-gray-500 small px-5">No defaults or non-performing loans were detected in the system for this member.</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- 4. MODAL FOOTER --}}
                    <div class="modal-footer border-top bg-light px-4 py-3">
                        <button type="button" class="btn btn-white border px-4 font-weight-bold shadow-xs" data-dismiss="modal">Close Profile</button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- reject application --}}
    <x-action-modal
            id="modal-reject-application"
            title="Reject Application"
            type="reject"
            icon="fa-user-times"
            size="modal-lg"
            x-on:open-modal.window="if($event.detail.name === 'modal-reject-application') $($el).modal('show')"
            x-on:close-modal.window="if($event.detail.name === 'modal-reject-application') $($el).modal('hide')"
        >
        <form wire:submit.prevent="confirmRejectApplication">
            {{-- Information Alert --}}
            <div class="alert alert-light border mb-4 py-2 px-3" style="border-radius: 12px; background-color: #fcf8f8;">
                <span class="text-gray-500">
                    <i class="fas fa-exclamation-triangle mr-1 text-primary"></i>
                    Please note that this action cannot be reversed.
                </span>
            </div>

            {{-- Primary Reason (Searchable Dropdown) --}}
            <div class="form-group mb-4 text-left">
                <label class="text-uppercase text-gray-500 font-weight-black small tracking-wider mb-2">
                    Primary Reason <span class="text-danger">*</span>
                </label>

                <x-dropdown-search
                    model="selectedRejectionKey"
                    :options="$this->formattedRejectionReasons"
                    icon="fas fa-list-ul"
                    optionLabel="name"
                    optionValue="id"
                    placeholder="Search rejection reason..."
                />

                @error('selectedRejectionKey')
                    <span class="text-danger small font-weight-bold mt-1 d-block">{{ $message }}</span>
                @enderror
            </div>

            {{-- Detailed Explanation --}}
            <div class="form-group mb-0 text-left">
                <label class="text-uppercase text-gray-500 font-weight-black small tracking-wider mb-2">
                    Detailed Explanation <span class="text-primary">*</span>
                </label>
                <div class="input-group premium-input-group" style="border-radius: 16px !important; overflow: hidden; border: 1px solid #ced4da;">
                    <div class="input-group-prepend align-items-start pt-3 pl-3">
                        <span class="input-group-text bg-transparent border-0 p-0">
                            <i class="fas fa-comment-dots text-primary"></i>
                        </span>
                    </div>
                    <textarea wire:model="rejectionComment"
                        class="form-control border-0 shadow-none py-3"
                        rows="4"
                        placeholder="Provide specific details (e.g. which documents are missing)..."
                        style="min-height: 120px; font-size: 0.95rem;"></textarea>
                </div>
                <div class="d-flex justify-content-between mt-1">
                    @error('rejectionComment')
                        <span class="text-danger small font-weight-bold d-block">{{ $message }}</span>
                    @enderror
                    <small class="text-gray-500 ml-auto">Min. 20 characters</small>
                </div>
            </div>

            <x-slot name="footer">
                <x-pill-button label="Cancel" variant="outline" color="dark" data-dismiss="modal" class="mx-1" />
                <x-pill-button
                    type="submit"
                    label="Confirm Rejection"
                    color="primary"
                    target="confirmRejectApplication"
                    icon="fas fa-check-circle"
                    class="mx-1 shadow-sm"
                />
            </x-slot>
        </form>
    </x-action-modal>

    {{-- next step --}}
    <x-action-modal
            id="modal-next-step"
            :title="$phaseCompleted ? 'Phase Fully Vetted!' : 'Action Successful!'"
            :type="$phaseCompleted ? 'approve' : 'info'"
            :icon="$phaseCompleted ? 'fa-check-double' : 'fa-check-circle'"
            x-on:open-modal.window="if($event.detail.name === 'modal-next-step') $($el).modal('show')"
            x-on:close-modal.window="if($event.detail.name === 'modal-next-step') $($el).modal('hide')"
        >
        @if($phaseCompleted)
            {{-- COMPLETION VIEW --}}
            <div class="text-center py-4">
                <div class="mb-4">
                    <i class="fas fa-medal text-success fa-5x animate__animated animate__bounceIn" style="filter: drop-shadow(0 4px 8px rgba(40,167,69,0.3))"></i>
                </div>
                <h5 class="font-weight-bold text-gray-500">Excellent Work! 🥳</h5>
                <p class="text-muted px-4">
                    You have successfully processed all applications in this phase. The queue is currently empty.
                </p>
                <div class="p-3 bg-light rounded-lg border mt-3 italic small text-success">
                    <i class="fas fa-info-circle mr-1"></i> You can now proceed to finalize the batch submission to HQ.
                </div>
            </div>
        @else

            {{-- SUMMARY VIEW (UP NEXT) --}}
            <div class="text-left mt-3">
                <div class="d-flex align-items-center mb-4 p-3 bg-light rounded border-left border-success" style="border-left-width: 5px !important;">
                    <div class="bg-success rounded-circle p-2 mr-3 text-white shadow-sm">
                        <i class="fas fa-check"></i>
                    </div>
                    <span class="text-gray-500 font-weight-bold">Current application finalized.</span>
                </div>
                @if($nextSummary)
                    <h6 class="text-uppercase text-muted small font-weight-black mb-2 tracking-widest">Up Next for Review:</h6>

                    <div class="list-group shadow-sm rounded-lg overflow-hidden">
                        <div class="list-group-item d-flex justify-content-between align-items-center border-0">
                            <span class="text-muted"><i class="fas fa-users text-primary mr-2"></i> Group Name</span>
                            <span class="font-weight-bold text-gray-500 text-right ml-2">{{ $nextSummary['group_name'] }}</span>
                        </div>

                        <div class="list-group-item d-flex justify-content-between align-items-center border-top border-bottom-0">
                            <span class="text-muted"><i class="fas fa-hashtag text-primary mr-2"></i> Application ID</span>
                            <span class="badge badge-light border text-monospace px-3 py-2">{{ $nextSummary['app_id'] }}</span>
                        </div>

                        <div class="list-group-item d-flex justify-content-between align-items-center bg-light border-0">
                            <span class="text-muted"><i class="fas fa-coins text-primary mr-2"></i> Amount</span>
                            <span class="text-success font-weight-black h6 mb-0">KES {{ $nextSummary['amount'] }}</span>
                        </div>
                    </div>
                @endif
            </div>

        @endif

        <x-slot name="footer">
            <x-pill-button
                label="Stay on Page"
                color="light"
                icon=""
                data-dismiss="modal"
                size="btn-lg"
                class="px-4 mr-2"
            />

            @if ($phaseCompleted)
                <a href="{{ $submissionUrl ?? 's' }}"

                wire:loading.attr="disabled"
                class="btn btn-primary pill-btn px-5 shadow-lg font-weight-bold">

                    <span wire:loading.remove wire:target="submissionUrl">
                        <i class="fas fa-file-export mr-2"></i> Proceed to Submission
                    </span>

                    <span wire:loading wire:target="submissionUrl">
                        <i class="fas fa-spinner fa-spin mr-2"></i> Preparing Submission...
                    </span>
                </a>
            @else
                <a href="{{ $nextSummary['url'] ?? '#' }}"

                wire:loading.attr="disabled"
                class="btn btn-primary pill-btn font-weight-bold px-5 shadow-lg animate__animated animate__pulse animate__infinite">

                    <span wire:loading.remove>
                        Review Next <i class="fas fa-arrow-right ml-2"></i>
                    </span>

                    <span wire:loading>
                        <i class="fas fa-circle-notch fa-spin mr-2"></i> Loading...
                    </span>
                </a>
            @endif
        </x-slot>
    </x-action-modal>
</section>
