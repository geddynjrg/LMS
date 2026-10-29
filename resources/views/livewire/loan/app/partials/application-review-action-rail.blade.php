@can('review application')
<div class="app-review-action-rail">
    {{-- Phase assignment --}}
    <div class="rail-card" wire:ignore.self @refresh-app-data="$wire.$refresh()">
        <div class="rail-card-header">
            <i class="fas fa-layer-group mr-1"></i> Phase assignment
        </div>
        <div class="rail-card-body">
            @if(is_null($this->application->phaseApplication))
                <div class="form-group mb-3">
                    <label class="text-gray-600 mb-2 small font-weight-bold">Target review phase</label>
                    <x-dropdown-search
                        model="selectedPhaseId"
                        :options="$this->activePhases"
                        optionLabel="phase"
                        optionValue="id"
                        placeholder="Choose phase"
                    />
                </div>
                <x-loading-button
                    :title="!$selectedPhaseId ? 'Please select a phase first' : 'Click to assign'"
                    target="assignToPhase"
                    icon="fas fa-link mr-2"
                    class="btn-outline-primary btn-refresh-premium btn-block"
                    label="Assign now"
                    :disabled="!$selectedPhaseId"
                />
            @else
                <div class="p-3 border rounded bg-light mb-0">
                    <small class="text-gray-600 d-block text-uppercase font-weight-bold mb-1" style="font-size: 0.65rem;">Current phase</small>
                    <div class="d-flex align-items-center">
                        <i class="fas fa-check-circle text-success mr-2"></i>
                        <span class="text-gray-900 font-weight-bold">{{ $this->application->phaseApplication->reviewPhase->phase }}</span>
                    </div>
                    @if($this->application->phaseApplication->status === 'pending')
                        <div class="mt-3 pt-3 border-top text-center">
                            <x-loading-button
                                target="removeFromPhase"
                                icon="fas fa-unlink mr-2"
                                class="btn-outline-primary btn-refresh-premium btn-sm"
                                label="Remove assignment"
                            />
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>

    @if(!is_null($this->application->phaseApplication))
        {{-- System verification --}}
        <div class="rail-card" wire:ignore.self @refresh-app-data="$wire.$refresh()">
            <div class="rail-card-header">
                <i class="fas fa-fingerprint mr-1"></i> System verification
            </div>
            <div class="rail-card-body">
                @if($banner = $this->verificationBanner)
                    <div class="app-review-verify-banner tone-{{ $banner['tone'] }} mb-3">
                        <div class="font-weight-bold">
                            <i class="fas {{ $banner['icon'] }} mr-1"></i> {{ $banner['title'] }}
                        </div>
                        <div class="mt-1">{{ $banner['body'] }}</div>
                    </div>
                @endif

                <label class="text-gray-600 font-weight-bold text-uppercase small d-block mb-2">Identity status</label>
                <div class="p-3 border rounded {{ $this->application->phaseApplication->group_id ? 'bg-light' : 'bg-white border-primary' }} mb-3">
                    @if ($this->application->phaseApplication->group_id)
                        <div class="d-flex align-items-start">
                            <div class="mr-3">
                                <i class="fas fa-users-cog text-gray-900 fa-2x"></i>
                            </div>
                            <div class="flex-grow-1">
                                <small class="text-gray-900 d-block text-uppercase font-bold">Linked master record</small>
                                <p class="mb-0 text-gray-600">{{ $this->application->phaseApplication->linkedGroup->name }}</p>
                                <span class="text-gray-600 d-block text-xs">REG: {{ $this->application->phaseApplication->linkedGroup->reg_number }}</span>
                                <code class="text-primary">GROUP CODE: {{ $this->application->phaseApplication->linkedGroup->code }}</code>
                            </div>
                        </div>
                        @if($this->application->phaseApplication->status === 'pending')
                            <div class="mt-3 pt-2 border-top text-center">
                                <x-loading-button
                                    target="removeLink"
                                    icon="fas fa-unlink mr-1"
                                    class="btn-outline-primary rounded-lg btn-sm"
                                    label="Unlink wrongly matched record"
                                />
                            </div>
                        @endif
                    @elseif($this->application->link_status === 'attempted_no_match')
                        <div class="text-center py-2">
                            <i class="fas fa-plus-circle text-info fa-lg mb-2"></i>
                            <h6 class="font-weight-bold text-gray-900 mb-1">New group verified</h6>
                            <p class="text-xs text-gray-600 mb-2">
                                No master record match — registers as <strong>Wezesha</strong> on approval.
                            </p>
                            @if($this->application->phaseApplication->status === 'pending')
                                <x-modal-trigger
                                    target="findMatches"
                                    label="Retry search"
                                    icon="fas fa-sync-alt"
                                    variant="outline-primary shadow-sm btn-sm"
                                />
                            @endif
                        </div>
                    @else
                        <div class="text-center py-2">
                            <i class="fas fa-search-location text-primary mb-2"></i>
                            <p class="text-xs text-gray-900 mb-2">Manual verification required — review possible duplicate matches.</p>
                            <x-modal-trigger
                                target="findMatches"
                                label="Review matches"
                                icon="fas fa-fingerprint"
                                variant="primary"
                                size="sm"
                            />
                        </div>
                    @endif
                </div>

                <div class="decision-zone border-top pt-3">
                    @if($this->application->phaseApplication->status === 'pending')
                        <label class="text-gray-600 font-weight-bold text-uppercase small mb-3 d-block">Final decision</label>
                        @if($blocked = $this->approveBlockedReason)
                            <div class="alert alert-warning py-2 px-3 small mb-3">
                                <i class="fas fa-lock mr-1"></i> {{ $blocked }}
                            </div>
                        @endif
                        <div class="btn-group d-flex flex-row mb-2 text-center w-100">
                            <x-loading-button
                                target="approve"
                                title="{{ $this->application->link_status === 'not_attempted' ? 'Complete verification before approving' : 'Approve' }}"
                                class="btn-refresh-premium flex-fill"
                                icon="fas fa-check"
                                label="Approve"
                                :disabled="$this->application->link_status === 'not_attempted'"
                            />
                            <x-loading-button
                                target="rejectApplication"
                                class="btn-refresh-premium-danger flex-fill"
                                icon="fas fa-times"
                                label="Reject"
                            />
                        </div>
                        <x-loading-button
                            target="returnToGroup"
                            class="btn btn-outline-warning btn-sm btn-block mb-2"
                            icon="fas fa-undo"
                            label="Return to group"
                            title="Send back to the group as draft so they can upload documents or correct details"
                        />
                    @else
                        <div class="text-center p-3 border rounded {{ in_array($this->application->phaseApplication->status, ['rejected']) ? 'border-success' : 'border-primary' }}">
                            <i class="fas {{ in_array($this->application->phaseApplication->status, ['rejected']) ? 'fa-times-circle text-success' : 'fa-check-circle text-primary' }} fa-2x mb-2"></i>
                            <h3 class="text-sm font-bold mb-1">{{ strtoupper($this->application->phaseApplication->status) }}</h3>
                            <span class="text-gray-600 d-block text-xs">{{ $this->application->phaseApplication->updated_at->format('d M Y, H:i A') }}</span>
                            <div class="mt-3 text-left">
                                <table class="table table-sm table-borderless mb-0">
                                    <tr class="border-bottom">
                                        <td class="text-gray-600 pl-0">Vetted by:</td>
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
                            @if(in_array($this->application->phaseApplication->status, ['pending_submission_to_hq', 'rejected']) && $this->application->phaseApplication->reviewPhase->status === 'draft')
                                <x-loading-button
                                    target="reviewDecision"
                                    class="btn-refresh-premium btn-sm mt-3"
                                    icon="fas fa-undo"
                                    label="Review decision"
                                />
                            @endif
                        </div>
                    @endif
                </div>

                <div class="mt-3 d-flex flex-column align-items-center text-center border-top pt-3">
                    <x-table-action-btn
                        type="back"
                        inline=""
                        class="btn-block"
                        title="Back to list"
                        route="const.app.app_dashboard"
                        :params="[
                            'county' => $county->slug,
                            'constituency' => $constituency->slug
                        ]"
                    > Back to list </x-table-action-btn>
                </div>
            </div>
        </div>
    @endif
</div>
@endcan
