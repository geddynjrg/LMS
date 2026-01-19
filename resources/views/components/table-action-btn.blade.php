@props([
    'route',
    'params' => [],
    'type' => 'view',
    'title' => null
])

@php
    $config = [
        'view' => ['class' => 'btn-outline-info', 'icon' => 'fas fa-eye', 'title' => 'View Details'],
        'edit' => ['class' => 'btn-outline-primary', 'icon' => 'fas fa-edit', 'title' => 'Edit Record'],
        'delete' => ['class' => 'btn-outline-danger', 'icon' => 'fas fa-trash', 'title' => 'Delete Record'],
    ][$type] ?? ['class' => 'btn-outline-secondary', 'icon' => 'fas fa-question', 'title' => 'Action'];
@endphp

<a href="{{ route($route, $params) }}"
   wire:navigate
   {{ $attributes->merge([
       'class' => 'btn btn-sm ' . $config['class'] . ' d-inline-flex align-items-center',
       'title' => $title ?? $config['title']
   ]) }}
>
    <i class="{{ $config['icon'] }}" wire:loading.remove.delay.long wire:target="navigate"></i>

    <i class="fas fa-spinner fa-spin" wire:loading.delay.long wire:target="navigate" style="display: none;"></i>

    @if($slot->isNotEmpty())
        <span class="ml-1">{{ $slot }}</span>
    @endif
</a>
