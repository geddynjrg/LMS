<div>
    <div class="mb-3 d-flex flex-wrap gap-2">
        <button type="button" wire:click="$set('filter', 'pending')"
                class="btn btn-sm {{ $filter === 'pending' ? 'btn-primary' : 'btn-outline-primary' }}">
            Pending review
        </button>
        <button type="button" wire:click="$set('filter', 'approved')"
                class="btn btn-sm {{ $filter === 'approved' ? 'btn-success' : 'btn-outline-success' }}">
            Cleared for HQ management
        </button>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th>Phase</th>
                        <th>Constituency</th>
                        <th>Submitted</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($phases as $phase)
                        <tr>
                            <td>{{ $phase->phase }}<br><small class="text-muted">{{ $phase->phase_ref }}</small></td>
                            <td>{{ $phase->constituency?->constituency_name ?? $phase->constituency_code }}</td>
                            <td>{{ $phase->submitted_to_hq_at?->format('d M Y') ?? '—' }}</td>
                            <td>
                                <span class="badge badge-light border">
                                    {{ \App\Support\AccountantReviewStatus::labels()[$phase->accountant_review_status] ?? 'Awaiting review' }}
                                </span>
                            </td>
                            <td class="text-right">
                                <a href="{{ route('accountant.phases.review', $phase) }}" class="btn btn-sm btn-outline-primary">
                                    Review
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No phases awaiting accounts review.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($phases->hasPages())
            <div class="card-footer">{{ $phases->links() }}</div>
        @endif
    </div>
</div>
