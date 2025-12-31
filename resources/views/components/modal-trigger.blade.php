@props([
    'target',
    'modalId',
    'variant' => 'primary',
    'size' => 'sm',
    'icon' => 'fas fa-link',
    'label' => 'Click Me',
    'block' => false
])

<button
    wire:click="{{ $target }}"
    wire:loading.attr="disabled"
    {{ $attributes->merge(['class' => "btn btn-{$variant} btn-{$size} " . ($block ? 'btn-block' : '')]) }}
>
    {{-- Normal State --}}
    <span wire:loading.remove wire:target="{{ $target }}">
        @if($icon) <i class="{{ $icon }} mr-1"></i> @endif
        {{ $label }}
    </span>

    {{-- Loading State --}}
    <span wire:loading wire:target="{{ $target }}">
        <i class="fas fa-spinner fa-spin mr-1"></i> Processing...
    </span>
</button>
