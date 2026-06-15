@props([
    'field' => null,
    'sortField' => null,
    'sortDirection' => null,
    'label' => null,
    'sortable' => true, // Default to true
])

@php
    $isActive = $sortable && $field && $sortField === $field;
@endphp

<th
    @if($sortable && $field) wire:click="sortBy('{{ $field }}')" @endif
    {{ $attributes->merge([
        'class' => 'font-medium px-4 py-3 text-gray-500 text-left text-uppercase text-xs tracking-wider ' .
                   ($sortable ? 'table-header-hover cursor-pointer' : '')
    ]) }}
>
    <div class="d-flex align-items-center">
        <span>{{ $label ?? $slot }}</span>

        @if($sortable && $field)
            <span class="ml-2" style="width: 14px; display: inline-block;">
                {{-- 1. Loading Spinner (Shows ONLY when this specific field is being sorted) --}}
                <div wire:loading wire:target="sortBy('{{ $field }}')">
                    <i class="fas fa-spinner fa-spin text-muted" style="font-size: 0.65rem;"></i>
                </div>

                {{-- 2. Sort Icons (Hidden when loading) --}}
                <div wire:loading.remove wire:target="sortBy('{{ $field }}')">
                    @if($isActive)
                        <i class="fas fa-chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-primary"></i>
                    @else
                        <i class="fas fa-chevron-up sort-icon"></i>
                    @endif
                </div>
            </span>
        @endif
    </div>
</th>
