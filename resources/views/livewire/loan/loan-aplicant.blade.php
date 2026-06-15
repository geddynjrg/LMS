<section class="content">
    @can('update loan application')
    @if($appliedLoan != null)
    <div class="container-fluid">
        <h5 class="mt-4">Group: <span class="text-bold">{{$appliedLoan->group->name}}, {{$appliedLoan->group->ward->ward_name}}</span></h5>
        <h6 class="mb-2">Loan Application Status: <span class="text-bold">{{$appliedLoan->loan_status}} </span></h6>
        <div class="btn-group m-2 mr-auto" role="group">
            <button type="button" class="btn btn-primary mr-2" wire:click="export()" wire:loading.attr="disabled">
                <span wire:loading >loading...</span>
                <span wire:loading.remove ></i><i class="fas fa-file-excel"></i> Export Group Info </span>
            </button>
        </div>
        <div class="row">
            <div class="col-md-3">
              <!-- Group Info -->
              <div class="card card-primary">
                <div class="card-header">
                  <h3 class="card-title">{{$appliedLoan->group->name}}</h3>

                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <strong><i class="fas fa-coins"></i> Amount Applied<code>*</code> </strong>
                    <p class="text-bold text-success">KSH {{number_format($appliedLoan->amount_applied)}}/=</p>

                    <hr>
                    <strong><i class="fas fa-coins"></i> Amount Approved<code>*</code> </strong>
                    <p class="text-bold text-success">KSH {{number_format($appliedLoan->amount_approved)}}/=</p>

                    <hr>
                    <strong><i class="fas fa-clock mr-1"></i> Date Formed</strong>

                    <p class="text-muted">
                        {{$appliedLoan->group->date_formed}}
                    </p>

                    <hr>

                    <strong><i class="fas fa-clock mr-1"></i> Date Registered</strong>

                    <p class="text-muted">
                        {{$appliedLoan->group->date_registered}}
                    </p>

                    <hr>

                    <strong><i class="far fa-copyright mr-1"></i> Registration Number</strong>

                    <p class="text-muted">
                        {{$appliedLoan->group->reg_number}}
                    </p>

                    <hr>

                    <strong><i class="fas fa-qrcode mr-1"></i> GFS Code</strong>

                    <p class="text-muted">
                        {{$appliedLoan->group->gfs_code}}
                    </p>

                    <hr>

                    <strong><i class="fas fa-qrcode mr-1"></i> Identifier</strong>

                    <p class="text-muted">
                        {{$appliedLoan->group->identifier}}
                    </p>

                    <hr>

                    <strong><i class="fas fa-map-marker-alt mr-1"></i> Location </strong>

                    <p class="text-muted">Ward: {{$appliedLoan->group->ward->ward_name}}</p>
                    <p class="text-muted">Location: {{$appliedLoan->group->location}}</p>
                    <hr>

                    <strong><i class="fas fa-users-cog mr-1"></i> Category </strong>

                    <p class="text-muted">{{$appliedLoan->group->category}}</p>

                    <hr>

                    <strong><i class="fas fa-arrows-alt mr-1"></i> Sector </strong>

                    <p class="text-muted">{{$appliedLoan->group->activity}}</p>

                    <hr>

                    <strong><i class="fas fa-audio-description mr-1"></i> Description </strong>

                    <p class="text-muted">{{$appliedLoan->group->activity_desc}}</p>

                    <hr>

                    <strong><i class="fas fa-university mr-1"></i> Bank </strong>

                    <p class="text-muted">Name: {{$appliedLoan->group->bank}}</p>
                    <p class="text-muted">Acc: {{$appliedLoan->group->account}}</p>
                    <hr>
                    <div class="btn-group float-right" role="group">
                        @can('update loan application')
                        <button type="button" class="btn btn-primary btn-sm" wire:click="editGroupData()">
                            <span><i class="far fa-edit"></i> Edit Data</span>
                        </button>
                        @endcan
                    </div>
                </div>
                <!-- /.card-body -->
              </div>
              <!-- /.card -->
            </div>
            <!-- /.col -->
            <div class="col-md-9">

                <div class="card">

                  <div class="card-header p-2">
                    <ul class="nav nav-pills">
                      <li class="nav-item"><a class="nav-link active" href="#members" data-toggle="tab">Group Members</a></li>
                      <li class="nav-item"><a class="nav-link" href="#history" data-toggle="tab">Loan History</a></li>
                      <li class="nav-item"><a class="nav-link" href="#defaulters" data-toggle="tab">Defaulters <span class="badge badge-danger text-truncate"> {{ count($defaulters) }}</a></span></li>
                    </ul>

                  </div>
                  <!-- /.card-header -->
                  <div class="card-body">
                    <!-- tab content -->
                    <div class="tab-content">
                        <!-- tab-pane -->
                        <div class="active tab-pane" id="members">
                            <div class="btn-group float-right m-3" role="group">
                                <button type="button" class="btn btn-primary mr-2" wire:click="addMember({{$appliedLoan->loan_phase_id}})">
                                    <span><i class="fas fa-plus"></i> Add Member</span>
                                </button>
                            </div>
                            <div class="table-responsive p-0" >
                                <table class="table table-head-fixed table-bordered table-sm">
                                    <thead>
                                    <tr class="text-center">
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Role</th>
                                        <th>Gender</th>
                                        <th>ID. No.</th>
                                        <th>Tel. No.</th>
                                        <th>Disabled</th>
                                        <th>Action</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $i = 1;
                                        @endphp
                                    @foreach ($membersArray as $member)
                                    <tr>
                                        <td>{{$i++}}</td>
                                        <td>{{$member->member->name}}</td>
                                        <td>{{$member->role}}</td>
                                        <td>{{$member->member->gender}}</td>
                                        <td>{{$member->member->member_Id}}</td>
                                        <td>{{$member->member->number}}</td>
                                        <td class="text-center">{{$member->member->disabled?'Yes':'No';}}</td>
                                        <td class="text-center">
                                            <button type="button" wire:click="editMember('{{$member}}')" class="btn btn-sm"><i class="fas fa-edit text-primary"></i></button>
                                            <button type="button" wire:click="removeMember('{{$member}}')" class="btn btn-sm"><i  class="fas fa-trash-alt text-danger"></i></button>
                                        </td>
                                    </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <!-- /.tab-pane -->
                        <div class="tab-pane" id="history">
                            @if(count($groupLoans) < 1)
                                <p class="text-success text-bold text-center!">No History Found</p>
                            @else
                            <div class="timeline">
                                @foreach ($groupLoans as $loan)
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
                                        </div>
                                    </div>
                                @endforeach
                                    <div>
                                        <i class="fas fa-clock bg-gray"></i>
                                    </div>
                            </div>
                            @endif
                        </div>
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
                    </div>
                    <!-- /.tab content -->
                    <div class="btn-group float-right m-3" role="group">
                        @can('update loan application')
                        @if ($appliedLoan->loan_status === 'pending')
                            <button type="button" class="btn btn-primary mr-2" wire:click="approve()">
                                <span><i class="fas fa-plus"></i> Approve Application</span>
                            </button>
                            <button type="button" class="btn btn-warning mr-2" wire:click="differ()">
                                <span><i class="fas fa-plus"></i> Differ Application</span>
                            </button>
                        @elseif ($appliedLoan->loan_status === 'approved')
                            <button type="button" class="btn btn-primary mr-2" wire:click="finance()">
                                <span><i class="fas fa-plus"></i> Issue Cheque</span>
                            </button>
                            <button type="button" class="btn btn-warning mr-2" wire:click="cancel()">
                                <span><i class="fas fa-plus"></i> Cancel Cheque</span>
                            </button>
                        @endif

                        @can('delete loan application')
                        <button type="button" class="btn btn-danger mr-2" wire:click="delete()">
                            <span><i class="fas fa-trash-alt"></i></i> Delete Application</span>
                        </button>
                        @endcan
                        @endcan
                    </div>
                  </div>
                  <!-- /.card body-->
                </div>
            </div>
        </div>
        <!--/. row -->
    </div>
    @else
    <div class="container-fluid">
        <h5 class="mt-4 mb-2 text-danger text-bold">Invalid group selection!!</h5>
    </div>
    @endif
    @endcan


    <!--Approve Modal -->
    <div wire:ignore class="modal fade" id="modal-approve">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="member_modal_title">Approve Loan Application</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <livewire:approve-application >
                </div>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>
    <!-- /.Approve modal -->

    <!--finance Modal -->
    <div wire:ignore class="modal fade" id="modal-cheque">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="member_modal_title">Issue Loan Cheque</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <livewire:issue-check >
                </div>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>
    <!-- /.finance modal -->

    <!--add member Modal -->
    <div wire:ignore class="modal fade" id="modal-addmember">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="member_title">Add New Member</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <livewire:add-member >
                </div>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>
    <!-- /.add member modal -->

    <!--delete member Modal -->
    <div wire:ignore class="modal fade" id="modal-delete-member">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="member_delete">Remove Member</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <livewire:delete-member >
                </div>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>
    <!-- /.delete member modal -->

    <!--edit member Modal -->
    <div wire:ignore class="modal fade" id="modal-edit-member">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="member_edit">Edit Member</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <livewire:edit-member >
                </div>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>
    <!-- /.edit member modal -->

    <!--differ Modal -->
    <div wire:ignore class="modal fade" id="differ-modal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="differ">Differ Application</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <livewire:differ-application >
                </div>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>
    <!-- /.differ modal -->

    <!--cancel Modal -->
    <div wire:ignore class="modal fade" id="cancel-modal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="cancel">Cancel Application</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <livewire:cancel-application >
                </div>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>
    <!-- /.cancel modal -->

    <!--delete Modal -->
    <div wire:ignore class="modal fade" id="delete-modal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="delete">Delete Application</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="text-bold text-danger text-center">Are you certain you wish to proceed with deleting the application? Please note that this action cannot be undone.!!</p>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" wire:click="confirmDelete()"><i class="fas fa-trash-alt"></i> Confirm</button>
                </div>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>
    <!-- /.delete modal -->

    <!--edit-group data Modal -->
    <div  class="modal fade" id="edit-groupdata-modal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="editgroupdata">Edit Group Data</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <livewire:edit-group-data >
                </div>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>
    <!-- /.edit group data modal -->

