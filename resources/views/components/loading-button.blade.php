@props([
    'target' => '',      // The Livewire method name
    'type' => 'button',  // submit or button
    'icon' => '',        // font-awesome icon class
    'label' => '',       // button text
    'loadingLabel' => '',// text to show while loading
    'size' => 'sm',      // sm, lg, or empty
    'block' => false,    // whether it should be btn-block
    'disabled' => false  // additional disabled logic
])

<button
    type="{{ $type }}"
    wire:click="{{ $target }}"
    wire:loading.attr="disabled"
    wire:target="{{ $target }}"
    {{ $disabled ? 'disabled' : '' }}
    {{ $attributes->merge(['class' => "btn " . ($size ? "btn-$size" : "") . ($block ? " btn-block" : "")]) }}
>
    <span wire:loading.remove wire:target="{{ $target }}">
        @if($icon) <i class="{{ $icon }} {{ $label ? 'mr-1' : '' }}"></i> @endif
        {{ $label }}
    </span>

    <span wire:loading wire:target="{{ $target }}">
        <i class="fas fa-sync-alt fa-spin {{ ($label || $loadingLabel) ? 'mr-1' : '' }}"></i>
        {{ $loadingLabel ?: $label }}
    </span>
</button>
