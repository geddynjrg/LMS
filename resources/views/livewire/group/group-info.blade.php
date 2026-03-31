<section class="content">
  <div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="text-muted font-weight-bold mb-3">Group Stats</h5>
        <button wire:click="downloadReport" wire:loading.attr="disabled" class="btn btn-primary shadow-sm">
            <span wire:loading.remove>
                <i class="fas fa-file-pdf mr-2"></i> Download Stats Report
            </span>
            <span wire:loading>
                <i class="fas fa-spinner fa-spin mr-2"></i> Generating PDF...
            </span>
        </button>
    </div>
    <hr >
    <div class="row">
        @foreach($this->stats as $stat)
            <div class="col-lg-3 col-6">
                <div class="card card-hover shadow-sm border-0 mb-3">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center mb-2">
                            <div class="bg-light-{{ $stat['color'] }} p-2 rounded-circle mr-3 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                                <i class="fas {{ $stat['icon'] }} text-{{ $stat['color'] }} fa-lg"></i>
                            </div>
                            <div class="overflow-hidden">
                                <h4 class="mb-0 font-weight-bold text-dark">KES {{ number_format($stat['value']) }}</h4>
                                <p class="text-muted text-xs text-uppercase mb-0 truncate-text">{{ $stat['label'] }}</p>
                            </div>
                        </div>

                        @if($stat['has_progress'])
                            <div class="mt-2">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-xs font-weight-bold text-muted">{{ $stat['sub_label'] }}</span>
                                    <span class="text-xs font-weight-bold text-{{ $stat['color'] }}">{{ $stat['percentage'] }}%</span>
                                </div>
                                <div class="progress rounded-pill" style="height: 4px;">
                                    <div class="progress-bar bg-{{ $stat['color'] }}" style="width: {{ $stat['percentage'] }}%"></div>
                                </div>
                            </div>
                        @else
                            <div class="mt-2 pt-1 border-top">
                                <small class="text-xs text-muted font-weight-bold">
                                    <i class="fas fa-info-circle mr-1 text-{{ $stat['color'] }}"></i> {{ $stat['sub_label'] }}
                                </small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row mt-2">
        <div class="col-lg-6">
            <div class="card card-hover shadow-sm border-0 mb-3">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <p class="text-muted text-xs text-uppercase mb-1 font-weight-bold">Collection Efficiency</p>
                            <h3 class="mb-0 font-weight-bold text-dark">{{ $this->collectionEfficiency }}%</h3>
                            <span class="badge badge-pill badge-light-{{ $this->collectionEfficiency >= 90 ? 'success' : 'warning' }} text-{{ $this->collectionEfficiency >= 90 ? 'success' : 'warning' }} text-xs">
                                {{ $this->collectionEfficiency >= 90 ? 'High Performance' : 'Attention Needed' }}
                            </span>
                        </div>
                        <div class="text-right">
                            <span class="text-muted text-xs d-block">Paid vs. Due</span>
                            <span class="font-weight-bold text-dark text-sm">KES {{ number_format($this->totalAmountRepaid) }} / {{ number_format($this->totalAmountDue) }}</span>
                        </div>
                    </div>
                    <div class="progress rounded-pill" style="height: 6px;">
                        <div class="progress-bar bg-{{ $this->collectionEfficiency >= 90 ? 'success' : 'warning' }}" style="width: {{ $this->collectionEfficiency }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card card-hover shadow-sm border-0 mb-3">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <p class="text-muted text-xs text-uppercase mb-1 font-weight-bold">PAR (Portfolio at Risk)</p>
                            <h3 class="mb-0 font-weight-bold {{ $this->parRate > 5 ? 'text-danger' : 'text-dark' }}">{{ $this->parRate }}%</h3>
                            <span class="badge badge-pill badge-light-{{ $this->parRate <= 5 ? 'success' : 'danger' }} text-{{ $this->parRate <= 5 ? 'success' : 'danger' }} text-xs">
                                {{ $this->parRate <= 5 ? 'Healthy Portfolio' : 'Risk Exposure' }}
                            </span>
                        </div>
                        <div class="text-right">
                            <span class="text-muted text-xs d-block">Arrears vs. Issued</span>
                            <span class="font-weight-bold text-dark text-sm">KES {{ number_format($this->totalAmountInArrears) }} / {{ number_format($this->totalAmountIssued) }}</span>
                        </div>
                    </div>
                    <div class="progress rounded-pill" style="height: 6px;">
                        <div class="progress-bar bg-{{ $this->parRate <= 5 ? 'success' : 'danger' }}" style="width: {{ $this->parRate }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>



    {{-- <h5 class="mt-4 mb-2">Group: {{$group->name}}, {{$group->ward->ward_name}}</h5>

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
    </div> --}}


    <h5 class="text-muted font-weight-bold mb-3 mt-4">Group Data</h5>
    <hr class="my-4">


      <!-- row -->
    <div class="row">
        <div class="col-md-3">
            <div class="card card-primary card-outline">
                <div class="card-body box-profile">
                    <div class="text-center">
                        <span class="btn btn-lg btn-circle bg-soft-primary mb-3">
                            <i class="fas fa-users fa-2x text-primary"></i>
                        </span>
                    </div>

                    <h3 class="profile-username text-center text-bold mb-0">{{$group->name}}</h3>
                    <p class="text-muted text-center mb-3">
                        <span class="badge badge-info-light">{{$group->category}}</span>
                    </p>

                    <ul class="list-group list-group-unbordered mb-3">
                        <li class="list-group-item py-2">
                            <b class="text-secondary"><i class="fas fa-fingerprint mr-1"></i> GFS Code</b>
                            <a class="float-right text-dark font-weight-bold">{{$group->currentLoan->gfs_code ?? 'N/A'}}</a>
                        </li>
                        <li class="list-group-item py-2">
                            <b class="text-secondary"><i class="far fa-id-card mr-1"></i> Reg No.</b>
                            <a class="float-right text-dark font-weight-bold">{{$group->reg_number}}</a>
                        </li>
                        <li class="list-group-item py-2 border-bottom-0">
                            <b class="text-secondary"><i class="fas fa-tag mr-1"></i> ID</b>
                            <a class="float-right text-muted">{{$group->identifier}}</a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="card shadow-sm mt-3">
                <div class="card-header bg-light">
                    <h3 class="card-title text-sm text-bold uppercase">Operational Details</h3>
                </div>
                <div class="card-body p-3">
                    <div class="mb-3">
                        <small class="text-uppercase text-muted font-weight-bold">Location & Sector</small>
                        <div class="d-flex mt-2">
                            <i class="fas fa-map-marker-alt text-danger mt-1 mr-2"></i>
                            <p class="text-sm mb-0">
                                <strong>{{$group->ward->ward_name}}</strong><br>
                                {{$group->location}}
                            </p>
                        </div>
                        <div class="d-flex mt-2">
                            <i class="fas fa-briefcase text-orange mt-1 mr-2"></i>
                            <p class="text-sm mb-0"><strong>{{$group->currentLoan->sector ?? 'N/A'}}</strong></p>
                        </div>
                    </div>

                    <hr class="my-2">

                    <div class="mb-3">
                        <div class="row">
                            <div class="col-6 border-right">
                                <small class="text-muted d-block">Formed</small>
                                <span class="text-sm">{{$group->date_formed}}</span>
                            </div>
                            <div class="col-6 pl-3">
                                <small class="text-muted d-block">Registered</small>
                                <span class="text-sm">{{$group->date_registered}}</span>
                            </div>
                        </div>
                    </div>

                    <hr class="my-2">

                    <div class="bg-light p-2 rounded">
                        <small class="text-uppercase text-muted font-weight-bold d-block mb-1">Banking Details</small>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-sm text-bold"><i class="fas fa-university text-navy mr-1"></i> {{$group->bank}}</span>
                        </div>
                        <span class="text-sm text-muted d-block mt-1">Acc: <code class="text-primary font-weight-bold">{{$group->account}}</code></span>
                    </div>

                    <div class="mt-3">
                        <small class="text-uppercase text-muted font-weight-bold">Description</small>
                        <p class="text-sm text-muted italic mt-1" style="line-height: 1.2;">"{{$group->activity_desc}}"</p>
                    </div>

                    @can('update group')
                        {{-- <button wire:click="editGroupData()" class="btn btn-primary btn-block btn-sm mt-3 shadow-sm">
                            <span wire:loading.remove wire:target="editGroupData">
                                <i class="far fa-edit"></i> Edit Group Data
                            </span>
                            <span wire:loading wire:target="editGroupData">
                                <i class="fas fa-spinner fa-spin"></i>
                            </span>
                        </button> --}}
                    @endcan
                </div>
            </div>

            <div class="card shadow-sm mt-3">
                <div class="card-header bg-light">
                    <h3 class="card-title text-sm text-bold text-uppercase">
                        <i class="fas fa-briefcase mr-1 text-primary"></i> Business Profile
                    </h3>
                </div>
                <div class="card-body p-3">

                    <div class="row mb-3">
                        <div class="col-6 border-right">
                            <small class="text-muted d-block uppercase font-weight-bold" style="font-size: 10px;">Group Category</small>
                            <span class="badge badge-primary shadow-sm mt-1 px-2 py-1">
                                <i class="fas fa-users-cog mr-1"></i> {{$group->currentLoan->category ?? 'N/A'}}
                            </span>
                        </div>
                        <div class="col-6 pl-3">
                            <small class="text-muted d-block uppercase font-weight-bold" style="font-size: 10px;">Nature of Business</small>
                            <p class="text-sm mb-0 mt-1 font-weight-bold text-navy">
                                {{$group->nature_of_business ?? 'Not Specified'}}
                            </p>
                        </div>
                    </div>

                    <hr class="my-2">

                    <div class="mb-3">
                        <small class="text-muted d-block uppercase font-weight-bold mb-2" style="font-size: 10px;">Primary Sector / Activity</small>
                        <div class="p-2 border rounded bg-light shadow-none">
                            <div class="d-flex align-items-center">
                                <div class="mr-3 bg-white p-2 rounded shadow-sm">
                                    <i class="fas fa-project-diagram text-orange"></i>
                                </div>
                                <div>
                                    <span class="text-sm d-block font-weight-bold text-dark">{{$group->activity}}</span>
                                    <small class="text-muted">Main Economic Sector</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <small class="text-muted d-block uppercase font-weight-bold mb-1" style="font-size: 10px;">Detailed Scope of Work</small>
                        <div class="text-sm text-muted p-2 rounded border-left border-primary" style="background-color: #f8f9fa; border-left-width: 3px !important;">
                            <i class="fas fa-quote-left fa-xs text-light mr-1"></i>
                            <em>{{$group->activity_desc}}</em>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="col-md-9">
            <div class="card">
                <div class="card-header p-2">
                    <ul class="nav nav-pills">
                        <li class="nav-item"><a class="nav-link active" href="#members" data-toggle="tab">Group Members</a></li>
                        <li class="nav-item"><a class="nav-link" href="#loans" data-toggle="tab">Loan History</a></li>
                        <li class="nav-item"><a class="nav-link" href="#defaulters" data-toggle="tab">Defaulters <span class="badge badge-danger text-truncate"> {{ count($defaulters) }}</a></span></li>
                    </ul>
                </div>

                <div class="card-body">
                    <div class="tab-content">
                        <div class="active tab-pane" id="members">
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <div class="d-flex justify-content-between align-items-center bg-light p-2 rounded border shadow-sm">
                                        <span>
                                            <i class="fas fa-info-circle text-primary mr-1"></i>
                                            {{-- Accessing the last loan phase for summary stats --}}
                                            @php $latestLoan = $groupData->groupLoans->last(); @endphp

                                            Latest Phase ({{ $latestLoan->loanPhase->phase }}):
                                            <strong>{{ $latestLoan->total_members_count ?? 0}} </strong> Members
                                            <small class="ml-2 ">
                                                (M: {{ $latestLoan->male_count ?? 0 }} | F: {{ $latestLoan->female_count ?? 0 }} | PWD: {{ $latestLoan->pwd_count ?? 0}})
                                            </small>
                                        </span>

                                    </div>
                                </div>
                            </div>

                            {{-- Tabs for different phases --}}
                            <nav class="w-100">
                                <div class="nav nav-tabs custom-tabs" id="product-tab" role="tablist">
                                    @foreach ($groupData->groupLoans as $loanTab)
                                        <a class="nav-item nav-link {{ $loop->last ? 'active' : '' }} font-weight-bold"
                                        id="tab-{{$loanTab->id}}" data-toggle="tab" href="#content-{{$loanTab->id}}">
                                        {{$loanTab->loanPhase->phase}}
                                        </a>
                                    @endforeach
                                </div>
                            </nav>

                            <div class="tab-content p-3 border border-top-0 bg-white">
                                @foreach ($groupData->groupLoans as $individualLoan)
                                    <div class="tab-pane fade {{ $loop->last ? 'show active' : '' }}" id="content-{{$individualLoan->id}}">

                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="text-navy font-weight-bold mb-0">
                                                <i class="fas fa-users mr-1"></i> Members for {{ $individualLoan->loanPhase->phase }}
                                                <small class="ml-2 ">
                                                    (M: {{ $individualLoan->male_count ?? 0 }} | F: {{ $individualLoan->female_count ?? 0 }} | PWD: {{ $individualLoan->pwd_count ?? 0}})
                                                </small>
                                            </h6>
                                            <div class="btn-group">
                                                {{-- PDF Download Button --}}
                                                {{-- <x-modal-trigger
                                                    class="shadow-sm"
                                                    target="exportMemberList({{ $individualLoan->id }})"
                                                    label="Download PDF"
                                                    title="Download PDF"
                                                    icon="fas fa-file-pdf"
                                                    variant="outline-danger"
                                                    defaultLabel="loading..."
                                                /> --}}

                                                {{-- Context-aware Add Member Button --}}
                                                @can('create member')
                                                    <x-modal-trigger
                                                        class="shadow-sm ml-2"
                                                        target="addMember({{ $individualLoan->id}})"
                                                        label="Add New Member"
                                                        title="Add New Member"
                                                        icon="fas fa-plus"
                                                        variant="success"
                                                        defaultLabel="loading..."
                                                    />
                                                @endcan
                                            </div>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table table-hover table-striped text-sm">
                                                <thead class="bg-navy">
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Full Name</th>
                                                        <th>Gender</th>
                                                        <th>ID Number</th>
                                                        <th>Position</th>
                                                        <th>Contact</th>
                                                        <th class="text-center">PWD</th>
                                                        <th class="text-right">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse ($individualLoan->groupMembers as $index => $groupMember)
                                                    <tr>
                                                        <td>{{ $index + 1 }}</td>
                                                        <td class="font-weight-bold">{{ $groupMember->member->name }}</td>
                                                        <td>
                                                            <span class="badge {{ $groupMember->member->gender == 'Male' ? 'badge-info' : 'badge-danger' }} px-2">
                                                                {{ substr($groupMember->member->gender, 0, 1) }}
                                                            </span>
                                                        </td>
                                                        <td><code class="text-dark">{{ $groupMember->member_Id }}</code></td>
                                                        <td><span class="text-uppercase small font-weight-bold text-muted">{{ $groupMember->role }}</span></td>
                                                        <td>{{ $groupMember->member->number }}</td>
                                                        <td class="text-center">
                                                            {!! $groupMember->member->disabled ? '<span class="text-warning"><i class="fas fa-wheelchair"></i></span>' : '<span class="text-muted">No</span>' !!}
                                                        </td>
                                                        <td class="text-right">
                                                            <div class="btn-group">
                                                                @can('update member')
                                                                    <button wire:click="editMember({{ $groupMember->member_Id }}, {{ $individualLoan->id }})" class="btn btn-xs btn-outline-primary" title="Edit">
                                                                        <i class="fas fa-edit"></i>
                                                                    </button>
                                                                @endcan
                                                                @can('delete member')
                                                                    <button wire:click="removeMember({{ $groupMember->member->id }})" class="btn btn-xs btn-outline-danger ml-1" title="Delete">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                @endcan
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    @empty
                                                    <tr>
                                                        <td colspan="8" class="text-center py-4 text-muted">
                                                            <i class="fas fa-user-slash fa-2x mb-2 d-block"></i>
                                                            No members found for this phase.
                                                        </td>
                                                    </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

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
                </div>
            </div>
        </div>
    </div>
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
    <style>
        .bg-soft-primary { background-color: #e7f1ff; border-radius: 50%; padding: 15px; display: inline-block; }
.badge-info-light { background-color: #d1ecf1; color: #0c5460; font-weight: 500; }
.uppercase { text-transform: uppercase; letter-spacing: 1px; }
    </style>
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
