
@php
    $memberMl = $memberMlScore ?? null;
    $memberMlIntake = is_array($memberMl) ? ($memberMl['intake'] ?? null) : null;
@endphp
@if($memberMlIntake)
    <div class="border rounded-sm p-3 mb-4 bg-white">
        <span class="font-weight-bold small text-uppercase tracking-widest text-muted d-block mb-2 text-center">ML repayment risk</span>
        @include('partials.application.ml-tu-gauge', [
            'mlIntake' => $memberMlIntake,
            'compact' => true,
            'showEarlywarn' => false,
            'showEarlywarnLine' => false,
        ])
    </div>
@endif

@if($member->is_defaulter)
    <div class="border overflow-hidden rounded-sm shadow-sm mb-4">
        <div class="bg-primary px-3 py-2 text-white small font-weight-bold text-uppercase tracking-wider d-flex justify-content-between align-items-center">
            <span><i class="fas fa-exclamation-triangle mr-2"></i> Critical: Active Default Records</span>
            <span class="badge badge-light px-2 py-1 text-gray-600">Exposure Detected</span>
        </div>

        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="bg-light">
                    <tr class="text-gray-500" style="font-size: 0.6rem; text-transform: uppercase; letter-spacing: 1px;">
                        <th class="pl-3 py-2 border-0">Loan Entity & Location</th>
                        <th class="py-2 border-0 text-center">Role / Phase</th>
                        <th class="py-2 border-0 text-center">Disbursement</th>
                        <th class="py-2 border-0 text-right">Principal</th>
                        <th class="pr-3 py-2 border-0 text-right">Arrears</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($member->default_history as $history)
                        @php
                            $group = $history->groupLoan->group;
                            $loan = $history->groupLoan;
                        @endphp
                        <tr class="border-bottom">
                            <td class="pl-3 py-3">
                                <div class="font-weight-bold text-dark small">{{ $group->name ?? 'Unknown Group' }}</div>
                                <div class="text-muted" style="font-size: 0.65rem;">
                                    {{ $group->ward->constituency->constituency_name ?? 'N/A' }}
                                    @if($group->ward->ward_name ?? false)
                                        &middot; {{ $group->ward->ward_name }}
                                    @endif
                                </div>
                            </td>
                            <td class="py-3 text-center">
                                <span class="badge badge-light border text-dark">{{ ucfirst($history->role ?? 'member') }}</span>
                                <div class="text-muted mt-1" style="font-size: 0.65rem;">{{ $loan->loanPhase->phase ?? 'N/A' }}</div>
                            </td>
                            <td class="py-3 text-center small text-muted">
                                {{ $loan->disbursement_date ? \Carbon\Carbon::parse($loan->disbursement_date)->format('d M Y') : 'N/A' }}
                            </td>
                            <td class="py-3 text-right small font-weight-bold">
                                {{ number_format($loan->amount_issued ?? 0, 2) }}
                            </td>
                            <td class="pr-3 py-3 text-right small text-danger font-weight-bold">
                                {{ number_format($loan->total_arrears ?? 0, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

@if($member->has_other_group_loan ?? false)
    <div class="border overflow-hidden rounded-sm shadow-sm mb-4">
        <div class="bg-warning px-3 py-2 text-dark small font-weight-bold text-uppercase tracking-wider d-flex justify-content-between align-items-center">
            <span><i class="fas fa-users mr-2"></i> Other Group Loan Exposure (All Constituencies)</span>
            <span class="badge badge-dark px-2 py-1">Flagged</span>
        </div>

        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="bg-light">
                    <tr class="text-gray-500" style="font-size: 0.6rem; text-transform: uppercase; letter-spacing: 1px;">
                        <th class="pl-3 py-2 border-0">Group & Constituency</th>
                        <th class="py-2 border-0 text-center">Role</th>
                        <th class="py-2 border-0 text-center">Repayment</th>
                        <th class="py-2 border-0 text-right">Outstanding</th>
                        <th class="pr-3 py-2 border-0 text-right">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($member->other_group_loans as $exposure)
                        <tr class="border-bottom">
                            <td class="pl-3 py-3">
                                <div class="font-weight-bold text-dark small">{{ $exposure['group_name'] ?? 'Unknown Group' }}</div>
                                <div class="text-muted" style="font-size: 0.65rem;">
                                    {{ $exposure['constituency_name'] ?? 'N/A' }}
                                    @if($exposure['ward_name'] ?? false)
                                        &middot; {{ $exposure['ward_name'] }}
                                    @endif
                                </div>
                            </td>
                            <td class="py-3 text-center">
                                <span class="badge badge-light border text-dark">{{ ucfirst($exposure['role'] ?? 'member') }}</span>
                            </td>
                            <td class="py-3 text-center small">
                                {{ number_format($exposure['repayment_percentage'] ?? 0, 1) }}%
                            </td>
                            <td class="py-3 text-right small font-weight-bold">
                                KES {{ number_format($exposure['outstanding_balance'] ?? 0, 2) }}
                            </td>
                            <td class="pr-3 py-3 text-right">
                                @if($exposure['is_performing'] ?? false)
                                    <span class="badge badge-success">Performing</span>
                                @else
                                    <span class="badge badge-danger">Non-performing</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

@if(!($member->is_defaulter ?? false) && !($member->has_other_group_loan ?? false))
    <div class="text-center py-5 border rounded-lg bg-light/20 mb-4" style="border-style: dashed !important;">
        <div class="d-inline-flex align-items-center justify-content-center bg-white rounded-circle shadow-sm border mb-3" style="width: 60px; height: 60px;">
            <i class="fas fa-shield-alt text-primary fa-lg"></i>
        </div>
        <h6 class="font-weight-bold text-dark">Clear Credit History</h6>
        <p class="text-gray-500 small px-5 mb-0">No defaults or other-group financed loans were detected for this member across all constituencies.</p>
    </div>
@endif

<div class="border rounded-sm bg-light px-3 py-2">
    <div class="d-flex align-items-center justify-content-between">
        <span class="text-xs font-weight-bold text-uppercase text-muted">External fund checks</span>
        <span class="badge badge-secondary">Coming soon</span>
    </div>
    <p class="text-xs text-muted mb-0 mt-1">Hustler Fund, Women Enterprise Fund, and Youth Fund debt screening will be integrated here.</p>
</div>
