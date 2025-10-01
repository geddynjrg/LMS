<div>
@if(empty($data))
    <div class="text-center">
        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
        <span>Loading...</span>
    </div>
@else

@if ($loading)
    <div class="text-center">
        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
        <span>Loading...</span>
    </div>
@else


<div class="modal-content">
    <div class="modal-header">
        <h4 class="modal-title text-capitalize" id="memberName">{{$data[1]}}</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <div class="modal-body">
        <section class="content">
            <div class="container-fluid">
                <div class="row">

                    <!-- /.col -->
                    <div class="col">
                    <div class="card">
                        <div class="card-header p-2">
                        <ul class="nav nav-pills">

                            <li class="nav-item"><a class="nav-link active" href="#info" data-toggle="tab">Member Information</a></li>
                            <li class="nav-item"><a class="nav-link" href="#groups" data-toggle="tab">Member Groups</a></li>
                            <li class="nav-item"><a class="nav-link" href="#delete" data-toggle="tab">Delete Account</a></li>
                        </ul>
                        </div><!-- /.card-header -->
                        <div class="card-body">
                            <div class="tab-content">

                                <div class="active tab-pane" id="info">
                                    <p>Update Member's information</p>
                                    <x-form-section submit="updateMemberInformation">
                                        <x-slot name="form">
                                            <div class="row">
                                                <!-- Name -->
                                                <div class="form-group col-sm-7">
                                                    <label for="name">Member Name <code>(full name)*</code></label>
                                                    <input type="text" wire:model.defer="name" required class="form-control form-control-border border-width-2  text-cyan text-capitalize" id="name" placeholder="Name" autocomplete="name">

                                                    <x-input-error for="name"/>
                                                </div>
                                                <!-- ID Number -->
                                                <div class="form-group col-sm-5">
                                                    <label for="IDNumber">ID. Number<code></code></label>
                                                    <input required type="text" required wire:model="member_Id" class="form-control form-control-border border-width-2  text-cyan text-capitalize" id="IDNumber" >
                                                    <x-input-error for="member_Id"/>
                                                    <span class="text-sm text-danger">{{ $error_id }}</span>
                                                </div>
                                                <!-- phone Number -->
                                                <div class="form-group col-sm-6">
                                                    <label for="phonenumber">Tell. Number <code></code></label>
                                                    <input required wire:model.live="phonenumber" type="text" class="form-control form-control-border border-width-2  text-cyan" id="phonenumber">

                                                    <x-input-error for="phonenumber"/>
                                                </div>
                                                <!-- Gender -->
                                                <div class="form-group col-sm-6">
                                                    <label for="gender">Gender</label>
                                                    <select required wire:model.defer="gender" class="custom-select form-control-border border-width-2 text-cyan" id="gender">
                                                        <option value="">select...</option>
                                                        @foreach ($genders as $option)
                                                            <option value="{{$option}}">{{$option}}</option>
                                                        @endforeach

                                                    </select>
                                                    <x-input-error for="gender"/>
                                                </div>
                                                <!--disability -->
                                                <div class="form-group col-sm-12">
                                                    <div class="custom-control custom-checkbox">
                                                        <input type="checkbox"  wire:model="disabled" class="custom-control-input" id="exampleCheck1">
                                                        <label class="custom-control-label" for="exampleCheck1">The Member is Disabled.</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </x-slot>
                                        <x-slot name="actions">
                                            @can('update member')
                                               <x-button wire:loading.attr="disabled" class="btn-primary" wire:target="updateMemberInformation">
                                                <span wire:loading wire:target="updateMemberInformation">{{ __('Saving...') }}</span>
                                                <span wire:loading.remove wire:target="updateMemberInformation"><i class="fas fa-save"></i> {{ __('Save') }} </span>
                                            </x-button>
                                            @endcan

                                            <x-button type="button" class="btn btn-default" data-dismiss="modal">Close</x-button>
                                        </x-slot>
                                    </x-form-section>
                                </div>
                                <!-- /.tab-pane -->

                                <div class="tab-pane" id="groups">
                                    <p>The Member Belongs to the following groups</p>
                                    <div class="card card-primary card-outline">
                                        <div class="card-body">
                                            <div class="table-responsive p-0" >
                                                <table class="table table-head-fixed table-bordered table-sm table-valign-middle">
                                                    <thead>
                                                    <tr class="text-center">
                                                        <th>#</th>
                                                        <th>Group Name</th>
                                                        <th>Constituency</th>
                                                        <th>Position</th>
                                                        <th>Loan Phase</th>
                                                        <th>Amount Issued</th>
                                                        <th>Balance</th>
                                                        <th>View</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody id="groupsDisplay">
                                                        @php
                                                            $i = 1;
                                                        @endphp
                                                        @if($memberGroupLoans!== null)
                                                            @foreach($memberGroupLoans as $member)
                                                            <tr>
                                                                <td>{{ $i++ }}</td>
                                                                <td>{{ $member->groupLoan->group->name }} </td>
                                                                <td>{{ $member->groupLoan->loanPhase->constituency->constituency_name }} </td>
                                                                <td>{{ $member->role }} </td>
                                                                <td>{{ $member->groupLoan->loanPhase->phase}} </td>
                                                                <td>{{ number_format($member->groupLoan->amount_issued) }} </td>
                                                                <td>{{ number_format($member->groupLoan->amount_issued - $member->groupLoan->amount_repaid)}} </td>
                                                                <td> <a href="{{route('const.groups.group', [
                                                                'county'=>Auth::user()->county->slug,
                                                                'constituency'=>$constituency->slug,
                                                                'group', 'group'=>$member->groupLoan->group->identifier
                                                                ])}}" class="btn btn-primary btn-sm">view</a> </td>
                                                            </tr>
                                                            @endforeach
                                                        @else
                                                            <p>NOT REGISTERED WITH ANY GROUP</p>
                                                        @endif
                                                    </tbody>
                                                </table>
                                            </div>
                                            <x-button type="button" class="btn btn-default float-right" data-dismiss="modal">Close</x-button>
                                        </div>
                                    </div>
                                </div>
                                <!-- /.tab-pane -->

                                <div class="tab-pane" id="delete">
                                    <p>Delete Member Record</p>
                                    <div class="card card-primary card-outline">
                                        <div class="card-body">
                                            <p class="text-red text-bold">Once the member is deleted, all of the member's data will be permanently deleted. Before deleting the member, please download any data or information that you wish to retain.</p>
                                            <div class="btn-group float-right " role="group">
                                                @can('delete member')
                                                    <x-button wire:loading.attr="disabled" class="btn-danger" wire:target="deleteAccount" wire:click="deleteAccount()">
                                                    <span wire:loading wire:target="deleteAccount">Deleting...</span>
                                                    <span wire:loading.remove wire:target="deleteAccount"><i class="fas fa-trash-alt"></i> Delete Account </span>
                                                </x-button>
                                                @endcan

                                                <x-button type="button" class="btn btn-default" data-dismiss="modal">Close</x-button>
                                            </div>
                                        </div>

                                    </div>

                                </div>
                            </div>
                            <!-- /.tab-content -->

                        </div><!-- /.card-body -->
                    </div>
                    <!-- /.card -->
                    </div>
                    <!-- /.col -->
                </div>
                <!-- /.row -->
            </div><!-- /.container-fluid -->
        </section>
        <!-- /.content -->
        </div>
    </div>
</div>
<!-- /.modal-content -->
@endif
@endif

</div>
