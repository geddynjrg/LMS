<div>
    @if($showModal)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.55); z-index: 1060;" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable shadow-lg">
            <div class="modal-content border-0">
                <div class="modal-header text-white" style="background-color: #003366;">
                    <h5 class="modal-title">
                        <i class="fas fa-sms mr-2"></i>
                        Member confirmation codes (SMS not delivered)
                    </h5>
                    <button type="button" class="close text-white" wire:click="dismiss" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body bg-light">
                    <div class="alert alert-danger mb-3">
                        <strong>Consent required.</strong>
                        You must receive consent from the members of the group before typing any of these codes on
                        <strong>loans.uwezo.go.ke</strong> or <strong>loans-staging.uwezo.go.ke</strong>.
                        Do not enter a member&rsquo;s code unless that member is present and has agreed.
                    </div>

                    <p class="text-muted mb-3">
                        SMS did not go through for the members below. Some members use feature phones and cannot
                        install the Uwezo App on those handsets. Where a member likely has a smartphone, please
                        encourage them to download and install the Uwezo App so they can receive codes when SMS is down.
                    </p>

                    @foreach($items as $item)
                        @php
                            $encourage = !empty($item['encourage_app']);
                            $hasApp = ($item['device_profile'] ?? '') === 'smartphone_with_app';
                            $featurePhone = !empty($item['likely_feature_phone']);
                        @endphp
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start flex-wrap">
                                    <div>
                                        <h6 class="font-weight-bold mb-1">{{ $item['member_name'] ?? 'Member' }}</h6>
                                        <div class="small text-muted">
                                            {{ $item['group_name'] ?? 'Group' }}
                                            @if(!empty($item['constituency']))
                                                &middot; {{ $item['constituency'] }}
                                            @endif
                                            @if(!empty($item['portal_label']))
                                                &middot; {{ $item['portal_label'] }}
                                            @endif
                                        </div>
                                        <div class="small text-muted">
                                            Phone: {{ $item['phone'] ?? 'n/a' }}
                                            @if(!empty($item['application_id']))
                                                &middot; {{ $item['application_id'] }}
                                            @endif
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-weight-bold" style="font-size: 1.6rem; letter-spacing: 0.25em; font-family: monospace;">
                                            {{ $item['otp_code'] ?? '------' }}
                                        </div>
                                        @if(!empty($item['expires_at']))
                                            <div class="small text-muted">Valid until {{ $item['expires_at'] }}</div>
                                        @endif
                                    </div>
                                </div>

                                <div class="mt-3 p-2 rounded {{ $featurePhone ? 'bg-warning-light border' : 'bg-white border' }}">
                                    <div class="small mb-0">
                                        {{ $item['device_label'] ?? 'Device type could not be determined.' }}
                                    </div>
                                    @if($hasApp)
                                        <div class="small text-success mt-1">
                                            This member already uses the Uwezo Fund app. They can copy the code from Notifications.
                                        </div>
                                    @elseif($encourage)
                                        <div class="small mt-1">
                                            Please encourage this member to download and install the Uwezo App:
                                            <a href="{{ $appDownloadUrl }}" target="_blank" rel="noopener noreferrer">Download Uwezo App</a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="modal-footer bg-white">
                    <a class="btn btn-outline-secondary" href="{{ $appDownloadUrl }}" target="_blank" rel="noopener noreferrer">
                        Uwezo App download
                    </a>
                    <button type="button" class="btn btn-uwezo px-4" wire:click="dismiss">
                        I have member consent / I have noted these codes
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
