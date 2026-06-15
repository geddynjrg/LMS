<span class="d-flex align-items-center justify-content-between w-100">
    <span>{{ $label }}</span> {{-- We pass the label here now --}}
    <span class="ml-2">
        @if ($sortField !== $field)
            <i class="fas fa-sort text-muted" style="opacity: 0.3; font-size: 0.8rem;"></i>
        @elseif ($sortDirection === 'asc')
            <i class="fas fa-sort-up text-dark font-weight-bold" style="font-size: 1rem;"></i>
        @else
            <i class="fas fa-sort-down text-dark font-weight-bold" style="font-size: 1rem;"></i>
        @endif
    </span>
</span>
