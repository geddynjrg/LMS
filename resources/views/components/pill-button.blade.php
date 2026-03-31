@props([
    'label' => 'Submit',
    'icon' => 'fas fa-check',
    'color' => 'primary',
    'target' => null,
    'defaultLabel' => 'Processing...',
    'variant' => 'solid',
    'type' => 'button',
    'size' => '',
])

<button
    {{ $attributes->merge([
        'type' => $type,
        'wire:click' => $target,
        'wire:loading.attr' => 'disabled',
        'wire:target' => $target,
    ])->class([
        "btn pill-btn {$size}",
        "btn-{$color}" => $variant === 'solid',
        "btn-outline-{$color}" => $variant === 'outline',
    ]) }}
>
    <div class="d-flex align-items-center justify-content-center px-2">
        <span wire:loading.remove @if($target) wire:target="{{ $target }}" @endif>
            @if($icon)<i class="{{ $icon }} mr-2"></i>@endif {{ $label }}
        </span>

        <span wire:loading @if($target) wire:target="{{ $target }}" @endif style="display: none;">
            <i class="fas fa-spinner fa-spin mr-2"></i> {{ $defaultLabel }}
        </span>
    </div>
</button>

<style>
    .pill-btn {
        border-radius: 50px !important;
        border-width: 1px !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        white-space: nowrap;
    }

    .pill-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1) !important;
    }

    .pill-btn:active {
        transform: translateY(0);
    }
</style>
