<section class="content">
    <div class="container-fluid">
        <h5 class="mt-4 mb-2">Applied Loans Summary </h5>
        <div class="card">
            <div class="card-header p-2">
                <ul class="nav nav-pills">
                  <li class="nav-item"><a class="nav-link active" href="#pending" data-toggle="tab">Pending <span class="badge badge-primary text-truncate"> {{ $pending->count() }}</span></a></li>
                  <li class="nav-item"><a class="nav-link" href="#approved" data-toggle="tab">Approved <span class="badge badge-primary text-truncate"> {{ $approved->count() }}</span></a></li>
                  <li class="nav-item"><a class="nav-link" href="#deferred" data-toggle="tab">Deferred <span class="badge badge-primary text-truncate"> {{ $deferred->count() }}</span></a></li>
                  <li class="nav-item"><a class="nav-link" href="#cancelled" data-toggle="tab">Cancelled Cheques <span class="badge badge-danger text-truncate"> {{ $cancelled->count() }}</span></a></li>
                </ul>
            </div>
            <!-- /.card-header -->

            <div class="card-body">
                <!-- tab content -->
                <div class="tab-content">
                    <!-- tab-pane -->
                    <div class="active tab-pane" id="pending">
                        <table id="tpending" class="table table-bordered table-striped">
                            <thead class="bg-gradient-cyan">
                                 <tr>
                                    <th>#</th>
                                    <th>Application Date</th>
                                    <th>Group</th>
                                    <th>Ward</th>
                                    <th>Phase</th>
                                    <th>Loan Level</th>
                                    <th>Loan Status</th>
                                    <th>Amount Applied</th>
                                    <th>View</th>
                                 </tr>
                            </thead>
                            <tbody>
                                @php
                                    $i = 1;
                                @endphp
                                @foreach ($pending as $grouploan)
                                <tr>
                                    <td>{{ $i++ }}</td>
                                    <td class="col-1">{{$grouploan->application_date}} </td>
                                    <td class="col-2 text-capitalize">{{ strtolower($grouploan->group->name) }}</td>
                                    <td class="text-capitalize">{{ strtolower($grouploan->group->ward->ward_name) }}</td>
                                    <td>{{ $grouploan->loanPhase->phase }}</td>
                                    <td>{{ $grouploan->level }}</td>
                                    <td>{{ $grouploan->loan_status }}</td>
                                    <td class="col-1 text-right">{{ number_format($grouploan->amount_applied) }}</td>
                                    <td>
                                        @can('update loan application')
                                            <a href="{{route('const.loans.applicant', [
                                        'county' => $county->slug,
                                        'constituency' => $constituency->slug,
                                        'group'=>$grouploan->group->slug],
                                        )}}" class="text-bold bg-fuchsia p-2 rounded-lg" href="#">View</a>
                                        @endcan
                                        
                                    </td>

                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th class="text-center" colspan="7">Total</th>
                                    <th class="text-right">{{number_format($this->pending->sum('amount_applied'))}}</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- tab-pane -->
                    <div class="tab-pane" id="approved">
                        <table id="tapproved" class="table table-bordered table-striped">
                            <thead class="bg-gradient-cyan">
                                 <tr>
                                    <th>#</th>
                                    <th>Application Date</th>
                                    <th>Group</th>
                                    <th>Ward</th>
                                    <th>Phase</th>
                                    <th>Loan Level</th>
                                    <th>Loan Status</th>
                                    <th>Amount Applied</th>
                                    <th>Amount Approved</th>
                                    <th>View</th>
                                 </tr>
                            </thead>
                            <tbody>
                                @php
                                    $i = 1;
                                @endphp
                                @foreach ($approved as $grouploan)
                                <tr>
                                    <td>{{ $i++ }}</td>
                                    <td class="col-1">{{$grouploan->application_date}} </td>
                                    <td class="col-2 text-capitalize">{{ strtolower($grouploan->group->name) }}</td>
                                    <td class="text-capitalize">{{ strtolower($grouploan->group->ward->ward_name) }}</td>
                                    <td>{{ $grouploan->loanPhase->phase }}</td>
                                    <td>{{ $grouploan->level }}</td>
                                    <td>{{ $grouploan->loan_status }}</td>
                                    <td class="col-1 text-right">{{ number_format($grouploan->amount_applied) }}</td>
                                    <td class="col-1 text-right">{{ number_format($grouploan->amount_approved) }}</td>
                                    <td>
                                        @can('update loan application')
                                        <a href="{{route('const.loans.applicant', [
                                        'county' => $county->slug,
                                        'constituency' => $constituency->slug,
                                        'group'=>$grouploan->group->slug],
                                        )}}" class="text-bold bg-fuchsia p-2 rounded-lg" href="#">View</a>
                                        @endcan
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th class="text-center" colspan="7">Total</th>
                                    <th class="text-right">{{number_format($this->approved->sum('amount_applied'))}}</th>
                                    <th class="text-right">{{number_format($this->approved->sum('amount_approved'))}}</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- tab-pane -->
                    <div class="tab-pane" id="deferred">
                        <table id="tdeferred" class="table table-bordered table-striped">
                            <thead class="bg-gradient-cyan">
                                 <tr>
                                    <th>#</th>
                                    <th>Application Date</th>
                                    <th>Group</th>
                                    <th>Ward</th>
                                    <th>Phase</th>
                                    <th>Loan Level</th>
                                    <th>Loan Status</th>
                                    <th>Amount Applied</th>
                                    <th>Comments</th>
                                    <th>View</th>
                                 </tr>
                            </thead>
                            <tbody>
                                @php
                                    $i = 1;
                                @endphp
                                @foreach ($deferred as $grouploan)
                                <tr>
                                    <td>{{ $i++ }}</td>
                                    <td class="col-1">{{$grouploan->application_date}} </td>
                                    <td class="col-2 text-capitalize">{{ strtolower($grouploan->group->name) }}</td>
                                    <td class="text-capitalize">{{ strtolower($grouploan->group->ward->ward_name) }}</td>
                                    <td>{{ $grouploan->loanPhase->phase }}</td>
                                    <td>{{ $grouploan->level }}</td>
                                    <td>{{ $grouploan->loan_status }}</td>
                                    <td class="col-1 text-right">{{ number_format($grouploan->amount_applied) }}</td>
                                    <td class="col-2">{{ $grouploan->comments }}</td>
                                    <td>
                                        @can('update loan application')
                                        <a href="{{route('const.loans.applicant', [
                                        'county' => $county->slug,
                                        'constituency' => $constituency->slug,
                                        'group'=>$grouploan->group->slug],
                                        )}}" class="text-bold bg-fuchsia p-2 rounded-lg" href="#">View</a>
                                        @endcan
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th class="text-center" colspan="7">Total</th>
                                    <th class="text-right">{{number_format($this->deferred->sum('amount_applied'))}}</th>
                                    <th></th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- tab-pane -->
                    <div class="tab-pane" id="cancelled">
                        <table id="tcancelled" class="table table-bordered table-striped">
                            <thead class="bg-gradient-cyan">
                                 <tr>
                                    <th>#</th>
                                    <th>Application Date</th>
                                    <th>Group</th>
                                    <th>Ward</th>
                                    <th>Phase</th>
                                    <th>Loan Level</th>
                                    <th>Loan Status</th>
                                    <th>Amount Applied</th>
                                    <th>Comments</th>
                                    <th>View</th>
                                 </tr>
                            </thead>
                            <tbody>
                                @php
                                    $i = 1;
                                @endphp
                                @foreach ($cancelled as $grouploan)
                                <tr>
                                    <td>{{ $i++ }}</td>
                                    <td class="col-1">{{$grouploan->application_date}} </td>
                                    <td class="col-2 text-capitalize">{{ strtolower($grouploan->group->name) }}</td>
                                    <td class="text-capitalize">{{ strtolower($grouploan->group->ward->ward_name) }}</td>
                                    <td>{{ $grouploan->loanPhase->phase }}</td>
                                    <td>{{ $grouploan->level }}</td>
                                    <td>{{ $grouploan->loan_status }}</td>
                                    <td class="col-1 text-right">{{ number_format($grouploan->amount_applied) }}</td>
                                    <td class="col-2">{{ $grouploan->comments }}</td>
                                    <td>
                                        @can('update loan application')
                                        <a href="{{route('const.loans.applicant', [
                                        'county' => $county->slug,
                                        'constituency' => $constituency->slug,
                                        'group'=>$grouploan->group->slug],
                                        )}}" class="text-bold bg-fuchsia p-2 rounded-lg" href="#">View</a></td>
                                        @endcan
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th class="text-center" colspan="7">Total</th>
                                    <th class="text-right">{{number_format($this->cancelled->sum('amount_applied'))}}</th>
                                    <th></th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <!-- tab content -->

            </div>
        </div>
    </div>
</section>
@push('styles')
    <link rel="stylesheet" href="{{asset('plugins/toastr/toastr.min.css')}}">
@endpush
@push('scripts')
    <script src="{{asset('plugins/toastr/toastr.min.js')}}"></script>
    <script>
        document.addEventListener('application-deleted', function (event) {
            toastr.success('Application Deleted Successfully');
        });
    </script>
@endpush
