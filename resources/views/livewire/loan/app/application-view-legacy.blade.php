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
                                @if($this->application->phaseApplication && $this->canDownloadTrainingCertificate())
                                    <a href="{{ route('training-certificate.download', $this->application->phaseApplication) }}"
                                       class="btn btn-refresh-premium btn-outline-primary"
                                       title="Download training certificate PDF">
                                        <i class="fas fa-file-download mr-1"></i> Training Certificate
                                    </a>
                                @elseif($notice = $this->trainingCertificateNotice())
                                    <span class="btn btn-light btn-sm text-muted" title="{{ $notice }}">
                                        <i class="fas fa-certificate mr-1"></i> Certificate pending
                                    </span>
                                @endif

                                @if($this->canShowDisbursementActions())
                                    <button type="button" wire:click="openMarkDisbursedModal" class="btn btn-refresh-premium btn-primary">
                                        <i class="fas fa-coins mr-1"></i> Mark Disbursed
                                    </button>
                                    <button type="button" wire:click="openNotDisbursedModal" class="btn btn-refresh-premium btn-outline-danger">
                                        <i class="fas fa-times-circle mr-1"></i> Not Disbursed
                                    </button>
                                @endif

                                @include('partials.application.send-group-sms-button')
                                <x-loading-button
                                    target="exportInstitutionalInfo"
                                    class="btn-refresh-premium"
                                    icon="fas fa-file-excel"
                                    label="Institutional Information"
                                />

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
                <div class="d-flex justify-content-center align-items-center text-gray-600">
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
                <div class="{{ auth()->user()->can('review application') ? 'col-md-9' : 'col-md-12' }}">
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

                @can('review application')
                <div class="col-md-3 d-flex flex-column">
                    <div class="sticky-top" style="top: 50px; z-index: 1020;">
                        {{-- phase assignement --}}
                            <div class="card card-custom-hover shadow-sm mb-3"
                                wire:ignore.self
                                x-data="{ open: {{ is_null($this->application->phaseApplication) ? 'true' : 'false' }} }"
                                {{-- When data is refreshed, close this card because the assignment is done --}}
                                @refresh-app-data="open = false">

                                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3"
                                    @click="open = !open"
                                    style="cursor: pointer; user-select: none;">

                                    <h6 class="font-weight-bold mb-0 text-gray-900 text-uppercase text-xs" style="letter-spacing: 0.5px;">
                                        <i class="fas fa-layer-group mr-2 text-gray-600"></i> Phase Assignment
                                    </h6>

                                    <div class="d-flex align-items-center">
                                        <span x-show="!open" x-transition.opacity class="badge badge-light border text-gray-600 mr-2" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                                            <i class="fas fa-expand-alt mr-1"></i> VIEW
                                        </span>
                                        <i class="fas fa-chevron-down text-gray-600 small transition-all"
                                        :class="open ? 'fa-rotate-180' : ''"
                                        style="transition: transform 0.3s ease;"></i>
                                    </div>
                                </div>

                                <div class="card-body" x-show="open" x-collapse>
                                    @if(is_null($this->application->phaseApplication))
                                        <div class="form-group mb-4">
                                            <label class="text-gray-600 mb-2">Target Review Phase</label>
                                            <x-dropdown-search
                                                model="selectedPhaseId"
                                                :options="$this->activePhases"
                                                optionLabel="phase"
                                                optionValue="id"
                                                placeholder="Choose Phase"
                                            />
                                        </div>
                                        <div class="text-center">
                                            <x-loading-button
                                                :title="!$selectedPhaseId ? 'Please select a phase first' : 'Click to assign'"
                                                target="assignToPhase"
                                                icon="fas fa-link mr-2"
                                                class="btn-outline-primary btn-refresh-premium "
                                                label="Assign Now"
                                                :disabled="!$selectedPhaseId"
                                            />
                                        </div>

                                    @else
                                        <div class="p-3 border rounded bg-light mb-0">
                                            <small class="text-gray-600 d-block text-uppercase font-weight-bold mb-1" style="font-size: 0.65rem;">Current Phase</small>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-check-circle text-success mr-2"></i>
                                                <span class="text-gray-900 font-weight-bold">{{ $this->application->phaseApplication->reviewPhase->phase }}</span>
                                            </div>

                                            @if($this->application->phaseApplication->status === 'pending')
                                                <div class="mt-3 pt-3 border-top text-center">
                                                    <x-loading-button
                                                        target="removeFromPhase"
                                                        icon="fas fa-unlink mr-2"
                                                        class="btn-outline-primary btn-refresh-premium"
                                                        label="Remove Assignment"
                                                    />
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>

                            @if(!is_null($this->application->phaseApplication))
                            <div class="card card-custom-hover shadow-sm mb-3"
                                wire:ignore.self
                                x-data="{ open: {{ is_null($this->application->phaseApplication) ? 'false' : 'true' }} }"
                                @refresh-app-data="open = false">

                                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3"
                                    @click="open = !open" style="cursor: pointer;">
                                    <h6 class="font-weight-bold mb-0 text-gray-900 text-uppercase text-xs" style="letter-spacing: 0.5px;">
                                        <i class="fas fa-fingerprint mr-2 text-gray-600"></i> System Verification
                                    </h6>
                                    <div class="d-flex align-items-center">
                                        @if($this->application->phaseApplication && $this->application->phaseApplication->group_id)
                                            <span x-show="!open" class="badge badge-light border text-success mr-2" style="font-size: 0.7rem;">
                                                <i class="fas fa-link mr-1"></i> LINKED
                                            </span>
                                        @endif
                                        <i class="fas fa-chevron-down text-gray-600 small transition-all" :class="open ? 'fa-rotate-180' : ''"></i>
                                    </div>
                                </div>

                                <div class="card-body" x-show="open" x-collapse>
                                    <label class="text-gray-600 font-weight-bold text-uppercase small d-block mb-3">1. Identity Status</label>
                                    {{-- Group Linking Section --}}

                                        <div class="p-3 border rounded {{ $this->application->phaseApplication->group_id ? 'bg-light' : 'bg-white border-primary' }} mb-3">
                                            @if ($this->application->phaseApplication->group_id)
                                                {{-- Display Linked Group Info --}}
                                                <div class="d-flex align-items-start">
                                                    <div class="mr-3">
                                                        <i class="fas fa-users-cog text-gray-900 fa-2x"></i>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <small class="text-gray-900 d-block text-uppercase font-bold">Linked Master Record</small>
                                                        <p class="mb-0 text-gray-600">{{ $this->application->phaseApplication->linkedGroup->name }}</p>
                                                        <span class="text-gray-600 d-block text-xs">REG: {{$this->application->phaseApplication->linkedGroup->reg_number}}</span>
                                                        <code class="text-primary">GROUP CODE: {{ $this->application->phaseApplication->linkedGroup->code }}</code>
                                                    </div>
                                                </div>

                                                @if($this->application->phaseApplication->status === 'pending')
                                                    <div class="mt-3 pt-2 border-top text-center">
                                                        <x-loading-button
                                                            target="removeLink"
                                                            icon="fas fa-unlink mr-1"
                                                            class="btn-outline-primary rounded-lg"
                                                            label="Unlink wrongly matched record"
                                                        />
                                                    </div>
                                                @endif
                                            @elseif($this->application->link_status === 'attempted_no_match')
                                                <div class="text-center py-3 px-2 border rounded bg-light-info" style="border-style: dashed !important;">
                                                    {{-- Custom Icon with a "Plus" to signify NEW --}}
                                                    <div class="position-relative mb-2">
                                                        <i class="fas fa-search text-gray-600" style="font-size: 1.2rem;"></i>
                                                        <i class="fas fa-plus-circle text-info position-absolute" style="font-size: 0.8rem; bottom: -2px; right: 40%;"></i>
                                                    </div>

                                                    <h6 class="font-weight-bold text-gray-900 mb-1">System Verified: New Group</h6>
                                                    <span class="text-xs text-gray-600 mb-3">
                                                        No existing master group matches this application. It will be treated as a <strong>new Wezesha entry</strong> upon approval.
                                                        @if(config('applications.auto_system_verification'))
                                                            <span class="d-block mt-1 text-info"><i class="fas fa-robot mr-1"></i> Verified automatically on phase assignment.</span>
                                                        @endif
                                                    </span>

                                                    <div class="d-flex flex-column mt-2">
                                                        @if($this->application->phaseApplication->status === 'pending')
                                                            <x-modal-trigger
                                                                target="findMatches"
                                                                label="Retry Search?"
                                                                icon="fas fa-sync-alt"
                                                                variant="outline-primary shadow-sm"
                                                            />
                                                        @endif
                                                    </div>
                                                </div>
                                            @else
                                                <div class="text-center py-2">
                                                    <i class="fas fa-search-location text-primary mb-2"></i>
                                                    <p class="text-xs text-gray-900 mb-2">Manual verification required — possible duplicate group matches found.</p>
                                                    <p class="text-xs text-muted mb-3">Review suggested matches and link the correct master record, or confirm as a new group.</p>
                                                    <x-modal-trigger
                                                        target="findMatches"
                                                        label="Review Matches"
                                                        icon="fas fa-fingerprint"
                                                        variant="primary"
                                                        size="sm" />
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Decision Section --}}
                                        <div class="decision-zone border-top pt-3">
                                            @if($this->application->phaseApplication->status === 'pending')
                                                <label class="text-gray-600 font-weight-bold text-uppercase small mb-3 d-block">2. Final Decision</label>
                                                 <div class="align-items-center btn-group d-flex flex-row mb-2 text-center">
                                                    <x-loading-button
                                                        target="approve"
                                                        title="{{$this->application->link_status === 'not_attempted'? 'Atempt to link the application first' : 'Approve'}}"
                                                        class="btn-refresh-premium"
                                                        icon="fas fa-check"
                                                        label="Approve"
                                                        :disabled="$this->application->link_status === 'not_attempted'"
                                                    />

                                                    <x-loading-button
                                                        target="rejectApplication"
                                                        class="btn-refresh-premium-danger"
                                                        icon="fas fa-times"
                                                        label="Reject"
                                                    />
                                                </div>
                                            @else
                                                {{-- Historical Decision View --}}
                                                <div class="text-center p-3 border rounded {{ in_array($this->application->phaseApplication->status, ['rejected']) ? 'border-success' : 'border-primary' }}">
                                                    <i class="fas {{ in_array($this->application->phaseApplication->status, ['rejected']) ? 'fa-times-circle text-success' : 'fa-check-circle text-primary' }} fa-2x mb-2"></i>
                                                    <h3 class=" text-sm font-bold mb-1">{{ strtoupper($this->application->phaseApplication->status) }}</h3>
                                                    <span class="text-gray-600 d-block text-xs">{{ $this->application->phaseApplication->updated_at->format('d M Y, H:i A') }}</span>


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

                                                    @if(in_array($this->application->phaseApplication->status, ['pending_submission_to_hq', 'rejected'] ) && $this->application->phaseApplication->reviewPhase->status === 'draft')

                                                        <x-loading-button
                                                        target="reviewDecision"
                                                        class="btn-refresh-premium btn-sm mt-3"
                                                        icon="fas fa-undo"
                                                        label="Review Decision"
                                                        />
                                                    @endif
                                                </div>
                                            @endif
                                        </div>

                                    {{-- Navigation --}}
                                    <div class="mt-3 d-flex flex-column align-items-center text-center border-top pt-3">
                                        <x-table-action-btn
                                                type="back"
                                                inline=""
                                                class="btn-block"
                                                title="Back To List"
                                                route="const.app.app_dashboard"
                                                :params="[
                                                    'county' => $county->slug,
                                                    'constituency' => $constituency->slug
                                                ]"
                                        > Back to List </x-table-action-btn>
                                    </div>
                                </div>
                            </div>
                            @endif

                    </div>
                </div>
                @endcan
            </div>
        </div>
    </div>


    {{-- remove phase --}}
    <x-action-modal
        id="modal-remove-from-phase"
        title="Remove from Phase"
        type="info"
        icon="fas fa-times"
        size="modal-md"
        x-on:open-modal.window="if($event.detail.name === 'modal-remove-from-phase') $($el).modal('show')"
        x-on:close-modal.window="if($event.detail.name === 'modal-remove-from-phase') $($el).modal('hide')"
        >
        <div class="text-center">
            <div class="mb-4 d-flex justify-content-center align-items-center">
                <div class="badge badge-light p-3 shadow-sm" style="border-radius: 12px; border: 1px solid #e2e8f0;">
                    <span class="text-gray-600 text-xs d-block mb-1">Current Phase</span>
                    <span class="text-primary font-weight-bold h6 mb-0">{{ $this->application->phaseApplication->reviewPhase->phase ?? 'Standard Processing' }}</span>
                </div>
                <i class="fas fa-arrow-right mx-3 text-gray-500"></i>
                <div class="badge badge-light p-3 shadow-sm" style="border-radius: 12px; border: 1px dashed #cbd5e1;">
                    <span class="text-gray-600 d-block mb-1">Destination</span>
                    <span class="text-secondary font-weight-bold h6 mb-0">Pending Queue</span>
                </div>
            </div>

            <p class="mb-1 text-gray-900 font-weight-bold">Confirm Phase Removal?</p>
            <p class="text-gray-600">
                This will remove the application from the current processing track.
                All <span class="text-info font-weight-bold">assessment data</span> will be preserved, but the phase status will reset to 'Unassigned'.
            </p>
        </div>

        <x-slot name="footer">
            <div class="d-flex justify-content-center w-100">
                {{-- Cancel Button --}}
                <x-pill-button
                    label="Keep in Phase"
                    variant="outline"
                    color="secondary"
                    data-dismiss="modal"
                    icon="fas fa-times"
                    class="mx-1 btn-refresh-premium"
                />

                {{-- Action Button --}}
                <x-pill-button
                    label="Confirm Removal"
                    target="confirmRemoveFromPhase"
                    color="info"
                    icon="fas fa-undo-alt"
                    class="mx-1 btn-refresh-premium"
                />
            </div>
        </x-slot>
    </x-action-modal>

    {{-- link modal --}}
    <div wire:ignore.self
        class="modal fade"
        id="modal-group-linker"
        tabindex="-1"
        role="dialog"
        x-on:open-modal.window="if($event.detail.name === 'modal-group-linker') $($el).modal('show')"
        x-on:close-modal.window="if($event.detail.name === 'modal-group-linker') $($el).modal('hide')"
        >
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">

                {{-- Header: Dynamic Gradient based on Mode --}}
                <div class="modal-header pt-4 px-4 border-bottom-0 bg-primary">
                    <h5 class="modal-title text-white font-weight-bold">
                        <i class="fas {{ $isManualSearch ? 'fa-search' : 'fa-magic' }} mr-2 animate__animated animate__pulse animate__infinite"></i>
                        {{ $isManualSearch ? 'Manual Group Search' : 'Intelligent Group Suggestions' }}
                    </h5>
                    <button type="button" class="close text-white opacity-10" data-dismiss="modal"><span>&times;</span></button>
                </div>

                <div class="modal-body p-4">
                    {{-- Mode Switcher & Context --}}
                    <div class="row align-items-center mb-4">
                        <div class="col-md-7">
                            @if(!$isManualSearch)
                                <div class="d-flex align-items-center p-3 bg-light rounded-lg border-left border-info" style="border-left-width: 5px !important;">
                                    <div class="mr-3 text-info"><i class="fas fa-robot fa-2x"></i></div>
                                    <div>
                                        <small class="text-uppercase font-weight-bold text-info tracking-wider" style="font-size: 0.7rem;">Matching For:</small>
                                        <div class="h6 mb-0 font-weight-bold">{{ $this->application->group_name }} <span class="text-gray-600 font-weight-normal ml-2">({{ $this->application->group_registration_number }})</span></div>
                                    </div>
                                </div>
                            @else
                                <div class="position-relative">
                                    <input type="text" wire:model.live.debounce.300ms="searchTerm"
                                        class="form-control form-control-lg shadow-sm border-primary pl-5"
                                        style="border-radius: 12px; font-size: 1.1rem;"
                                        placeholder="Search Name, Reg Number, or Code...">
                                    <div class="position-absolute" style="top: 12px; left: 18px;">
                                        <i wire:loading.remove wire:target="searchTerm" class="fas fa-search text-primary"></i>
                                        <i wire:loading wire:target="searchTerm" class="fas fa-circle-notch fa-spin text-primary"></i>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="col-md-5 text-right mt-3 mt-md-0">
                            <button wire:click="toggleSearchMode" class="btn {{ $isManualSearch ? 'btn-outline-info' : 'btn-outline-primary' }} px-4 shadow-sm rounded-pill font-weight-bold">
                                <i class="fas {{ $isManualSearch ? 'fa-bolt' : 'fa-keyboard' }} mr-2"></i>
                                Switch to {{ $isManualSearch ? 'Auto Suggestions' : 'Manual Search' }}
                            </button>
                        </div>
                    </div>

                    {{-- Results Table --}}
                    <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                        <h6 class="text-uppercase text-gray-600 font-weight-black small tracking-widest">Available Matches</h6>
                        <span class="badge badge-light border text-gray-600 px-3 py-2">{{ count($suggestedMatches) }} Results Found</span>
                    </div>

                    <div class="table-responsive rounded-lg border shadow-sm" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover mb-0">
                            <thead  id="table-data"class="bg-light sticky-top" style="z-index: 5;">
                                <tr class="text-gray-600 small text-uppercase">
                                    <x-table-header :sortable="false">
                                        Match %
                                    </x-table-header>
                                    <x-table-header :sortable="false">
                                        Group Identity
                                    </x-table-header>
                                    <x-table-header :sortable="false">
                                        Location & Code
                                    </x-table-header>
                                    <x-table-header :sortable="false">
                                        Current Phase
                                    </x-table-header>
                                    <x-table-header :sortable="false">
                                        Logic
                                    </x-table-header>
                                    <x-table-header :sortable="false">
                                        Action
                                    </x-table-header>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($suggestedMatches as $match)
                                    <tr class="{{ $match->match_score > 90 ? 'bg-soft-success' : '' }}">
                                        <td class="px-4 py-3">
                                            <div class="progress shadow-sm" style="height: 10px; width: 60px; border-radius: 10px;">
                                                <div class="progress-bar {{ $match->match_score > 80 ? 'bg-primary' : 'bg-info' }}"
                                                    role="progressbar" style="width: {{ $match->match_score }}%"></div>
                                            </div>
                                            <span class="mt-1 text-xs d-block text-center">{{ number_format($match->match_score, 0) }}%</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="text-gray-900 mb-0">{{ $match->name }}</span>
                                            @if($match->reg_matched)
                                                <span class="badge badge-success-light px-2 py-1 small mt-1">
                                                    <i class="fas fa-check-double mr-1"></i> Reg Match
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="d-block text-gray-900">{{ $match->ward->ward_name }}</span>
                                            <span class="text-gray-600 text-xs">Group Code: {{ $match->code }}</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="bg-amber-50 border-amber-200 text-amber-700 border d-inline-flex align-items-center font-medium px-2.5 py-1 rounded-lg text-xs whitespace-nowrap">
                                                {{ $match->currentLoan?->loanPhase->phase ?? 'New Cycle' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3" style="max-width: 200px;">
                                            @if(is_array($match->match_reasons) || is_object($match->match_reasons))
                                                @foreach($match->match_reasons as $reason)
                                                    <span class="d-block text-gray-900 text-xs {{ $reason['class'] }} mb-1">
                                                        <i class="fas {{ $reason['icon'] }} mr-1"></i> {{ $reason['text'] }}
                                                    </span>
                                                @endforeach
                                            @else
                                                <span class="d-block text-gray-600 small">No specific match details</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            <button wire:click="confirmLink({{ $match->id }})"
                                                    class="btn btn-primary btn-sm px-3 rounded-pill shadow-sm font-weight-bold">
                                                <span wire:loading.remove wire:target="confirmLink({{ $match->id }})">Link</span>
                                                <i wire:loading wire:target="confirmLink({{ $match->id }})" class="fas fa-circle-notch fa-spin"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <div class="py-4">
                                                <i class="fas fa-search-minus fa-3x text-gray-600 mb-3"></i>
                                                <h5 class="text-gray-600">No Matching Groups Found</h5>
                                                <p class="text-gray-600 small">Try adjusting your search or mark this as a new group.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 px-4 py-3 d-flex justify-content-between">

                    <x-pill-button
                        label="Cancel"
                        color="outline-info"
                        icon=""
                        data-dismiss="modal"
                        size="btn-lg"
                        class="px-4 mr-2"
                    />

                    <x-pill-button
                        target="markAsNoMatch"
                        label="No Matches Exist (Register as New)"
                        color="primary"
                        icon="fas fa-plus-circle"
                        size="btn-lg"
                        class="px-4 shadow-sm"
                    />
                </div>
            </div>
        </div>
    </div>

    {{-- unlink modal --}}
    <x-action-modal
            id="modal-unlink-confirm"
            title="Confirm Unlink"
            type="reject"
            icon="fa-unlink"
            size="modal-md"
            x-on:open-modal.window="if($event.detail.name === 'modal-unlink-confirm') $($el).modal('show')"
            x-on:close-modal.window="if($event.detail.name === 'modal-unlink-confirm') $($el).modal('hide')"
        >
        <div class="text-center">
            <p class="mb-1 text-gray-900 font-weight-bold">Break this connection? 🧐</p>
            <p class="text-gray-600 px-3">
                You are about to unlink this application from the master record
                <span class="text-danger font-weight-bold">{{ $this->application->phaseApplication->linkedGroup->name ?? 'this group' }}</span>.
                The application will return to an <span class="font-weight-bold">unlinked status</span>.
            </p>
        </div>


        <x-slot name="footer">
            <x-pill-button
                label="No, keep linked"
                color="light"
                icon=""
                data-dismiss="modal"
                size="btn-lg"
                class="px-4 mr-2 shadow-sm btn-refresh-premium"
            />

            <x-pill-button
                target="confirmRemoveLink"
                label="Yes, Unlink Record"
                color="danger"
                icon="fas fa-unlink"
                size="btn-lg"
                class="px-4 shadow-sm btn-refresh-premium"
            />
        </x-slot>
    </x-action-modal>

    {{-- modal approve --}}
    <x-action-modal
            id="modal-approve"
            title="Approve Application"
            type="approve"
            icon="fa-check-circle"
            size="modal-xl"
            x-on:close-modal.window="if($event.detail.name === 'modal-approve') $($el).modal('hide')"
        >
        <div class="row">
            <div class="col-md-5 border-right pr-4">

                <div class="applied-amount-badge mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-uppercase small font-weight-bold opacity-75">Applied Amount</span>
                        <i class="fas fa-file-invoice-dollar opacity-50"></i>
                    </div>
                    <div class="h3 font-weight-bold mb-0">
                        <span class="font-weight-normal mr-1">KES</span>{{ number_format($this->application->loan_amount, 2) }}
                    </div>
                </div>

                @if($this->application->link_status !== 'linked')
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-primary uppercase">
                            Group Code <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                            wire:model.live.lazy="groupCode"
                            class="form-control"
                            placeholder="Enter group identification number (e.g. 001)">
                        @error('groupCode') <span class="text-success font-bold text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group mb-4">
                        <label class="font-weight-bold text-primary uppercase mb-2 d-block">
                            Generated GFS Code
                        </label>
                        <div class="gfs-container shadow-sm">
                            <div class="gfs-icon-section d-none d-sm-flex">
                                <i class="fas fa-barcode"></i>
                                <div class="scanner-line"></div>
                            </div>
                            <div class="gfs-code-section">
                                <span class="gfs-prefix">{{ $gfsCode }}</span>
                                <span class="gfs-dynamic-code">{{ $groupCode }}</span>
                            </div>
                        </div>
                        <span class="text-gray-600 mt-2">
                            <i class="fas fa-info-circle mr-1"></i> Unique identifier for financial tracking.
                        </span>
                    </div>

                    <hr class="my-4" style="border-top: 2px dashed #f1f5f9;">
                @endif

                @if ($this->application->ward_id === null)
                    <div class="form-group mb-0">
                        <label class="font-weight-bold text-primary uppercase">Assign Correct Ward</label>
                        <div class="select-wrapper">
                            <select wire:model.live="selectedWardId" class="custom-select">
                                <option value="">-- Select Ward --</option>
                                @foreach($constituency->wards as $ward)
                                    <option value="{{ $ward->id }}">{{ $ward->ward_name }}</option>
                                @endforeach
                            </select>
                            @error('selectedWardId') <span class="text-success text-xs font-bold">{{ $message }}</span> @enderror
                        </div>
                    </div>
                @endif
            </div>

            <div class="col-md-7 pl-4">
                <div class="form-group mb-4">
                    <label class="font-weight-bold text-primary uppercase mb-2 d-block">Final Approved Amount (KES) *</label>
                    <div class="finance-input-wrapper">
                        <span class="currency-label">KES</span>
                        <input type="number"
                            data-approval-amount
                            wire:model="approvalAmount"
                            step="1"
                            min="0"
                            class="finance-input @error('approvalAmount') is-invalid @enderror"
                            placeholder="0">
                    </div>
                    @error('approvalAmount') <span class="text-success text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label class="font-weight-bold text-primary uppercase">Approval Remarks (Optional) </label>
                    <textarea wire:model="approvalComments"
                            class="form-control custom-input"
                            rows="4"
                            placeholder="Internal notes regarding this approval decision..."></textarea>
                </div>
            </div>
        </div>

        <x-slot name="footer">
            @if($this->eligibility['can_borrow'])
                <x-pill-button
                    label="Close"
                    color="info"
                    icon=""
                    data-dismiss="modal"
                    class="px-4 mr-2 shadow-sm btn-refresh-premium"
                />

                <x-pill-button
                    target="confirmApproval"
                    label="Confirm Approval"
                    color="primary"
                    icon="fas fa-check-circle"
                    class="px-4 shadow-sm btn-refresh-premium"
                />
            @else
                <div class="alert bg-light border w-100 mb-0 d-flex justify-content-between align-items-center" style="border-radius: 50px;">
                    <span class="text-danger font-weight-bold ml-3">
                        <i class="fas fa-ban mr-2"></i> Blocked: Uncleared Balance (KES {{ number_format($this->eligibility['balance']) }})
                    </span>
                    <button type="button" class="btn btn-refresh-premium shadow-sm px-4" data-dismiss="modal" style="border-radius: 50px;">Close</button>
                </div>
            @endif
        </x-slot>
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
            <div class="text-center py-4">
                <div class="mb-4">
                    <i class="fas fa-medal text-primary fa-5x animate__animated animate__bounceIn" style="filter: drop-shadow(0 4px 8px rgba(40,167,69,0.3))"></i>
                </div>
                <h5 class="font-weight-bold text-gray-900">Excellent Work! 🥳</h5>
                <p class="text-gray-600 px-4">
                    You have successfully processed all applications in this phase. The queue is currently empty.
                </p>
                <div class="p-3 bg-light rounded-lg border mt-3 italic small text-primary">
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
                    <span class="text-gray-900 font-weight-bold">Current application finalized.</span>
                </div>
                @if($nextSummary)
                    <h6 class="text-uppercase text-gray-600 small font-weight-black mb-2 tracking-widest">Up Next for Review:</h6>

                    <div class="list-group shadow-sm rounded-lg overflow-hidden">
                        <div class="list-group-item d-flex justify-content-between align-items-center border-0">
                            <span class="text-gray-600"><i class="fas fa-users text-primary mr-2"></i> Group Name</span>
                            <span class="font-weight-bold text-gray-900 text-right ml-2">{{ $nextSummary['group_name'] }}</span>
                        </div>

                        <div class="list-group-item d-flex justify-content-between align-items-center border-top border-bottom-0">
                            <span class="text-gray-600"><i class="fas fa-hashtag text-primary mr-2"></i> Application ID</span>
                            <span class="badge badge-light border text-monospace px-3 py-2">{{ $nextSummary['app_id'] }}</span>
                        </div>

                        <div class="list-group-item d-flex justify-content-between align-items-center bg-light border-0">
                            <span class="text-gray-600"><i class="fas fa-coins text-primary mr-2"></i> Amount</span>
                            <span class="text-primary font-weight-black h6 mb-0">KES {{ $nextSummary['amount'] }}</span>
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
                class="btn btn-primary pill-btn px-5 shadow-sm font-weight-bold">

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
                class="btn btn-primary pill-btn font-weight-bold px-5 shadow-sm animate__animated animate__pulse animate__infinite">

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

    {{-- reject application --}}
    <x-action-modal
            id="modal-reject-application"
            title="Reject Application"
            type="reject"
            icon="fa-user-times"
            size="modal-md"
            x-on:open-modal.window="if($event.detail.name === 'modal-reject-application') $($el).modal('show')"
            x-on:close-modal.window="if($event.detail.name === 'modal-reject-application') $($el).modal('hide')"
        >
        <form wire:submit.prevent="confirmRejectApplication">
            {{-- Information Alert --}}
            <div class="alert alert-light border mb-4 py-2 px-3" style="border-radius: 12px; background-color: #fcf8f8;">
                <small class="text-gray-600">
                    <i class="fas fa-exclamation-triangle mr-1 text-danger"></i>
                    Please categorize the rejection and provide specific details below.
                </small>
            </div>

            {{-- Primary Reason (Searchable Dropdown) --}}
            <div class="form-group mb-4 text-left">
                <label class="text-uppercase text-gray-600 font-weight-black small tracking-wider mb-2">
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
                <label class="text-uppercase text-gray-600 font-weight-black small tracking-wider mb-2">
                    Detailed Explanation <span class="text-danger">*</span>
                </label>
                <div class="input-group premium-input-group" style="border-radius: 16px !important; overflow: hidden; border: 1px solid #ced4da;">
                    <div class="input-group-prepend align-items-start pt-3 pl-3">
                        <span class="input-group-text bg-transparent border-0 p-0">
                            <i class="fas fa-comment-dots text-danger"></i>
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
                    <small class="text-gray-600 ml-auto">Min. 20 characters</small>
                </div>
            </div>

            <x-slot name="footer">
                <x-pill-button label="Cancel" variant="outline" color="dark" data-dismiss="modal" class="mx-1" />
                <x-pill-button
                    type="submit"
                    label="Confirm Rejection"
                    color="danger"
                    target="confirmRejectApplication"
                    icon="fas fa-check-circle"
                    class="mx-1 shadow-sm"
                />
            </x-slot>
        </form>
    </x-action-modal>

    {{-- review decision --}}
    <x-action-modal
        id="modal-review-decision"
        title="Review Decision: {{ ucfirst($reviewType ?? 'Decision') }}"
        :type="$reviewType === 'approval' ? 'info' : 'reject'"
        :icon="$reviewType === 'approval' ? 'fa-check-circle' : 'fa-times-circle'"
        size="modal-md"
        x-on:open-modal.window="if($event.detail.name === 'modal-review-decision') $($el).modal('show')"
        x-on:close-modal.window="if($event.detail.name === 'modal-review-decision') $($el).modal('hide')"
        >
        <div class="text-center">
            <p class="mb-3 text-gray-900 font-weight-bold">Are you sure you want to review this decision?</p>

            @if($reviewType === 'rejection' && $rejectionReason)
                <div class="p-3 bg-light border rounded mb-3 text-left">
                    <small class="text-gray-600 d-block text-uppercase font-weight-bold mb-1" style="letter-spacing: 1px;">
                        Previous Rejection Reason:
                    </small>
                    <span class="text-danger font-italic">"{{ $rejectionReason }}"</span>
                </div>
            @endif

            <p class="text-gray-600 text-xs">
                By confirming, the application status will be reset, allowing for a new assessment.
                This action will be logged in the audit trail.
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
                target="confirmReviewDecision"
                label="Yes, Confirm Review"
                color="danger"
                icon="fas fa-check"
                size="btn-lg"
                class="px-4 shadow-sm"
            />
        </x-slot>
    </x-action-modal>

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
                    <div class="modal-header border-bottom px-4 py-4 align-items-center bg-white">
                        <div class="d-flex align-items-center">
                            <div class="avatar-wrapper mr-4">
                                <div class="d-flex align-items-center justify-content-center shadow-sm"
                                    style="width: 65px; height: 65px; border-radius: 15px; font-size: 1.5rem; font-weight: 800; background: #f8fafc; color: #1e3a8a; border: 1px solid #e2e8f0;">
                                    {{ substr($selectedMember->name, 0, 1) }}
                                </div>
                            </div>
                            <div>
                                <h3 class="font-weight-bold text-gray-900 mb-1" style="letter-spacing: -0.5px;">{{ $selectedMember->name }}</h3>
                                <div class="d-flex align-items-center">
                                    <span class="badge badge-light border text-primary px-2 py-1 mr-3" style="font-size: 0.7rem; font-weight: 700; text-uppercase: uppercase;">
                                        {{ $selectedMember->position ?? 'Member' }}
                                    </span>
                                    <span class="text-gray-600 small font-weight-medium">
                                        <i class="fas fa-fingerprint mr-1"></i> ID: {{ $selectedMember->id_number }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="close ml-auto outline-none" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true" class="text-gray-600">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body p-0">
                        <div class="row no-gutters">
                            <div class="col-md-5 p-4 bg-light/30 border-right">
                                <label class="text-gray-600 small font-weight-bold text-uppercase tracking-widest mb-4 d-block">Personal Profile</label>

                                <div class="bg-white rounded-sm border p-3 mb-4 shadow-xs">
                                    <ul class="list-unstyled mb-0">
                                        <li class="d-flex justify-content-between mb-3 border-bottom pb-2">
                                            <span class="text-gray-600 small">Phone Number</span>
                                            <span class="font-weight-bold text-gray-900">{{ $selectedMember->phone_number }}</span>
                                        </li>
                                        <li class="d-flex justify-content-between mb-3 border-bottom pb-2">
                                            <span class="text-gray-600 small">Gender</span>
                                            <span class="font-weight-bold text-gray-900">{{ ucfirst($selectedMember->gender) }}</span>
                                        </li>
                                        <li class="d-flex justify-content-between">
                                            <span class="text-gray-600 small">Disability Status</span>
                                            <span class="font-weight-bold {{ $selectedMember->person_with_disability ? 'text-primary' : 'text-gray-900' }}">
                                                {{ $selectedMember->person_with_disability ? 'Registered PWD' : 'None' }}
                                            </span>
                                        </li>
                                    </ul>
                                </div>

                                <label class="text-gray-600 small font-weight-bold text-uppercase tracking-widest mb-3 d-block">Verified Documents</label>
                                <div class="d-flex flex-wrap gap-2">

                                    @if($selectedMember->photo_url)
                                        <a href="{{ asset($selectedMember->photo_url) }}"
                                        download="{{ $selectedMember->photo_original_filename ?? 'national_id' }}"
                                        class="btn btn-sm btn-white border shadow-sm text-gray-600 d-inline-flex align-items-center"
                                        title="Download National ID">
                                            <i class="fas fa-download"></i> National ID
                                        </a>
                                    @endif

                                    @if($selectedMember->pwd_certificate_url)
                                        <a href="{{ asset($selectedMember->pwd_certificate_url) }}"
                                        download="{{ $selectedMember->pwd_certificate_original_filename ?? 'pwd_certificate' }}"
                                        class="btn btn-sm btn-white border shadow-sm text-gray-600 d-inline-flex align-items-center"
                                        title="Download PWD Certificate">
                                            <i class="fas fa-download"></i> PWD Cert
                                        </a>
                                    @endif
                                </div>
                            </div>

                            <div class="col-md-7 p-4 bg-white">
                                <label class="text-gray-500 font-weight-bold text-uppercase tracking-widest mb-4 d-block">Credit Risk Assessment</label>

                                @include('partials.application.member-credit-risk', ['member' => $selectedMember])
                            </div>
                        </div>
                    </div>

                    {{-- 4. MODAL FOOTER --}}
                    <div class="modal-footer border-top bg-light px-4 py-3">
                        <button type="button" class="btn btn-refresh-premium px-4 font-weight-bold shadow-sm" data-dismiss="modal">Close Profile</button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

    <x-action-modal
        id="app-view-disburse-modal"
        title="Final Disbursement Approval"
        type="primary"
        icon="fas fa-money-check-alt"
        size="modal-lg"
        x-on:open-modal.window="if($event.detail.name === 'app-view-disburse-modal') $($el).modal('show')"
        x-on:close-modal.window="if($event.detail.name === 'app-view-disburse-modal') $($el).modal('hide')"
    >
        <div class="alert alert-default-info shadow-sm mb-4">
            <div class="d-flex align-items-center">
                <i class="fas fa-file-invoice-dollar fa-2x mr-3 text-info"></i>
                <div>
                    <strong class="text-bold">Final Action: Fund Disbursement</strong><br>
                    <span class="text-gray-600">
                        Record the amount issued and cheque reference. The application moves to <strong>Disbursed</strong> and the loans portal updates on sync.
                    </span>
                    <span class="d-block text-gray-600">
                        Amount Approved: <strong>KES {{ number_format($app_approved_amount ?? 0, 2)}}</strong>
                    </span>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="form-group col-md-6">
                <label class="text-uppercase font-bold text-gray-600 mb-2">Actual Amount Issued</label>
                <div class="input-group premium-input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-money-bill text-info"></i></span>
                    </div>
                    <input type="number" required wire:model.live="app_amount_issued" class="form-control" placeholder="0.00">
                </div>
                @error('app_amount_issued') <small class="d-block text-success font-bold">{{ $message }}</small> @enderror
            </div>
            <div class="form-group col-md-6">
                <label class="text-uppercase font-bold text-gray-600 mb-2">Cheque / Reference Number</label>
                <div class="input-group premium-input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-money-check text-info"></i></span>
                    </div>
                    <input type="text" required wire:model.live="app_cheque_number" class="form-control" placeholder="">
                </div>
                @error('app_cheque_number') <small class="d-block text-success font-weight-bold">{{ $message }}</small> @enderror
            </div>
            <div class="form-group col-md-6">
                <label class="text-uppercase font-bold text-gray-600 mb-2">Group Unique Identifier</label>
                <div class="input-group premium-input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-id-badge text-info"></i></span>
                    </div>
                    <input type="text" required {{ $hasExistingIdentifier ? 'disabled' : '' }} wire:model.live="app_group_identifier" class="form-control" placeholder="MYN001">
                </div>
                @error('app_group_identifier') <small class="d-block text-success font-weight-bold">{{ $message }}</small> @enderror
            </div>
            @if($app_approved_amount != $app_amount_issued)
                <div class="form-group col-md-6">
                    <label class="text-uppercase font-bold text-gray-600 mb-2">Justification for amount discrepancy</label>
                    <textarea wire:model.live="app_disbursement_comment" class="form-control" rows="2" placeholder="Reason for difference in amount issued"></textarea>
                    @error('app_disbursement_comment') <small class="d-block text-success font-weight-bold">{{ $message }}</small> @enderror
                </div>
            @endif
        </div>
        <x-slot name="footer">
            <div class="d-flex justify-content-center w-100">
                <x-pill-button label="Cancel" variant="outline" color="info" data-dismiss="modal" icon="fas fa-times" class="mx-1 shadow-sm btn-refresh-premium" />
                <x-pill-button label="Confirm & Disburse" target="processDisbursementFromView" color="primary" icon="fas fa-paper-plane" class="mx-1 shadow-sm btn-refresh-premium" />
            </div>
        </x-slot>
    </x-action-modal>

    <x-action-modal
        id="app-view-not-disbursed-modal"
        title="Record: Not Disbursed"
        type="warning"
        icon="fas fa-times-circle"
        size="modal-lg"
        x-on:open-modal.window="if($event.detail.name === 'app-view-not-disbursed-modal') $($el).modal('show')"
        x-on:close-modal.window="if($event.detail.name === 'app-view-not-disbursed-modal') $($el).modal('hide')"
    >
        <div class="alert alert-warning shadow-sm mb-4">
            <strong>Disbursement was not completed</strong><br>
            <span class="text-sm">The group trained but did not receive funds. Status syncs to the loans portal after processing.</span>
        </div>
        <div class="form-group">
            <label class="font-weight-bold text-gray-600">Reason</label>
            <select wire:model.live="disbursementFailureReason" class="form-control">
                <option value="">Select reason...</option>
                @foreach($this->disbursementFailureReasonOptions() as $reason)
                    <option value="{{ $reason['id'] }}">{{ $reason['name'] }}</option>
                @endforeach
            </select>
            @error('disbursementFailureReason') <small class="text-danger d-block">{{ $message }}</small> @enderror
        </div>
        <div class="form-group mb-0">
            <label class="font-weight-bold text-gray-600">Details (required)</label>
            <textarea wire:model.live="disbursementFailureNotes" class="form-control" rows="3" placeholder="Explain why disbursement did not happen (min 20 characters)"></textarea>
            @error('disbursementFailureNotes') <small class="text-danger d-block">{{ $message }}</small> @enderror
        </div>
        <x-slot name="footer">
            <div class="d-flex justify-content-center w-100">
                <x-pill-button label="Cancel" variant="outline" color="info" data-dismiss="modal" icon="fas fa-times" class="mx-1 shadow-sm btn-refresh-premium" />
                <x-pill-button label="Confirm Not Disbursed" target="processNotDisbursedFromView" color="warning" icon="fas fa-check" class="mx-1 shadow-sm btn-refresh-premium" />
            </div>
        </x-slot>
    </x-action-modal>

    @livewire('send-custom-sms')

@push('styles')

@endpush
