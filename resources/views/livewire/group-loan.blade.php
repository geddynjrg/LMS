<section class="content">
    <div class="container-fluid">
        <h5 class="mt-4 mb-2">Group: {{$group->name}}</h5>
        <div class="row">
            <!-- /.col -->
            <div class="col-md-4 col-sm-6 col-12">
                <div class="info-box bg-gradient-success">
                    <span class="info-box-icon"><i class="fas fa-hand-holding-usd"></i></span>

                    <div class="info-box-content">
                      <span class="info-box-text text-wrap">Total Amount Financed</span>
                      <span class="info-box-number">Total KES {{ number_format($amount_issued)}}/=</span>
                    </div>
                    <!-- /.info-box-content -->
                </div>
                <!-- /.info-box -->
            </div>
            <!-- /.col -->
            <div class="col-md-4 col-sm-6 col-12">
                <div class="info-box bg-gradient-warning">
                    <span class="info-box-icon"><i class="fas fa-reply-all"></i></span>

                    <div class="info-box-content">
                      <span class="info-box-text text-wrap">Total Repaid</span>
                      <span class="info-box-number">Total KES {{ number_format($amount_repaid) }}/=</span>
                    </div>
                    <!-- /.info-box-content -->
                </div>
                <!-- /.info-box -->
            </div>
            <!-- /.col -->
            <div class="col-md-4 col-sm-6 col-12">
                <div class="info-box bg-gradient-danger">
                    <span class="info-box-icon"><i class="fas fa-money-bill"></i></span>

                    <div class="info-box-content">
                      <span class="info-box-text text-wrap">Balance</span>
                      <span class="info-box-number">Total KES {{number_format($amount_issued - $amount_repaid)}}/= </span>
                    </div>
                    <!-- /.info-box-content -->
                </div>
                <!-- /.info-box -->
            </div>
        </div>
        <!--/row -->

        <div class="card">
            <div class="card-header p-2">
                <ul class="nav nav-pills">
                    <li class="nav-item"><a class="nav-link active" href="#loans" data-toggle="tab">Group Loans</a></li>
                    <li class="nav-item"><a class="nav-link" href="#members" data-toggle="tab">Group Members</a></li>
                    <li class="nav-item"><a class="nav-link" href="#defaulters" data-toggle="tab">Defaulters <span class="badge badge-danger text-truncate"> {{ count($defaulters) }}</a></span></li>
                </ul>
            </div><!-- /.card-header -->
            <div class="card-body">
                <div class="tab-content">
                    <div class="active tab-pane" id="loans">
                        <!-- The timeline -->
                        <div class="timeline">
                        @foreach ($group->groupLoans as $loan)

                            <!-- timeline time label -->
                            <div class="time-label">
                                <span class="{{$loan->amount_repaid >= $loan->amount_issued?'bg-success':'bg-red'}}">{{$loan->loanPhase->phase}}: {{ date('l, j F, Y', strtotime($loan->disbursement_date)) }} <br>Amount Issued: {{ number_format($loan->amount_issued, 0) }}/=</span>
                            </div>

                            <!-- timeline item -->
                            <div>
                                <i class="fas fa-history bg-gradient-orange"></i>
                                <div class="timeline-item">
                                    <span class="time"><i class="fas fa-clock"> </i></span>
                                    <h3 class="timeline-header text-primary text-bold">Amount Repaid - KES {{ number_format($loan->amount_repaid, 0) }}/=
                                        <br>
                                        <small class="text-gray text-bold">Balance - KES {{ number_format($loan->amount_issued - $loan->amount_repaid, 0) }}/=</small>
                                    </h3>
                                    <div class="timeline-body">
                                        <div class="card collapsed-card {{$loan->amount_repaid >= $loan->amount_issued?'card-success':'card-danger'}}">
                                            <div class="card-header">
                                                <div class="row">
                                                <div class="col-12">
                                                    <h3 class="card-title">Receipts</h3>

                                                    <div class="card-tools float-right">
                                                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i>
                                                    </button>
                                                        <button type="button" class="btn btn-tool" data-card-widget="maximize">
                                                        <i class="fas fa-expand"></i>
                                                        </button>

                                                    </div>
                                                    <!-- /.card-tools -->
                                                </div>

                                                </div>

                                            </div>
                                            <!-- /.card-header -->
                                            <div class="card-body table-responsive p-1">
                                                <table class="table table-sm">
                                                    <thead>
                                                    <tr>
                                                        <th style="">#</th>
                                                        <th>Reciept</th>
                                                        <th>Date</th>
                                                        <th>Amount</th>
                                                        <th>Ref No.</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                        @php
                                                            $j = 1;
                                                        @endphp
                                                        @foreach ($loan->loanReceipts as $repayment)
                                                        <tr>
                                                            <td>{{$j++}}.</td>
                                                            <td>{{$repayment->receipt_number}}</td>
                                                            <td>{{$repayment->transaction_date}}</td>
                                                            <td>{{ number_format($repayment->amount, 0)}}/=</td>
                                                            <td>{{$repayment->reference_number}}</td>
                                                        </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                            <!-- /.card-body -->
                                        </div>
                                        <!-- /.card -->
                                    </div>
                                </div>
                            </div>
                        @endforeach




                            <div>
                            <i class="fas fa-clock bg-gray"></i>
                            </div>
                        </div>
                    </div>
                    <!-- /.tab-pane -->
                    <div class=" tab-pane" id="members">
                        <div class="row ">
                            <nav class="w-100">
                                <div class="nav nav-tabs" id="product-tab" role="tablist">
                                @if ($applications > 1)
                                    @foreach ($group->groupLoans as $groupLoan)
                                        <a class="nav-item nav-link {{$group->groupLoans[0] == $groupLoan?'active':''}} " id="group-loan-tab-{{$groupLoan->id}}" data-toggle="tab" href="#group-desc-{{$groupLoan->id}}" role="tab" aria-controls="group-desc-{{$groupLoan->id}}" aria-selected="true">{{$groupLoan->loanPhase->phase}}</a>

                                    @endforeach

                                @else
                                    <a class="nav-item nav-link active" id="loan-desc-tab" data-toggle="tab" href="#loan-desc" role="tab" aria-controls="loan-desc" aria-selected="true">{{$group->groupLoans[0]->loanPhase->phase}}</a>
                                @endif


                                </div>
                            </nav>
                            <div class="tab-content p-3 w-100" id="nav-tabContent">
                                @if ($applications > 1)
                                    @foreach ($group->groupLoans as $groupLoan)
                                        <div class="tab-pane fade {{$group->groupLoans[0] == $groupLoan?'show active':''}}" id="group-desc-{{$groupLoan->id}}" role="tabpanel" aria-labelledby="group-loan-tab-{{$groupLoan->id}}">
                                            <div class="table-responsive p-0" style="height: 590px;">
                                                <table class="table table-head-fixed table-bordered table-sm">
                                                    <thead>
                                                    <tr class="text-center">
                                                        <th>#</th>
                                                        <th class="text-left">Name</th>
                                                        <th>Gender</th>
                                                        <th>ID. No.</th>
                                                        <th>Role</th>
                                                        <th>Tel. No.</th>
                                                        <th>Disabled</th>
                                                        <th>Action</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    @php
                                                        $i = 1;
                                                    @endphp
                                                    @foreach ($groupLoan->groupMembers as $member)
                                                    <tr class="text-center">
                                                        <td>{{$i++}}.</td>
                                                        <td class="text-left">{{$member->member->name}}</td>
                                                        <td>{{$member->member->gender}}</td>
                                                        <td>{{$member->member_Id}}</td>
                                                        <td>{{$member->role}}</td>
                                                        <td>{{$member->member->number}}</td>

                                                        <td class="text-center">{{$member->member->disabled?'Yes':'No';}}</td>
                                                        <td class="text-center">
                                                            @can('update member')
                                                                <button class="btn btn-sm"><i class="fas fa-edit text-primary"></i></button>
                                                            @endcan
                                                            @can('delete member')
                                                                /
                                                                <button class="btn btn-sm"><i class="fas fa-trash-alt text-danger"></i></button>

                                                            @endcan
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="tab-pane fade show active" id="loan-desc" role="tabpanel" aria-labelledby="loan-desc-tab">
                                        <div class="table-responsive p-0" style="height: 500px;">
                                            <table class="table table-head-fixed table-bordered table-sm">
                                                <thead>
                                                <tr class="text-center">
                                                    <th>#</th>
                                                    <th class="text-left">Name</th>
                                                    <th>Gender</th>
                                                    <th>ID. No.</th>
                                                    <th>Role</th>
                                                    <th>Tel. No.</th>
                                                    <th>Disabled</th>
                                                    <th>Action</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @php
                                                    $i = 1;
                                                @endphp
                                                @foreach ($group->groupLoans[0]->groupMembers as $member)
                                                <tr class="text-center">
                                                    <td>{{$i++}}.</td>
                                                    <td class="text-left">{{$member->member->name}}</td>
                                                    <td>{{$member->member->gender}}</td>
                                                    <td>{{$member->member_Id}}</td>
                                                    <td>{{$member->role}}</td>
                                                    <td>{{$member->member->number}}</td>

                                                    <td class="text-center">{{$member->member->disabled?'Yes':'No';}}</td>
                                                    <td class="text-center">
                                                        <button class="btn btn-sm"><i class="fas fa-edit text-primary"></i></button>
                                                        /
                                                        <button class="btn btn-sm"><i  class="fas fa-trash-alt text-danger"></i></button>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endif

                                </div>
                            </div>
                    </div>
                    <!-- /.tab-pane -->
                    <div class="tab-pane" id="defaulters">
                        @if(count($defaulters) < 1)
                            <p class="text-success text-bold text-center!">No Defaulter Found!</p>
                        @else
                            @php
                                $i = 1;
                            @endphp
                            @foreach ($defaulters as $defaults)
                            <hr class="bold bg-gradient-red ">
                                <h6>{{$i++}}. {{$defaults[0]->member->name}} - ID: {{$defaults[0]->member->member_Id}}</h6>
                                <div class="table-responsive p-0" id="defaulters-data">
                                    <table class="table table-head-fixed table-bordered table-sm">
                                        <thead>
                                        <tr class="text-center">
                                            <th>#</th>
                                            <th>Group Name</th>
                                            <th>Constituency</th>
                                            <th>Position</th>
                                            <th>Loan Phase</th>
                                            <th>Amount Issued</th>
                                            <th>Amount Repaid</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($defaults as $key => $groupMember)
                                            <tr >

                                                <td>{{$key + 1}}</td>
                                                <td>{{$groupMember->groupLoan->group->name}}</td>
                                                <td>{{$groupMember->groupLoan->loanPhase->constituency->constituency_name}}</td>
                                                <td>{{$groupMember->role}}</td>
                                                <td>{{$groupMember->groupLoan->loanPhase->phase}}</td>
                                                <td class='text-center text-bold'>{{number_format($groupMember->groupLoan->amount_issued)}}</td>
                                                <td class='text-center text-bold'>{{number_format($groupMember->groupLoan->amount_repaid)}}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                            @endforeach
                        @endif
                    </div>
                    <!-- /.tab-pane -->

                </div>
                <!-- /.tab-content -->
            </div><!-- /.card-body -->
        </div>
        <!-- /.card -->
    </div>
</section>
