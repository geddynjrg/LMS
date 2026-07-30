<div>
    @if($isOpen)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);" wire:click.self="close">
        <div class="modal-dialog modal-lg modal-dialog-scrollable shadow-sm">
            <div class="modal-content border-0">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title font-weight-bold">
                        <i class="fas fa-sms mr-2"></i>Send Custom SMS
                    </h5>
                    <button type="button" class="close text-white" wire:click="close" aria-label="Close">
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-light border mb-3">
                        <strong>Recipient:</strong>
                        @if($recipientType === 'member')
                            <span class="text-capitalize">{{ $memberName ?? 'Member' }}</span>
                            @if($memberPhone)
                                <br><small class="text-muted"><i class="fas fa-phone mr-1"></i>{{ $memberPhone }}</small>
                            @endif
                        @else
                            Group: <strong>{{ $groupName ?? 'N/A' }}</strong>
                            <br><small class="text-muted">Message will be sent to all members with contact numbers.</small>
                        @endif
                    </div>

                    <div class="form-group">
                        <label for="custom-sms-message" class="font-weight-bold">Message</label>
                        <textarea
                            id="custom-sms-message"
                            class="form-control @error('message') is-invalid @enderror"
                            rows="5"
                            wire:model="message"
                            placeholder="Type your message here..."
                            maxlength="480"
                            {{ $sending ? 'disabled' : '' }}
                        ></textarea>
                        @error('message')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                        <small class="text-muted">Max 480 characters. "Uwezo Fund:" prefix added automatically if omitted.</small>
                    </div>

                    @if($statusMessage)
                        <div class="alert alert-{{ $statusType }} mb-0">
                            {{ $statusMessage }}
                        </div>
                    @endif
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" wire:click="close" {{ $sending ? 'disabled' : '' }}>
                        Close
                    </button>
                    <button type="button" class="btn btn-info" wire:click="send" wire:loading.attr="disabled" {{ $sending ? 'disabled' : '' }}>
                        <span wire:loading.remove wire:target="send">
                            <i class="fas fa-paper-plane mr-1"></i> Send SMS
                        </span>
                        <span wire:loading wire:target="send">
                            <i class="fas fa-spinner fa-spin mr-1"></i> Sending...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
