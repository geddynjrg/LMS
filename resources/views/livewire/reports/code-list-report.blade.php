<section class="content">
  <div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Loan Submission Form</h3>
            <div class="btn-group float-right m-2" role="group">
                <button type="button" class="btn btn-warning mr-2" wire:click="clearCache" wire:target="clearCache" wire:loading.attr="disabled">
                    <span wire:target="clearCache" wire:loading>Clearing...</span>
                    <span wire:target="clearCache" wire:loading.remove><i class="fas fa-sync"></i> Refresh</span>
                </button>
                <button type="button" class="btn btn-primary mr-2" wire:click="export" wire:target="export" wire:loading.attr="disabled">
                    <span wire:target="export" wire:loading>Exporting...</span>
                    <span wire:target="export" wire:loading.remove><i class="fas fa-file-excel"></i> Export Excel</span>
                </button>
            </div>
        </div>

        {{-- <div class="card-body table-responsive p-0">
            <table id="example1" class="table table-sm table-head-fixed table-bordered table-valign-middle">
            <thead>
                <tr class="bg-gradient-cyan">
                    <th class="bg-gradient-cyan">S/N</th>
                    <th class="bg-gradient-cyan">Date of Disbursement</th>
                    <th class="bg-gradient-cyan">Name of the group/institution</th>
                    <th class="bg-gradient-cyan">G.F.S.CODE</th>
                    <th class="bg-gradient-cyan">Sector</th>
                    <th class="bg-gradient-cyan">Category</th>
                    <th class="bg-gradient-cyan">Amount Approved (as per approved) codelist</th>
                    <th class="bg-gradient-cyan">Actual Amount Issued (KShs)</th>
                    <th class="bg-gradient-cyan text-wrap">Variance in Amounts (Approved and Issued)</th>
                    <th class="bg-gradient-cyan">Amount Due (KShs) ( consider time of loan disbursement and grace period)</th>
                    <th class="bg-gradient-cyan">Cummulative Amount repaid (KShs)</th>
                    <th class="bg-gradient-cyan">Amount cummulatively  in arrears (KShs)</th>
                    <th class="bg-gradient-cyan">Outstanding Loan Balance (KShs)</th>
                    <th class="bg-gradient-cyan">Date of Repayment completion</th>
                </tr>
            </thead>
            <thead>
                <tr class="bg-gradient-cyan">
                    <th class="bg-gradient-cyan"></th>
                    <th class="bg-gradient-cyan"></th>
                    <th class="bg-gradient-cyan"></th>
                    <th class="bg-gradient-cyan"></th>
                    <th class="bg-gradient-cyan"></th>
                    <th class="bg-gradient-cyan"></th>
                    <th class="bg-gradient-cyan"></th>
                    <th class="bg-gradient-cyan"></th>
                    <th class="bg-gradient-cyan text-wrap">a</th>
                    <th class="bg-gradient-cyan">b = (a/24 *No of months lapsed since expiry of grace period)</th>
                    <th class="bg-gradient-cyan">c</th>
                    <th class="bg-gradient-cyan">d = b-c</th>
                    <th class="bg-gradient-cyan">e = a-c</th>
                    <th class="bg-gradient-cyan"></th>
                </tr>
            </thead>
            <tbody class="text-bold">
                @php
                    $i = 1;
                    $levelOrder = ['New', 'Resubmission', 'Refinancing'];
                @endphp

                @foreach ($phases as $phase)
                    @foreach ($levelOrder as $level)
                        @php
                            $levelLoans = $phase->grouploans->where('level', $level);
                        @endphp

                        @if($levelLoans->isNotEmpty())
                            <tr>
                                <td class="bg-gray"></td>
                                <td colspan="9" class="text-center text-bold bg-gray">{{ $phase->phase }} - {{ $level }}</td>
                            </tr>

                            @foreach ($levelLoans as $grouploan)
                                <tr>
                                    <td class="bg-gray">{{ $i++ }}</td>
                                    <td>{{ $grouploan->disbursement_date }}</td>
                                    <td>{{ $grouploan->group->name ?? 'N/A' }}</td>
                                    <td>{{ $grouploan->group->gfs_code ?? 'N/A' }}</td>
                                    <td>{{ $grouploan->group->activity ?? 'N/A' }}</td>
                                    <td>{{ $grouploan->group->category ?? 'N/A' }}</td>
                                    <td>{{ number_format($grouploan->amount_approved) }}</td>
                                    <td>{{ number_format($grouploan->amount_issued) }}</td>
                                    <td>{{ number_format($grouploan->amount_repaid) }}</td>
                                </tr>
                            @endforeach

                            <tr class="bg-gray">
                                <td></td>
                                <td class="text-center" colspan="5">SubTotal {{ $phase->phase }} - {{ $level }}</td>
                                <td>{{ number_format($levelLoans->sum('amount_approved')) }}</td>
                                <td>{{ number_format($levelLoans->sum('amount_issued')) }}</td>
                                <td>{{ number_format($levelLoans->sum('amount_repaid')) }}</td>
                            </tr>
                        @endif
                    @endforeach
                @endforeach

                <tr class="bg-gray text-lg text-bold">
                    <td></td>
                    <td class="text-center" colspan="5">Totals</td>
                    <td>{{ number_format($totalAmountApproved) }}</td>
                    <td>{{ number_format($totalAmountIssued) }}</td>
                    <td>{{ number_format($totalAmountRepaid) }}</td>
                </tr>
            </tbody>
            </table>
        </div> --}}
        <div class="card-body table-responsive p-0" style="height: 750px;">
            <table id="example1" class="table table-sm table-head-fixed table-bordered table-valign-middle">
                <thead>
                    <tr class="bg-gradient-cyan">
                        <th class="bg-gradient-cyan">S/N</th>
                        <th class="bg-gradient-cyan">Date of Disbursement</th>
                        <th class="bg-gradient-cyan">Name of the group/institution</th>
                        <th class="bg-gradient-cyan">G.F.S.CODE</th>
                        <th class="bg-gradient-cyan">Sector</th>
                        <th class="bg-gradient-cyan">Category</th>
                        <th class="bg-gradient-cyan">Amount Approved (as per approved) codelist</th>
                        <th class="bg-gradient-cyan">Actual Amount Issued (KShs)</th>
                        <th class="col-2 bg-gradient-cyan text-wrap">Variance in Amounts (Approved and Issued)</th>
                        <th class="bg-gradient-cyan">Amount Due (KShs) ( consider time of loan disbursement and grace period)</th>
                        <th class="bg-gradient-cyan">Cummulative Amount repaid (KShs)</th>
                        <th class="bg-gradient-cyan">Amount cummulatively  in arrears (KShs)</th>
                        <th class="bg-gradient-cyan">Outstanding Loan Balance (KShs)</th>
                        <th class="bg-gradient-cyan">Date of Repayment completion</th>
                    </tr>
                </thead>
                <tbody class="text-bold">
                    <tr class="bg-gradient-cyan">
                        <td class="bg-gradient-cyan"></td>
                        <td class="bg-gradient-cyan"></td>
                        <td class="bg-gradient-cyan"></td>
                        <td class="bg-gradient-cyan"></td>
                        <td class="bg-gradient-cyan"></td>
                        <td class="bg-gradient-cyan"></td>
                        <td class="bg-gradient-cyan"></td>
                        <td class="bg-gradient-cyan"></td>
                        <td class="bg-gradient-cyan text-wrap">a</td>
                        <td class="bg-gradient-cyan">b = (a/24 *No of months lapsed since expiry of grace period)</td>
                        <td class="bg-gradient-cyan">c</td>
                        <td class="bg-gradient-cyan">d = b-c</td>
                        <td class="bg-gradient-cyan">e = a-c</td>
                        <td class="bg-gradient-cyan"></td>
                    </tr>
                    @php
                        $i = 1;
                        $levelOrder = ['New', 'Resubmission', 'Refinancing', 'Reallocation'];
                    @endphp

                    @foreach ($loanPhases as $phase)
                        @foreach ($levelOrder as $level)
                            @php
                                $levelLoans = $phase->grouploans->filter(fn ($grouploan) => ($grouploan->resolved_loan_type ?? $grouploan->loan_type ?? null) === $level);
                            @endphp

                            @if($levelLoans->isNotEmpty())
                                <tr>
                                    <td class="bg-gray"></td>
                                    <td colspan="14" class="text-center text-bold bg-gray">{{ $phase->phase }} - {{ $level }}</td>
                                </tr>

                                @foreach ($levelLoans as $grouploan)
                                    <tr>
                                        <td class="bg-gray">{{ $i++ }}</td>
                                        <td>{{ $grouploan->disbursement_date ?? 'N/A' }}</td>
                                        <td>{{ $grouploan->group->name ?? 'N/A' }}</td>
                                        <td>{{ $grouploan->gfs_code ?? 'N/A' }}</td>
                                        <td>{{ $grouploan->sector ?? 'N/A' }}</td>
                                        <td>{{ $grouploan->category ?? 'N/A' }}</td>
                                        <td>{{ number_format($grouploan->amount_approved) }}</td>
                                        <td>{{ number_format($grouploan->amount_issued) }}</td>
                                        <td>{{ number_format($grouploan->variance) }}</td>
                                        <td>{{ number_format($grouploan->amount_due) }}</td>
                                        <td>{{ number_format($grouploan->amount_repaid) }}</td>
                                        <td>{{ number_format($grouploan->arrears) }}</td>
                                        <td>{{ number_format($grouploan->outstanding_balance) }}</td>
                                        <td>{{ $grouploan->repayment_completion_date ?? 'N/A' }}</td>
                                    </tr>
                                @endforeach

                                <tr class="bg-gray">
                                    <td></td>
                                    <td class="text-center" colspan="5">SubTotal {{ $phase->phase }} - {{ $level }}</td>
                                    <td>{{ number_format($levelLoans->sum('amount_approved')) }}</td>
                                    <td>{{ number_format($levelLoans->sum('amount_issued')) }}</td>
                                    <td>{{ number_format($levelLoans->sum('variance')) }}</td>
                                    <td>{{ number_format($levelLoans->sum('amount_due')) }}</td>
                                    <td>{{ number_format($levelLoans->sum('amount_repaid')) }}</td>
                                    <td>{{ number_format($levelLoans->sum('arrears')) }}</td>
                                    <td>{{ number_format($levelLoans->sum('outstanding_balance')) }}</td>
                                    <td></td>
                                </tr>
                            @endif
                        @endforeach
                    @endforeach

                    <tr class="bg-gray text-lg text-bold">
                        <td></td>
                        <td class="text-center" colspan="5">Totals</td>
                        <td>{{ number_format($totalAmountApproved) }}</td>
                        <td>{{ number_format($totalAmountIssued) }}</td>
                        <td>{{ number_format($totalVariance) }}</td>
                        <td>{{ number_format($totalAmountDue) }}</td>
                        <td>{{ number_format($totalAmountRepaid) }}</td>
                        <td>{{ number_format($totalArrears) }}</td>
                        <td>{{ number_format($totalOutstandingBalance) }}</td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
  </div>
</section>
@push('scripts')

@endpush
