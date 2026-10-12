<div class="hq-panel-card p-3 p-md-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
        <div>
            <h5 class="font-weight-bold mb-1">Constituency allocation profile</h5>
            <p class="text-muted small mb-0">
                Read-only view for validation. Entries are maintained by Headquarters.
                If figures look wrong, ask HQ to send a correction prompt or update the profile.
            </p>
        </div>
        <span class="badge badge-info">No local editing</span>
    </div>

    <div class="row mb-4">
        <div class="col-md-3 mb-2">
            <div class="border rounded p-3 h-100">
                <div class="text-uppercase text-muted small">Total allocations</div>
                <div class="font-weight-bold">KES {{ number_format($profile->totalAllocations(), 2) }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="border rounded p-3 h-100">
                <div class="text-uppercase text-muted small">Total transfers</div>
                <div class="font-weight-bold">KES {{ number_format($profile->totalTransfers(), 2) }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="border rounded p-3 h-100">
                <div class="text-uppercase text-muted small">Phase issued</div>
                <div class="font-weight-bold">KES {{ number_format($profile->totalPhaseIssuedAmounts(), 2) }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="border rounded p-3 h-100">
                <div class="text-uppercase text-muted small">Balance (main account)</div>
                <div class="font-weight-bold">KES {{ number_format($profile->computedBalanceMainAccount(), 2) }}</div>
            </div>
        </div>
    </div>

    <h6 class="font-weight-bold">Allocation years</h6>
    <div class="table-responsive mb-4">
        <table class="table table-sm table-bordered">
            <thead class="thead-light">
                <tr>
                    <th>Fiscal year</th>
                    <th class="text-right">Amount (KES)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($allocationYears as $row)
                    <tr>
                        <td>{{ $row['year_start'] }}/{{ $row['year_end'] }}</td>
                        <td class="text-right">{{ number_format($row['amount'], 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="text-muted">No allocation years recorded.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <h6 class="font-weight-bold">Transfer levels</h6>
    <div class="table-responsive mb-4">
        <table class="table table-sm table-bordered">
            <thead class="thead-light">
                <tr>
                    <th>Level</th>
                    <th class="text-right">Amount (KES)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transferLevels as $row)
                    <tr>
                        <td>Level {{ $row['level'] }}</td>
                        <td class="text-right">{{ number_format($row['amount'], 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="text-muted">No transfer levels recorded.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <h6 class="font-weight-bold">Phase issued amounts</h6>
    <div class="table-responsive">
        <table class="table table-sm table-bordered">
            <thead class="thead-light">
                <tr>
                    <th>Phase</th>
                    <th class="text-right">Amount (KES)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($phaseIssuedAmounts as $row)
                    <tr>
                        <td>Phase {{ $row['phase'] }}</td>
                        <td class="text-right">{{ number_format($row['amount'], 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="text-muted">No phase issued amounts recorded.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
