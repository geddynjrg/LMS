<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Disbursement Report</h3>
                <div class="btn-group float-right m-2" role="group">
                    <button type="button" class="btn btn-primary mr-2" wire:click="export" wire:loading.attr="disabled">
                        <span wire:loading>Exporting...</span>
                        <span wire:loading.remove><i class="fas fa-file-excel"></i> Export Excel</span>
                    </button>
                </div>
            </div>

            <div class="card-body table-responsive p-0" style="height: 750px;">
                <table class="table table-sm table-head-fixed table-bordered table-valign-middle">
                    <thead>
                        <tr class="bg-gradient-cyan">
                            <th>S/N</th>
                            <th>Code</th>
                            <th>Group Name</th>
                            <th>Phase</th>
                            <th>Loan Type</th>
                            <th>Ward</th>
                            <th>Disbursement Date</th>
                            <th>Amount Issued (KShs)</th>
                            <th>Bank / Account</th>
                        </tr>
                    </thead>
                    <tbody class="text-bold">
                        @forelse ($disbursements as $index => $loan)
                            @php($group = $loan->group)
                            <tr>
                                <td class="bg-gray">{{ $index + 1 }}</td>
                                <td>{{ $group?->code ?? $loan->gfs_code ?? 'N/A' }}</td>
                                <td>{{ $group?->name ?? 'N/A' }}</td>
                                <td>{{ $loan->loanPhase?->phase ?? 'N/A' }}</td>
                                <td>{{ $loan->loan_type ?? 'N/A' }}</td>
                                <td>{{ $group?->ward?->ward_name ?? 'NOT ASSIGNED' }}</td>
                                <td>{{ $loan->disbursement_date ? $loan->disbursement_date->format('Y-m-d') : 'N/A' }}</td>
                                <td>{{ number_format($loan->amount_issued) }}</td>
                                <td>{{ trim(($group?->bank ?? 'N/A') . ($group?->account ? ' / ' . $group->account : '')) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">No financed loans found for this constituency.</td>
                            </tr>
                        @endforelse

                        @if($disbursements->isNotEmpty())
                            <tr class="bg-gray text-lg text-bold">
                                <td colspan="7" class="text-center">Totals</td>
                                <td>{{ number_format($totalAmountIssued) }}</td>
                                <td></td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
