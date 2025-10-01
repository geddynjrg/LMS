<section class="content">
  <div class="container-fluid">
    <div class="row">
        <div  class="col-6 mr-auto ml-auto form-group">
            <label for="selectgroup">Search Group</label>
            <div wire:ignore>
                <select class="form-control select2bs4" style="width: 100%;">
                    <option value="0" >Select group...</option>
                    @foreach ($groups as $sgroup )
                        <option value="{{ $sgroup->slug }}">{{ $sgroup->code}} - {{ $sgroup->name}} - {{ $sgroup->identifier}}</option>
                    @endforeach
                </select>
                @error('selectedGroup')
                    <span class="text-sm text-danger">{{ $message }}</span>
                @enderror
            </div>
        </div>
    </div>
      <h5 class="mt-4 mb-2">Group: {{$group->name}}, {{$group->ward->ward_name}}</h5>
      <div class="row">
          <div class="col-md-3 col-sm-6 col-12">
              <div class="info-box bg-gradient-info">
                  <span class="info-box-icon"><i class="far fa-copy"></i></span>

                  <div class="info-box-content">
                  <span class="info-box-text text-wrap">Approved Applications</span>
                  <span class="info-box-number">{{ $applications }}</span>

                  </div>
                  <!-- /.info-box-content -->
              </div>
              <!-- /.info-box -->
          </div>
          <!-- /.col -->
          <div class="col-md-3 col-sm-6 col-12">
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
          <div class="col-md-3 col-sm-6 col-12">
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
          <div class="col-md-3 col-sm-6 col-12">
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

      <!-- row -->
      <div class="row">
        <div class="col-md-3">
          <!-- Group Info -->
          <div class="card card-primary">
            <div class="card-header">
              <h3 class="card-title">{{$group->name}}</h3>
            </div>
            <!-- /.card-header -->
            <div class="card-body">
                <strong><i class="fas fa-clock mr-1"></i> Date Formed</strong>

                <p class="text-muted">
                    {{$group->date_formed}}
                </p>

                <hr>

                <strong><i class="fas fa-clock mr-1"></i> Date Registered</strong>

                <p class="text-muted">
                    {{$group->date_registered}}
                </p>

                <hr>

                <strong><i class="far fa-copyright mr-1"></i> Registration Number</strong>

                <p class="text-muted">
                    {{$group->reg_number}}
                </p>

                <hr>

                <strong><i class="fas fa-qrcode mr-1"></i> GFS Code</strong>

                <p class="text-muted">
                    {{$group->gfs_code}}
                </p>

                <hr>

                <strong><i class="fas fa-qrcode mr-1"></i> Identifier</strong>

                <p class="text-muted">
                    {{$group->identifier}}
                </p>

                <hr>

                <strong><i class="fas fa-map-marker-alt mr-1"></i> Location </strong>

                <p class="text-muted">Watd: {{$group->ward->ward_name}}</p>
                <p class="text-muted">Location: {{$group->location}}</p>
                <hr>

                <strong><i class="fas fa-users-cog mr-1"></i> Category </strong>

                <p class="text-muted">{{$group->category}}</p>

                <hr>

                <strong><i class="fas fa-arrows-alt mr-1"></i> Sector </strong>

                <p class="text-muted">{{$group->activity}}</p>

                <hr>

                <strong><i class="fas fa-audio-description mr-1"></i> Description </strong>

                <p class="text-muted">{{$group->activity_desc}}</p>

                <hr>

                <strong><i class="fas fa-university mr-1"></i> Bank </strong>

                <p class="text-muted">Name: {{$group->bank}}</p>
                <p class="text-muted">Acc: {{$group->account}}</p>
                <hr>
                <div class="btn-group float-right" role="group">
                  @can('update group')
                    <button type="button"
                        class="btn btn-sm btn-primary mr-2"
                        wire:click="editGroupData()"
                        wire:loading.attr="disabled">

                        <span wire:loading.remove wire:target="editGroupData">
                            <span><i class="far fa-edit"></i> Edit Data</span>
                        </span>

                        <span wire:loading wire:target="editGroupData">
                            <i class="fas fa-spinner fa-spin"></i> Loading...
                        </span>
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
                <li class="nav-item"><a class="nav-link" href="#loans" data-toggle="tab">Loan History</a></li>
                <li class="nav-item"><a class="nav-link" href="#defaulters" data-toggle="tab">Defaulters <span class="badge badge-danger text-truncate"> {{ count($defaulters) }}</a></span></li>
              </ul>
            </div><!-- /.card-header -->
            <div class="card-body">
              <div class="tab-content">
                <div class="active tab-pane" id="members">
                    <div class="row ">
                        <nav class="w-100">
                          <div class="nav nav-tabs" id="product-tab" role="tablist">
                            @if ($applications > 1)
                                @foreach ($group->groupLoans as $groupLoan)
                                    <a class="nav-item nav-link {{$group->groupLoans->last() == $groupLoan?'active':''}} " id="group-loan-tab-{{$groupLoan->id}}" data-toggle="tab" href="#group-desc-{{$groupLoan->id}}" role="tab" aria-controls="group-desc-{{$groupLoan->id}}" aria-selected="true">{{$groupLoan->loanPhase->phase}}</a>

                                @endforeach

                            @else
                                <a class="nav-item nav-link active" id="loan-desc-tab" data-toggle="tab" href="#loan-desc" role="tab" aria-controls="loan-desc" aria-selected="true">{{$group->groupLoans->last()->loanPhase->phase}}</a>
                            @endif


                          </div>
                        </nav>
                        <div class="tab-content p-3 w-100" id="nav-tabContent">
                            @if ($applications > 1)
                                @foreach ($group->groupLoans as $groupLoan)
                                    <div class="tab-pane fade {{$group->groupLoans->last() == $groupLoan?'show active':''}}" id="group-desc-{{$groupLoan->id}}" role="tabpanel" aria-labelledby="group-loan-tab-{{$groupLoan->id}}">
                                      <div class="btn-group float-right m-3" role="group">
                                        @can('create member')
                                           <button type="button"
                                                class="btn btn-primary mr-2"
                                                wire:click="addMember({{ $group->groupLoans->last()->id }})"
                                                wire:loading.attr="disabled">

                                                <span wire:loading.remove wire:target="addMember">
                                                    <i class="fas fa-plus"></i> Add Member
                                                </span>

                                                <span wire:loading wire:target="addMember">
                                                    <i class="fas fa-spinner fa-spin"></i> Loading...
                                                </span>
                                            </button>
                                        @endcan

                                      </div>
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
                                                        <button type="button"
                                                            class="btn btn-sm"
                                                            wire:click="editMember('{{$member}}')"
                                                            wire:loading.attr="disabled">

                                                            <span wire:loading.remove wire:target="editMember">
                                                                <i class="fas fa-edit text-primary"></i>
                                                            </span>

                                                            <span wire:loading wire:target="editMember">
                                                                <i class="fas fa-spinner fa-spin"></i>
                                                            </span>
                                                        </button> /
                                                        @endcan



                                                        @can('delete member')
                                                            <button type="button"
                                                            class="btn btn-sm"
                                                            wire:click="removeMember('{{$member}}')"
                                                            wire:loading.attr="disabled">

                                                            <span wire:loading.remove wire:target="removeMember">
                                                                <i class="fas fa-trash-alt text-danger"></i>
                                                            </span>

                                                            <span wire:loading wire:target="removeMember">
                                                                <i class="fas fa-spinner fa-spin"></i>
                                                            </span>
                                                        </button>
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
                                <div class="btn-group float-right m-3" role="group">
                                    @can('create member')
                                      <button type="button"
                                        class="btn btn-primary mr-2"
                                        wire:click="addMember({{ $group->groupLoans->last()->id }})"
                                        wire:loading.attr="disabled">

                                        <span wire:loading.remove wire:target="addMember">
                                            <i class="fas fa-plus"></i> Add Member
                                        </span>

                                        <span wire:loading wire:target="addMember">
                                            <i class="fas fa-spinner fa-spin"></i> Loading...
                                        </span>
                                      </button>
                                    @endcan

                                </div>
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
                                            @foreach ($group->groupLoans->last()->groupMembers as $member)
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
                                                        <button type="button"
                                                            class="btn btn-sm"
                                                            wire:click="editMember('{{$member}}')"
                                                            wire:loading.attr="disabled">

                                                            <span wire:loading.remove wire:target="editMember">
                                                                <i class="fas fa-edit text-primary"></i>
                                                            </span>

                                                            <span wire:loading wire:target="editMember">
                                                                <i class="fas fa-spinner fa-spin"></i>
                                                            </span>
                                                        </button> /
                                                        @endcan



                                                        @can('delete member')
                                                            <button type="button"
                                                            class="btn btn-sm"
                                                            wire:click="removeMember('{{$member}}')"
                                                            wire:loading.attr="disabled">

                                                            <span wire:loading.remove wire:target="removeMember">
                                                                <i class="fas fa-trash-alt text-danger"></i>
                                                            </span>

                                                            <span wire:loading wire:target="removeMember">
                                                                <i class="fas fa-spinner fa-spin"></i>
                                                            </span>
                                                        </button>
                                                        @endcan
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
                <div class="tab-pane" id="loans">
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
        <!-- /.col -->
      </div>
      <!-- /.row -->
  </div>


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
                @if (auth()->user()->hasPermissionTo('delete member'))
                <livewire:edit-group-data >
                @else
                <span class="text-center text-danger">you are not authorised to perform this action</span>
                @endif
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
  </div>
  <!-- /.edit group data modal -->

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
                @if (auth()->user()->hasPermissionTo('delete member'))
                    <livewire:delete-member >
                @else
                    <span class="text-center text-danger">You are not authorised to perform this action!!</span>
                @endif

            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
  </div>
  <!-- /.delete member modal -->
