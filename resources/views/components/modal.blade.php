@props([
    'id',
    'title',
    'size' => '',
    'type' => 'primary',
    'submitAction' => null
])

@php
    $gradients = [
        'primary' => 'linear-gradient(135deg, #007bff 0%, #0056b3 100%)',
        'success' => 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
        'danger'  => 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)',
        'info'    => 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)',
        'dark'    => 'linear-gradient(135deg, #1e293b 0%, #0f172a 100%)',
    ];
    $headerBg = $gradients[$type] ?? $gradients['primary'];
@endphp

<div wire:ignore.self
     class="modal fade custom-resource-modal"
     id="{{ $id }}"
     tabindex="-1"
     role="dialog"
     aria-hidden="true"
     data-backdrop="static"
     x-data="{ isClickedOutside: false }"
     @click.self="isClickedOutside = true; setTimeout(() => isClickedOutside = false, 500)"
 >

    {{-- The 'shake-modal' class is added dynamically if Livewire validation fails --}}
    <div class="modal-dialog modal-dialog-centered {{ $size }} animate__animated"
         {{-- Bind the Animate.css shake class when Alpine state is true --}}
         :class="{ 'animate__shakeX': isClickedOutside, 'animate__headShake': {{ $errors->any() ? 'true' : 'false' }} }"
         role="document">

        <div class="modal-content border-0 shadow-lg modern-modal-content">

            {{-- Header with Premium Gradient --}}
            <div class="modal-header text-white pt-4 px-4 border-bottom-0 d-flex align-items-center"
                 style="background: {{ $headerBg }}; min-height: 80px;">
                <h5 class="modal-title font-weight-bold mb-0">
                    <i class="fas fa-circle-notch fa-spin mr-2 small opacity-50" wire:loading wire:target="{{ $submitAction }}"></i>
                    {{ $title }}
                </h5>
                <button type="button" class="close text-white opacity-10 ml-auto" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" style="font-size: 1.5rem;">&times;</span>
                </button>
            </div>

            {{-- Form Wrapper --}}
            <form @if($submitAction) wire:submit.prevent="{{ $submitAction }}" @endif>
                <div class="modal-body p-4">
                    {{ $slot }}
                </div>

                <div class="modal-footer bg-light border-0 px-4 py-3">
                    {{-- Default Footer Layout: Cancel on left, Action on right --}}
                    @if(isset($footer))
                        {{ $footer }}
                    @else
                        <x-pill-button label="Cancel" variant="outline" color="dark" data-dismiss="modal" />
                        @if($submitAction)
                            <x-pill-button type="submit" label="Save Changes" color="{{ $type }}" target="{{ $submitAction }}" icon="fas fa-check-circle" />
                        @endif
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* 1. Modal Container Styling */
    .modern-modal-content {
        border-radius: 20px !important;
        overflow: hidden;
        border: none !important;
    }

    /* 2. Custom Smooth Zoom Animation */
    .modal.custom-resource-modal.fade .modal-dialog {
        transition: none !important;
        transform: scale(0.8);
        opacity: 0;
    }

    .modal.custom-resource-modal.show .modal-dialog {
        animation: modalEntrance 0.35s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
    }

    @keyframes modalEntrance {
        from { opacity: 0; transform: scale(0.8) translateY(-20px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }

    /* 3. Validation Shake Animation */
    .shake-modal {
        animation: modalShake 0.4s cubic-bezier(.36,.07,.19,.97) both;
    }

    @keyframes modalShake {
        10%, 90% { transform: translate3d(-1px, 0, 0); }
        20%, 80% { transform: translate3d(2px, 0, 0); }
        30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
        40%, 60% { transform: translate3d(4px, 0, 0); }
    }

    /* 4. Backdrop Customization */
    .modal-backdrop.show {
        opacity: 0.5 !important;
        background-color: #0f172a; /* Slate-900 for a more modern overlay */
    }
    /* The attention-grabbing pulse */
    .modal-static-pulse {
        animation: modalPulse 0.4s ease-in-out;
    }

    @keyframes modalPulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.04); } /* Slight swell */
        100% { transform: scale(1); }
    }

    .shake-modal {
        animation: modalShake 0.4s cubic-bezier(.36,.07,.19,.97) both;
    }

</style>