</section>

@push('styles')
    <link rel="stylesheet" href="{{asset('plugins/toastr/toastr.min.css')}}">
@endpush
@push('scripts')
    <script src="{{asset('plugins/toastr/toastr.min.js')}}"></script>
    <script>

        document.addEventListener('show-approve-modal', function (event) {
            $("#modal-approve").modal('show');
        });

        document.addEventListener('show-cheque-modal', function (event) {
            $("#modal-cheque").modal('show');
        });

        document.addEventListener('loanApproved', function (event) {

            switch (event.detail.message) {
                case 'error':
                    $("#modal-approve").addClass('fade').modal('hide');
                    toastr.error('Error occured pls try again!!');
                    break;
                case 'success':
                    $("#modal-approve").addClass('fade').modal('hide');
                    toastr.success('Loan Approved Successfully');
                    break;
                default:
                    break;
            }
        });

        document.addEventListener('chequeIssued', function (event) {
            $("#modal-approve").addClass('fade').modal('hide');
            toastr.success('Loan Financed Successfully.');
        });
        document.addEventListener('show-addMember-modal', function (event) {
            $("#modal-addmember").modal('show');
        });
        document.addEventListener('show-deleteMember-modal', function (event) {
            $("#modal-delete-member").modal('show');
        });
        document.addEventListener('show-editMember-modal', function (event) {
            $("#modal-edit-member").modal('show');
        });
        document.addEventListener('show-differ-modal', function (event) {
            $("#differ-modal").modal('show');
        });
        document.addEventListener('show-cancel-modal', function (event) {
            $("#cancel-modal").modal('show');
        });
        document.addEventListener('show-groupdata-modal', function (event) {
            $("#edit-groupdata-modal").modal('show');
        });

        document.addEventListener('show-delete-modal', function (event) {
            if(event.detail.message == 'show'){
                $("#delete-modal").modal('show');
            } else if(event.detail.message == 'error'){
                $("#delete-modal").addClass('fade').modal('hide');
                toastr.error('Error occured pls try again!!');
            }

        });

        document.addEventListener('memberIsDeleted', function (event) {

            switch (event.detail.message) {
                case 'error':
                    $("#modal-delete-member").addClass('fade').modal('hide');
                    toastr.error('Error occured pls try again!!');
                    break;
                case 'success':
                    $("#modal-delete-member").addClass('fade').modal('hide');
                    toastr.success('Member Deleted Successfully');
                    break;
                default:
                    break;
            }
        });
        document.addEventListener('memberIsAdded', function (event) {

            switch (event.detail.message) {
                case 'error':
                    $("#modal-addmember").addClass('fade').modal('hide');
                    toastr.error('Error occured pls try again!!');
                    break;
                case 'success':
                    $("#modal-addmember").addClass('fade').modal('hide');
                    toastr.success('Member Added Successfully');
                    break;
                case 'exists':
                    toastr.error('Member Already Exists in Group!!');
                    break;
                default:
                    break;
            }
        });
        document.addEventListener('memberIsEdited', function (event) {

            switch (event.detail.message) {
                case 'error':
                    $("#modal-edit-member").addClass('fade').modal('hide');
                    toastr.error('Error occured pls try again!!');
                    break;
                case 'success':
                    $("#modal-edit-member").addClass('fade').modal('hide');
                    toastr.success('Member Edited Successfully');
                    break;
                case 'idError':
                    toastr.error('The Member ID is invalid');
                    break;
                default:
                    break;
            }
        });

        document.addEventListener('isDeffered', function (event) {

            switch (event.detail.message) {
                case 'error':
                    $("#differ-modal").addClass('fade').modal('hide');
                    toastr.error('Error occured pls try again!!');
                    break;
                case 'success':
                    $("#differ-modal").addClass('fade').modal('hide');
                    toastr.success('Application Deffered Successfully');
                    break;
                default:
                    break;
            }
        });

        document.addEventListener('isCancelled', function (event) {

            switch (event.detail.message) {
                case 'error':
                    $("#cancel-modal").addClass('fade').modal('hide');
                    toastr.error('Error occured pls try again!!');
                    break;
                case 'success':
                    $("#cancel-modal").addClass('fade').modal('hide');
                    toastr.success('Application Canclled Successfully');
                    break;
                default:
                    break;
            }
        });
        document.addEventListener('deleted_application', function (event) {
            toastr.success('Loan Application Deleted Successfully');
            $("#delete-modal").addClass('fade').modal('hide');
        });
        document.addEventListener('isEdited', function (event) {

            switch (event.detail.message) {
                case 'error':
                    $("#edit-groupdata-modal").addClass('fade').modal('hide');
                    toastr.error('Error occured pls try again!!');
                    break;
                case 'success':
                    $("#edit-groupdata-modal").addClass('fade').modal('hide');
                    toastr.success('Group Data Edited Successfully');
                    break;
                default:
                    break;
            }
        });


    </script>
@endpush
