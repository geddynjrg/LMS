@if(auth()->user()->hasAnyRole(['constituency_officer', 'intern', 'super-admin']))
    <button type="button"
            wire:click="sendSmsToApplicationGroup"
            class="btn btn-info btn-sm shadow-sm {{ $class ?? '' }}"
            title="Send SMS to all members with phone numbers">
        <span wire:loading.remove wire:target="sendSmsToApplicationGroup">
            <i class="fas fa-sms mr-1"></i> Send Group SMS
        </span>
        <span wire:loading wire:target="sendSmsToApplicationGroup">
            <i class="fas fa-spinner fa-spin"></i>
        </span>
    </button>
@endif
