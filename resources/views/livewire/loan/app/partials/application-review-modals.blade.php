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
                @php $recommendation = $this->loanRecommendation; @endphp
                <div class="mb-4 p-3 rounded border {{ $recommendation['should_decline'] ? 'border-danger bg-light' : 'border-success bg-white' }}">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <div class="small font-weight-bold text-uppercase text-muted">System Recommended Amount</div>
                            <div class="h4 font-weight-bold mb-0 {{ $recommendation['should_decline'] ? 'text-danger' : 'text-success' }}">
                                @if($recommendation['should_decline'])
                                    Decline
                                @else
                                    KES {{ number_format($recommendation['recommended_amount'], 0) }}
                                @endif
                            </div>
                        </div>
                        <span class="badge {{ $recommendation['should_decline'] ? 'badge-danger' : 'badge-success' }}">
                            {{ $recommendation['loan_type'] }}
                        </span>
                    </div>
                    <div class="small text-muted mb-2">
                        <div>Monthly profit: <strong>KES {{ number_format($recommendation['monthly_profit'], 0) }}</strong></div>
                        @if($recommendation['table_banking_monthly'] > 0)
                            <div>Table banking (monthly): <strong>KES {{ number_format($recommendation['table_banking_monthly'], 0) }}</strong></div>
                        @endif
                        <div>Base amount used: <strong>KES {{ number_format($recommendation['base_amount'], 0) }}</strong> over <strong>{{ $recommendation['months'] }}</strong> months</div>
                        <div>Formula: <strong>{{ $recommendation['formula'] }}</strong></div>
                    </div>
                    <p class="small mb-0 {{ $recommendation['should_decline'] ? 'text-danger' : 'text-dark' }}">
                        {{ $recommendation['explanation'] }}
                    </p>
                </div>

                @if($summary = $this->wardAllocationSummary)
                    <div class="mb-4 p-3 rounded border bg-light">
                        <div class="small font-weight-bold text-uppercase text-muted mb-2">Ward allocation balance (this phase)</div>
                        <div class="small text-muted mb-2">
                            Approved so far: <strong>KES {{ number_format($summary['phase_total_approved'], 0) }}</strong>
                            @if($summary['target_per_ward'])
                                · Equal-share target per ward: <strong>KES {{ number_format($summary['target_per_ward'], 0) }}</strong>
                            @endif
                        </div>
                        @foreach($summary['wards'] as $wardRow)
                            <div class="d-flex justify-content-between small py-1 border-bottom">
                                <span>{{ $wardRow['ward_name'] }} ({{ $wardRow['application_count'] }})</span>
                                <span>KES {{ number_format($wardRow['approved_total'], 0) }} · {{ $wardRow['share_percent'] }}%</span>
                            </div>
                        @endforeach
                        @foreach($summary['imbalance_notes'] as $note)
                            <div class="small text-warning mt-2 mb-0"><i class="fas fa-balance-scale mr-1"></i>{{ $note }}</div>
                        @endforeach
                    </div>
                @endif

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
            @if($this->eligibility['can_borrow'] && ! $this->loanRecommendation['should_decline'])
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
            @elseif($this->loanRecommendation['should_decline'])
                <div class="alert alert-danger w-100 mb-0 d-flex justify-content-between align-items-center">
                    <span class="font-weight-bold">
                        <i class="fas fa-times-circle mr-2"></i> System recommends declining this application.
                    </span>
                    <button type="button" class="btn btn-refresh-premium shadow-sm px-4" data-dismiss="modal">Close</button>
                </div>
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