</section>
@push('styles')
    <link rel="stylesheet" href="{{asset('plugins/toastr/toastr.min.css')}}">
    <link rel="stylesheet" href="{{asset('plugins/select2/css/select2.min.css')}}">
    <link rel="stylesheet" href="{{asset('plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css')}}">
@endpush
@push('scripts')
    <script src="{{asset('plugins/toastr/toastr.min.js')}}"></script>
    <script>

      document.addEventListener('show-groupdata-modal', function (event) {
            $("#edit-groupdata-modal").modal('show');
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
      document.addEventListener('show-addMember-modal', function (event) {
            $("#modal-addmember").modal('show');
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
      document.addEventListener('show-editMember-modal', function (event) {
            $("#modal-edit-member").modal('show');
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
      document.addEventListener('show-deleteMember-modal', function (event) {
        $("#modal-delete-member").modal('show');
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
    </script>
    <script src="{{asset('plugins/select2/js/select2.full.min.js')}}"></script>
    <script>
         $(function () {
            //Livewire.dispatch('register-lock', {registerId: 2});
            //Initialize Select2 Elements
            $('.select2bs4').select2({
                theme: 'bootstrap4'
            })

            $('.select2bs4').on('change', function (e) {
                @this.set('selectedGroupSlug', e.target.value);

                Livewire.dispatch('group-select', {slug: e.target.value});
            })
        });
    </script>
@endpush
