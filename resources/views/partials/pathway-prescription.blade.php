{{-- Pathway / labour-export assistive recommendations for Core LMS --}}
@php $rx = $pathwayPrescription ?? null; @endphp
@if (!empty($rx))
<div class="card card-outline card-warning mb-3">
    <div class="card-header py-2">
        <h3 class="card-title mb-0"><i class="fas fa-lightbulb mr-1"></i> Pathway recommendations (assistive)</h3>
    </div>
    <div class="card-body py-3">
        <p class="small text-muted">{{ $rx['disclaimer'] ?? '' }}</p>
        @if (!empty($rx['destination_country']))
            <p class="mb-1"><strong>Destination:</strong> {{ $rx['destination_country'] }}
                @if (!empty($rx['optimal_destination_loan_kes']))
                    · heuristic KES {{ number_format($rx['optimal_destination_loan_kes']) }}
                @endif
            </p>
        @endif
        @if (!empty($rx['route_to_manual_compliance']))
            <span class="badge badge-danger mb-2">Suggest manual compliance queue</span>
        @endif
        <ul class="small mb-0 pl-3">
            @foreach (($rx['next_steps'] ?? []) as $step)
                <li>{{ $step }}</li>
            @endforeach
        </ul>
    </div>
</div>
@endif
