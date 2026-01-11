@props([
    'label' => 'Submit',
    'icon' => 'fas fa-check',
    'color' => 'success',
    'target' => null,
])

<button
    {{ $attributes->merge([
        'type' => 'submit',
        'class' => "btn btn-{$color} shadow"
    ]) }}
    wire:loading.attr="disabled"
    @if($target) wire:target="{{ $target }}" @endif
>
    <span wire:loading.remove @if($target) wire:target="{{ $target }}" @endif>
        <i class="{{ $icon }} mr-1"></i>
    </span>

    <span wire:loading @if($target) wire:target="{{ $target }}" @endif>
        <i class="fas fa-spinner fa-spin mr-1"></i>
    </span>

    <span wire:loading.remove @if($target) wire:target="{{ $target }}" @endif>
        {{ $label }}
    </span>

    <span wire:loading @if($target) wire:target="{{ $target }}" @endif>
        Processing...
    </span>
</button>
