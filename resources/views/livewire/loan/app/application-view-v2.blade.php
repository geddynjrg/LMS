<section class="content">
    <div class="container-fluid app-review-v2-shell py-2">
        {{-- Simplified header --}}
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-start flex-wrap">
                    <div class="mb-2">
                        <div class="d-flex align-items-center flex-wrap">
                            <h3 class="mb-0 mr-3 text-2xl font-weight-bold">{{ $this->application->application_id }}</h3>
                            @if(!is_null($this->application->phaseApplication))
                                <span class="{{ $this->getStatusClasses($this->application->phaseApplication->status) }} border d-inline-flex align-items-center font-medium px-2.5 py-1 rounded-lg text-xs">
                                    {{ $this->getStatusLabel($this->application->phaseApplication->status) }}
                                </span>
                            @else
                                <span class="{{ $this->getStatusClasses($this->application->status) }} border d-inline-flex align-items-center font-medium px-2.5 py-1 rounded-lg text-xs">
                                    {{ $this->getStatusLabel($this->application->status) }}
                                </span>
                            @endif
                        </div>
                        <div class="text-muted small mt-2">
                            <span class="mr-3"><i class="fas fa-layer-group mr-1"></i>
                                {{ is_null($this->application->phaseApplication) ? 'Not assigned' : $this->application->phaseApplication->reviewPhase->phase }}
                            </span>
                            <span class="mr-3"><i class="fas fa-users mr-1"></i> {{ $this->application->group_name ?? 'N/A' }}</span>
                            <span class="mr-3"><i class="fas fa-coins mr-1"></i> KES {{ number_format($this->application->loan_amount, 2) }}</span>
                            <span><i class="far fa-calendar-alt mr-1"></i> {{ $this->application->created_at->format('M d, Y') }}</span>
                        </div>
                    </div>
                    <div class="btn-group flex-wrap mb-2">
                        @if($this->application->phaseApplication && $this->canDownloadTrainingCertificate())
                            <a href="{{ route('training-certificate.download', $this->application->phaseApplication) }}"
                               class="btn btn-refresh-premium btn-outline-primary btn-sm"
                               title="Download training certificate PDF">
                                <i class="fas fa-file-download mr-1"></i> Certificate
                            </a>
                        @elseif($notice = $this->trainingCertificateNotice())
                            <span class="btn btn-light btn-sm text-muted" title="{{ $notice }}">
                                <i class="fas fa-certificate mr-1"></i> Certificate pending
                            </span>
                        @endif
                        @if($this->canShowDisbursementActions())
                            <button type="button" wire:click="openMarkDisbursedModal" class="btn btn-refresh-premium btn-primary btn-sm">
                                <i class="fas fa-coins mr-1"></i> Mark disbursed
                            </button>
                            <button type="button" wire:click="openNotDisbursedModal" class="btn btn-refresh-premium btn-outline-danger btn-sm">
                                <i class="fas fa-times-circle mr-1"></i> Not disbursed
                            </button>
                        @endif
                        <x-loading-button target="exportInstitutionalInfo" class="btn-refresh-premium btn-sm" icon="fas fa-file-excel" label="Export" />
                        <x-loading-button target="refreshData" class="btn-refresh-premium btn-sm" icon="fas fa-sync-alt" label="Refresh" />
                    </div>
                </div>
            </div>
        </div>

        {{-- Workflow stepper --}}
        <ul class="app-review-stepper mb-3">
            @foreach($this->workflowSteps as $step)
                <li class="step-item {{ $step['status'] }}">
                    <div class="font-weight-bold text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.04em;">{{ $step['label'] }}</div>
                    <div class="mt-1 small text-muted">
                        @if($step['status'] === 'done')
                            <i class="fas fa-check-circle text-success mr-1"></i> Complete
                        @elseif($step['status'] === 'active')
                            <i class="fas fa-arrow-circle-right text-primary mr-1"></i> Current step
                        @else
                            <i class="fas fa-lock text-muted mr-1"></i> Pending
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>

        {{-- Next action banner --}}
        @php $next = $this->nextAction; @endphp
        <div class="app-review-next-banner tone-{{ $next['tone'] }} mb-3 d-flex justify-content-between align-items-center flex-wrap">
            <div class="d-flex align-items-center">
                <i class="fas {{ $next['icon'] }} mr-2 fa-lg"></i>
                <span>{{ $next['message'] }}</span>
            </div>
            @if(!empty($next['primary']) && auth()->user()->can('review application'))
                <div class="mt-2 mt-md-0">
                    @if($next['primary'] === 'findMatches')
                        <x-modal-trigger
                            target="findMatches"
                            :label="$next['primaryLabel']"
                            icon="fas fa-fingerprint"
                            variant="primary btn-sm"
                        />
                    @elseif($next['primary'] === 'approve')
                        <x-loading-button
                            target="approve"
                            :label="$next['primaryLabel']"
                            icon="fas fa-check"
                            class="btn-refresh-premium btn-sm"
                            :disabled="$this->application->link_status === 'not_attempted'"
                        />
                    @endif
                </div>
            @endif
        </div>

        {{-- Tabs + content + action rail --}}
        <div x-data="{
                activeTab: @entangle('activeTab').live,
                prevIndex: 0,
                tabs: @js(collect($this->reviewTabOptions)->pluck('key')->values()->all()),
                setActive(tab) {
                    this.prevIndex = this.tabs.indexOf(this.activeTab);
                    this.activeTab = tab;
                },
                get direction() {
                    let newIndex = this.tabs.indexOf(this.activeTab);
                    return newIndex > this.prevIndex ? 'forward' : 'backward';
                }
            }" class="w-100">

            <div class="row mb-3">
                <div class="col-12">
                    <ul class="nav nav-pills app-review-tabs flex-wrap justify-content-center" role="tablist">
                        @foreach($this->reviewTabOptions as $tab)
                            <li class="nav-item mb-1">
                                <a class="nav-link d-flex align-items-center"
                                   :class="{ 'active': activeTab === @js($tab['key']) }"
                                   @click.prevent="setActive(@js($tab['key']))"
                                   href="#">
                                    <i class="fas {{ $tab['icon'] }} mr-1"></i>
                                    {{ $tab['label'] }}
                                    @if($tab['badge'] !== null)
                                        <span class="badge badge-{{ $tab['key'] === 'members-details' && $this->memberConflictCount > 0 ? 'danger' : 'secondary' }} ml-2">{{ $tab['badge'] }}</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <div class="row">
                <div class="{{ auth()->user()->can('review application') ? 'col-lg-9' : 'col-12' }}">
                    <div class="tab-content overflow-hidden">
                        <div x-show="activeTab === 'all-details'"
                             x-transition:enter="animate__animated animate__faster"
                             :class="direction === 'forward' ? 'animate__fadeInRight' : 'animate__fadeInLeft'"
                             class="card shadow-sm border-0">
                            <div class="card-body">@include('partials.application.application-data')</div>
                        </div>
                        <div x-show="activeTab === 'members-details'"
                             x-transition:enter="animate__animated animate__faster"
                             :class="direction === 'forward' ? 'animate__fadeInRight' : 'animate__fadeInLeft'"
                             style="display: none;"
                             class="card shadow-sm border-0">
                            <div class="card-body">@include('partials.application.member-details')</div>
                        </div>
                        <div x-show="activeTab === 'loan-history'"
                             x-transition:enter="animate__animated animate__faster"
                             :class="direction === 'forward' ? 'animate__fadeInRight' : 'animate__fadeInLeft'"
                             style="display: none;"
                             class="card shadow-sm border-0">
                            <div class="card-body">@include('partials.application.loan-history')</div>
                        </div>
                        <div x-show="activeTab === 'documents'"
                             x-transition:enter="animate__animated animate__faster"
                             :class="direction === 'forward' ? 'animate__fadeInRight' : 'animate__fadeInLeft'"
                             style="display: none;"
                             class="card shadow-sm border-0">
                            <div class="card-body">@include('partials.application.documents')</div>
                        </div>
                        <div x-show="activeTab === 'audit-trail'"
                             x-transition:enter="animate__animated animate__faster"
                             :class="direction === 'forward' ? 'animate__fadeInRight' : 'animate__fadeInLeft'"
                             style="display: none;"
                             class="card shadow-sm border-0">
                            <div class="card-body">@include('partials.application.audit-trail')</div>
                        </div>
                    </div>
                </div>

                @can('review application')
                    <div class="col-lg-3 d-none d-lg-block">
                        <div class="sticky-top" style="top: 50px; z-index: 1020;">
                            @include('livewire.loan.app.partials.application-review-action-rail')
                        </div>
                    </div>
                @endcan
            </div>
        </div>

        {{-- Mobile action rail (always visible on small screens) --}}
        @can('review application')
            <div class="app-review-mobile-actions d-lg-none">
                <div class="d-flex justify-content-between align-items-center">
                    @if(is_null($this->application->phaseApplication))
                        <x-loading-button
                            target="assignToPhase"
                            icon="fas fa-link"
                            class="btn-primary btn-sm flex-fill mr-2"
                            label="Assign phase"
                            :disabled="!$selectedPhaseId"
                        />
                    @elseif($this->application->phaseApplication->status === 'pending')
                        @if($this->application->link_status === 'not_attempted')
                            <x-modal-trigger
                                target="findMatches"
                                label="Verify"
                                icon="fas fa-fingerprint"
                                variant="primary btn-sm flex-fill mr-2"
                            />
                        @else
                            <x-loading-button
                                target="approve"
                                icon="fas fa-check"
                                class="btn-success btn-sm flex-fill mr-2"
                                label="Approve"
                            />
                        @endif
                        <x-loading-button
                            target="rejectApplication"
                            icon="fas fa-times"
                            class="btn-outline-danger btn-sm"
                            label="Reject"
                        />
                    @else
                        <x-table-action-btn
                            type="back"
                            inline=""
                            class="btn-block btn-sm"
                            title="Back to list"
                            route="const.app.app_dashboard"
                            :params="['county' => $county->slug, 'constituency' => $constituency->slug]"
                        > Back to list </x-table-action-btn>
                    @endif
                </div>
            </div>
        @endcan
    </div>

    @include('livewire.loan.app.partials.application-review-modals')

    @livewire('send-custom-sms')
</section>
