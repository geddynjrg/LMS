@props([
    'label',
    'value',
    'icon',
    'color' => 'primary',
    'text' => '',
    'amount' => null,
    'totalAmount' => 0,
    'url' => 'javascript:void(0)',
    'action' => null,
    // New props for automatic footer logic
    'type' => 'default', // 'total', 'progress', or 'default'
    'distribution' => null, // Expects ['youth' => X, 'women' => Y, 'pwd' => Z]
    'percentage' => null
])

<div {{ $attributes->merge(['class' => 'mb-3 h-100']) }}>
    <a @if($action) wire:click.prevent="{{ $action }}" @endif
       href="{{ $url }}"
       class="text-decoration-none">
        <div class="card card-hover shadow-sm border-0 h-100" style="border-radius: 12px; transition: transform 0.2s;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle mr-3 d-flex align-items-center justify-content-center bg-{{ $color }}-light"
                         style="width: 45px; height: 45px; min-width: 45px;">
                        <i class="fas {{ $icon }} text-{{ $color }} fa-lg"></i>
                    </div>

                    <div class="overflow-hidden flex-grow-1">
                        <h4 class="mb-0 font-weight-bold text-dark" style="font-size: 1.1rem; line-height: 1.2;">
                            {{ is_numeric($value) ? number_format($value) : $value }}
                        </h4>

                        <p class="font-medium text-gray-500 text-xs tracking-wider text-uppercase mb-0 text-wrap"
                           style="font-size: 0.65rem; letter-spacing: 0.5px; line-height: 1.1; font-weight: 700;">
                            {{ $label }}
                        </p>

                        @if($text)
                            <div class="text-muted opacity-75 mt-1" style="font-size: 0.7rem; font-style: italic; line-height: 1;">
                                {{ $text }}
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Automatic Footer Logic --}}
                @if($slot->isNotEmpty())
                    {{ $slot }}
                @elseif($type === 'total' && $distribution)
                    <div class="mt-2 pt-2 border-top">
                        <div class="d-flex align-items-center text-center">
                            @foreach(['youth' => 'success', 'women' => 'info', 'pwd' => 'danger'] as $key => $theme)
                                <div class="flex-fill {{ !$loop->last ? 'border-right' : '' }} px-1">
                                    <div class="text-{{ $theme }} font-weight-bold" style="font-size: 0.85rem; line-height: 1;">
                                        {{ format_smart_number($distribution[$key] ?? 0) }}
                                    </div>
                                    <div class="text-muted text-uppercase font-weight-bold" style="font-size: 0.55rem; letter-spacing: 0.2px;">
                                        {{ $key }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @elseif($type === 'progress' && isset($percentage))
                    <div class="mt-2 pt-2 border-top">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-xs text-muted">Share of Total</span>
                            <span class="text-xs font-bold text-{{ $color }}">{{ $percentage }}%</span>
                        </div>
                        <div class="progress rounded-pill" style="height: 4px;">
                            <div class="progress-bar bg-{{ $color }}" role="progressbar" style="width: {{ $percentage }}%"></div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </a>
</div>

@push('styles')
    <style>
        .bg-info-light { background: rgba(23, 162, 184, 0.15) !important; color: #17a2b8 !important; }
        .bg-warning-light { background: rgba(255, 193, 7, 0.15) !important; color: #856404 !important; }
        .bg-success-light { background: rgba(40, 167, 69, 0.15) !important; color: #28a745 !important; }
        .bg-primary-light { background: rgba(0, 123, 255, 0.15) !important; color: #007bff !important; }
        .bg-danger-light { background: rgba(220, 53, 69, 0.15) !important; color: #dc3545 !important; }
        /* Orange - using a deeper shade for the text color for accessibility */
        .bg-orange-light {
            background: rgba(253, 126, 20, 0.15) !important;
            color: #fd7e14 !important;
        }

        /* Pink - using a vibrant pink shade */
        .bg-pink-light {
            background: rgba(232, 62, 140, 0.15) !important;
            color: #e83e8c !important;
        }
        /* Purple - using a rich royal purple */
        .bg-purple-light {
            background: rgba(111, 66, 193, 0.15) !important;
            color: #6f42c1 !important;
        }
    </style>
@endpush
