<section class="content">
    <div class="container-fluid">
        <h5 class="mt-4 mb-2">Group loan phases </h5>
        <div class="card card-default">
            <div class="card-header">
                <div class="btn-group float-right" role="group">
                    @can('create new phase')
                        <button type="button" class="btn btn-sm btn-primary mr-2" data-toggle="modal" data-target="#modal-add-phase">
                        <span><i class="fas fa-plus"></i> Add New Phase</span>
                    </button>
                    @endcan

                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive p-0" >
                    <table class="table table-head-fixed table-bordered table-sm">
                        <thead>
                        <tr class="text-center">
                            <th>#</th>
                            <th>Phase</th>
                            <th class="text-wrap">Total Approved Applications</th>
                            <th class="text-wrap">Total Amount Issued</th>
                            <th class="text-wrap">Total Amount Repaid</th>
                            <th>Balance</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                            @php
                                $i = 1;
                            @endphp
                            @foreach ($loanPhases as $loanPhase)
                                <tr>
                                    <td>{{$i++}}.</td>
                                    <td >{{$loanPhase->phase}}</td>
                                    <td class="col-2 text-right">{{$loanPhase->grouploans_count}}</td>
                                    <td class="text-right">{{number_format($loanPhase->grouploans->sum('amount_issued'))}}</td>
                                    <td class="text-right">{{number_format($loanPhase->grouploans->sum('amount_repaid'))}}</td>
                                    <td class="text-right">{{number_format($loanPhase->grouploans->sum('amount_issued') - $loanPhase->grouploans->sum('amount_repaid'))}}</td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-primary mr-2">
                                            <span><i class="fas fa-eye"></i> View</span>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="text-bold text-right">
                                <td colspan="2" class="text-center">Totals</td></td>
                                <td>{{number_format($totalApplications)}}</td>
                                <td>{{number_format($totalAmountIssued)}}</td>
                                <td>{{number_format($totalAmountRepaid)}}</td>
                                <td>{{number_format($totalAmountIssued - $totalAmountRepaid)}}</td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

        <!--add phase Modal -->
        <div class="modal fade" id="modal-add-phase">
            <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                <h4 class="modal-title">Add New Phase</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="addPhase">
                        <div class="row">
                            <div class="form-group col-sm-12">
                                <label for="phase">Loan Phase <code>*</code></label>
                                <input required type="text" wire:model.defer="phase" required class="form-control form-control-border border-width-2  text-cyan text-capitalize" id="phase" placeholder="Phase 1">
                                @error('phase')
                                    <span class="text-sm text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="btn-group float-right m-2" role="group">
                            <button type="submit" class="btn btn-primary mr-2" wire:loading.attr="disabled">
                                <span wire:loading wire:target="addPhase">Saving...</span>
                                <span wire:loading.remove wire:target="addPhase"><i class="fas fa-save"></i> Add </span>
                            </button>
                        </div>
                    </form>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>

                </div>
            </div>
            <!-- /.modal-content -->
            </div>
            <!-- /.modal-dialog -->
        </div>
        <!-- /.defaulter members modal -->
</section>
@push('styles')
    <link rel="stylesheet" href="{{asset('plugins/toastr/toastr.min.css')}}">
@endpush

@push('scripts')
    <script src="{{asset('plugins/toastr/toastr.min.js')}}"></script>
    <script>
        document.addEventListener('addphase', function (event) {
            $("#modal-add-phase").addClass('fade').modal('hide');
            toastr.success(event.detail.message);

        });
    </script>
@endpush
