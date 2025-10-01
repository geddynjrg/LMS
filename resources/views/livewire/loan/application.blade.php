<section class="content">
    <div class="container-fluid">
        <h5 class="mt-4 mb-2">Group loan application </h5>

        <div class="card card-default">
            <div class="card-header">
                <h3 class="card-title">Ensure to save information after edit!</h3>
            </div>
            <div class="card-body">
                @if (auth()->user()->hasPermissionTo('create loan application'))
                    <div class="bs-stepper">
                        <div class="bs-stepper-header d-lg-flex d-none" role="tablist">
                            <div class="step {{$step == 'phaseStep' ?'active':''}}">
                                <button class="step-trigger" wire:click="$set('step', 'phaseStep')">
                                    <span class="bs-stepper-circle {{$icon['phase']=='fa-exclamation'?'bg-red':''}}">
                                        <i class="fas {{$icon['phase']}}"></i>
                                    </span>
                                    <span class="bs-stepper-label">Loan Phase </span>
                                </button>
                            </div>
                            <div class="line"></div>
                            <div class="step {{$step == 'dataStep'?'active':''}} ">
                                <button class="step-trigger" wire:click="$set('step', 'dataStep')">
                                    <span class="bs-stepper-circle {{$icon['data']=='fa-exclamation'?'bg-red':''}}">
                                        <i class="fas {{$icon['data']}}"></i>
                                    </span>
                                    <span class="bs-stepper-label">Group Data</span>
                                </button>
                            </div>
                            <div class="line"></div>
                            <div class="step {{$step == 'locationStep'?'active':''}} ">
                                <button class="step-trigger " wire:click="$set('step', 'locationStep')">
                                    <span class="bs-stepper-circle {{$icon['location']=='fa-exclamation'?'bg-red':''}}">
                                        <i class="fas {{$icon['location']}}"></i>
                                    </span>
                                    <span class="bs-stepper-label">Location</span>
                                </button>
                            </div>
                            <div class="line"></div>
                            <div class="step {{$step == 'membershipStep'?'active':''}} ">
                                <button class="step-trigger" wire:click="$set('step', 'membershipStep')">
                                    <span class="bs-stepper-circle {{$icon['membership']=='fa-exclamation'?'bg-red':''}}">
                                        <i class="fas {{$icon['membership']}}"></i>
                                    </span>
                                    <span class="bs-stepper-label">Members</span>
                                </button>
                            </div>
                            <div class="line"></div>
                            <div class="step {{$step == 'summary'?'active':''}} ">
                                <button class="step-trigger" wire:click="$set('step', 'summary')">
                                    <span class="bs-stepper-circle {{$icon['summary']=='fa-exclamation'?'bg-red':''}}">
                                        <i class="fas {{$icon['summary']}}"></i>
                                    </span>
                                    <span class="bs-stepper-label">Summary</span>
                                </button>
                            </div>
                        </div>
                        <hr>
                    </div>
                    <div class="tab-content bs-stepper-content">
                        @if ($step === 'phaseStep')
                        <div class="content tab-pane fade show active" role="tabpanel">
                            <form wire:submit.prevent="submitLoanPhase">
                                <div class="row">
                                    <div class="form-group col-sm-4">
                                        <label for="phase">Select Loan Phase<code>*</code></label>
                                        <select required wire:model.defer="phase" class="custom-select form-control-border text-cyan" id="phase">
                                        <option value="">Select...</option>

                                        @foreach ($phases as $phase)

                                            <option value="{{$phase->id}}">{{$phase->phase}}</option>
                                        @endforeach
                                        </select>
                                        @error('phase')
                                            <span class="text-sm text-red text-bold">*{{$message}}!</span>
                                        @enderror
                                    </div>
                                    <div class="form-group col-sm-4">
                                        <label for="Level">Loan Level<code>*</code></label>
                                        <select required wire:model.live="level" class="custom-select form-control-border text-cyan" id="level">
                                        <option value="">Select...</option>
                                        @foreach ($levels as $level)
                                            <option value="{{$level}}">{{$level}}</option>
                                        @endforeach
                                        </select>
                                        @error('level')
                                            <span class="text-sm text-red text-bold">*{{$message}}!</span>
                                        @enderror
                                    </div>
                                    @if ($isRefinancing)
                                    <div class="form-group col-sm-4">
                                        <label for="name">Group Name<code>*</code></label>
                                        <input autocomplete="off" wire:model.live="searchGroup" required ="text" class="form-control form-control-border border-width-2  text-cyan text-capitalize" id="name" placeholder="Enter Group Name">

                                        <div class="border border-top-0 mt-0 rounded" style="position: absolute;">
                                            @if ($na != null)
                                            <span class="m-3 text-danger text-bold text-sm accent-blue">{{$na}}</span><br>
                                            @endif

                                            @if ($groups)

                                            @foreach ($groups as $group)
                                                <button type="button" class="btn btn-dark btn-sm m-1 p-1 w-auto" wire:click="selectedGroup({{ $group->id }})">{{ $group->name }}</button>
                                            @endforeach


                                            @endif
                                        </div>
                                    </div>
                                    @endif

                                    <div class="form-group col-sm-4">
                                        <label for="applicationdate">Date of Application<code>*</code></label>
                                        <input required wire:model.defer="applicationDate" type="date" class="form-control form-control-border border-width-2 text-cyan" id="applicationdate">
                                        @error('applicationDate')
                                            <span class="text-sm text-red text-bold">*{{$message}}!</span>
                                        @enderror
                                    </div>
                                    <div class="form-group col-sm-4">
                                        <label for="amount">Amount Applying<code>*</code></label>
                                        <input required wire:model.defer="amount" type="number" min="1" class="form-control form-control-border border-width-2  text-cyan" id="amount" placeholder="Enter Amount Applying for.">
                                        @error('amount')
                                            <span class="text-sm text-red text-bold">*{{$message}}!</span>
                                        @enderror
                                    </div>

                                </div>
                                <div class="btn-group float-right m-2" role="group">
                                    <button type="submit" class="btn btn-primary mr-2" wire:loading.attr="disabled">
                                        <span wire:loading wire:target="submitLoanPhase">Saving...</span>
                                        <span wire:loading.remove wire:target="submitLoanPhase"><i class="fas fa-save"></i> Save </span>
                                    </button>
                                </div>
                            </form>
                        </div>
                        @elseif ($step === 'dataStep')
                        <div class="content tab-pane fade show active" role="tabpanel">
                            <form wire:submit.prevent="submitGroupData">
                                <div class="row">
                                    <div class="form-group col-sm-6">
                                        <label for="name">Group Name<code>*</code></label>
                                        <input wire:model="groupData.groupName" required ="text" class="form-control form-control-border border-width-2  text-cyan text-capitalize" id="name" placeholder="Enter Group Name">
                                        @error('groupData.groupName')
                                            <span class="text-sm text-red text-bold">*{{$message}}!</span>
                                        @enderror
                                    </div>
                                    <div class="col-sm-6 row">
                                        <div class="form-group col-sm-6">
                                            <label for="formed">Date Group Formed<code>*</code></label>
                                            <input required wire:model="groupData.dateFormed" type="date" class="form-control form-control-border border-width-2  text-cyan" id="formed">
                                            @error('groupData.dateFormed')
                                                <span class="text-sm text-red text-bold">*{{$message}}!</span>
                                            @enderror
                                        </div>
                                        <div class="form-group col-sm-6">
                                            <label for="registration">Date of Registration<code>*</code></label>
                                            <input required wire:model="groupData.dateRegistered" type="date" class="form-control form-control-border border-width-2  text-cyan" id="registration">
                                            @error('groupData.dateRegistered')
                                                <span class="text-sm text-red text-bold">*{{$message}}!</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="form-group col-sm-4">
                                        <label for="serial">Reg.No./Serial No.<code>*</code></label>
                                        <input required wire:model="groupData.registrationNumber" type="text" class="form-control form-control-border border-width-2  text-cyan" id="serial" placeholder="Enter Social Security Number">
                                        @error('groupData.registrationNumber')
                                            <span class="text-sm text-red text-bold">*{{$message}}!</span>
                                        @enderror
                                    </div>
                                    <div class="form-group col-sm-4">
                                        <label for="bank">Bank<code> - branch*</code></label>
                                        <input required wire:model="groupData.bankName" type="text" class="form-control form-control-border border-width-2 text-cyan text-capitalize" id="bank" placeholder="Equity, Voi Branch">
                                        @error('groupData.bankName')
                                            <span class="text-sm text-red text-bold">*{{$message}}!</span>
                                        @enderror
                                    </div>
                                    <div class="form-group col-sm-4">
                                        <label for="account">Bank Acc. No.<code>*</code></label>
                                        <input required wire:model="groupData.bankAccount" type="text" min="1" class="form-control form-control-border border-width-2  text-cyan" id="account" placeholder="Enter Bank Acc. No.">
                                        @error('groupData.bankAccount')
                                            <span class="text-sm text-red text-bold">*{{$message}}!</span>
                                        @enderror
                                    </div>
                                    <div class="form-group col-sm-4">
                                        <label for="category">Group Category<code>*</code></label>
                                        <select required wire:model="groupData.category" class="custom-select form-control-border border-width-2 text-cyan" id="category">
                                        <option value="">select category...</option>
                                        @foreach ($categories as $category => $name )
                                            <option value={{ $category }}>{{ $name }}</option>
                                        @endforeach
                                        </select>
                                        @error('groupData.category')
                                            <span class="text-sm text-red text-bold">*{{$message}}!</span>
                                        @enderror
                                    </div>
                                    <div class="form-group col-sm-4">
                                        <label for="activity">Group Activity<code>*</code></label>
                                        <select required wire:model="groupData.activity" class="custom-select form-control-border border-width-2 text-cyan" id="activity">
                                        <option value="">select activity...</option>
                                        @foreach ($activities as $activity => $name )
                                            <option value={{ $activity }}>{{ $name }}</option>
                                        @endforeach
                                        </select>
                                        @error('groupData.activity')
                                            <span class="text-sm text-red text-bold">*{{$message}}!</span>
                                        @enderror
                                    </div>
                                    <div class="form-group col-sm-4">
                                        <label for="activitydesc">Brief Activity of the Group.<code>*</code></label>
                                        <textarea required wire:model="groupData.description" id="activitydesc" class="form-control form-control-border border-width-2 text-cyan" rows="1" placeholder="describe activity briefly"></textarea>
                                        @error('groupData.description')
                                            <span class="text-sm text-red text-bold">*{{$message}}!</span>
                                        @enderror
                                    </div>

                                </div>
                                <div class="btn-group float-right m-2" role="group">
                                    <button type="submit" class="btn btn-primary mr-2" wire:loading.attr="disabled">
                                        <span wire:loading wire:target="submitGroupData">Saving...</span>
                                        <span wire:loading.remove wire:target="submitGroupData"><i class="fas fa-save"></i> Save </span>
                                    </button>
                                </div>
                            </form>
                        </div>
                        @elseif ($step === 'locationStep')
                        <div class="content tab-pane fade show active" role="tabpanel">
                            <form wire:submit.prevent="submitGroupLocation">
                                <div class="row">
                                    <div class="form-group col-sm-4">
                                        <label for="county">County<code></code></label>
                                        <input type="text" disabled class="form-control form-control-border border-width-2  text-cyan text-capitalize" id="county" value="{{Auth::user()->county->county_name}}">

                                    </div>
                                    <div class="form-group col-sm-4">
                                        <label for="constituency">Sub-County/Constituency<code></code></label>
                                        <input type="text" disabled class="form-control form-control-border border-width-2  text-cyan text-capitalize" id="constituency" value="{{$constituency->constituency_name}}">
                                    </div>
                                    <div class="form-group col-sm-4">
                                        <label for="box">P.O Box<code>*</code></label>
                                        <input required type="number" wire:model="groupLocation.boxNumber" class="form-control form-control-border border-width-2  text-cyan " id="box" placeholder="Group P.O. Box">
                                        @error('groupLocation.boxNumber')
                                            <span class="text-sm text-red text-bold">*{{$message}}!</span>
                                        @enderror
                                    </div>
                                    <div class="form-group col-sm-4">
                                        <label for="ward">Ward<code>*</code></label>
                                        <select required wire:model="groupLocation.ward" class="custom-select form-control-border border-width-2 text-cyan" id="ward">
                                        <option value="">select ward...</option>
                                        @foreach ($wards as $ward)
                                            <option value={{ $ward->id }}>{{ $ward->ward_name }}</option>
                                        @endforeach
                                        </select>
                                        @error('groupLocation.ward')
                                            <span class="text-sm text-red text-bold">*{{$message}}!</span>
                                        @enderror
                                    </div>
                                    <div class="form-group col-sm-4">
                                        <label for="sublocation">Sub-Location<code>*</code></label>
                                        <input type="text" wire:model="groupLocation.sublocation" class="form-control form-control-border border-width-2  text-cyan text-capitalize" id="sublocation" placeholder="Enter Sub-Location">
                                        @error('groupLocation.sublocation')
                                            <span class="text-sm text-red text-bold">*{{$message}}!</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="btn-group float-right m-2" role="group">
                                    <button type="submit" class="btn btn-primary mr-2" wire:loading.attr="disabled">
                                        <span wire:loading wire:target="submitGroupLocation">Saving...</span>
                                        <span wire:loading.remove wire:target="submitGroupLocation"><i class="fas fa-save"></i> Save </span>
                                    </button>
                                </div>
                            </form>
                        </div>
                        @elseif ($step === 'membershipStep')
                        <div class="content tab-pane fade show active" role="tabpanel">
                            <div class="row">
                                <div class="col-sm-4 ">
                                    <form wire:submit.prevent="submitMember">
                                        <div class="row">
                                            <div class="form-group col-sm-12">
                                                <label for="name">Member Name <code>(full name)*</code></label>
                                                <input required type="text" wire:model.defer="groupMember.name" required class="form-control form-control-border border-width-2  text-cyan text-capitalize" id="name" placeholder="Name">
                                                @error('groupMember.name')
                                                    <span class="text-sm text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="form-group col-sm-6">
                                                <label for="IDNumber">ID. Number<code></code></label>
                                                <input required type="text" required wire:model="groupMember.member_Id" class="form-control form-control-border border-width-2  text-cyan text-capitalize" id="IDNumber" >
                                                @error('groupMember.member_Id')
                                                    <span class="text-sm text-danger">{{ $message }}</span>
                                                @enderror
                                                <span class="text-sm text-danger">{{ $error_id }}</span>
                                            </div>
                                            <div class="form-group col-sm-6">
                                                <label for="role">Member Role <code></code></label>
                                                <select required wire:model.defer="groupMember.role" class="custom-select form-control-border border-width-2 text-cyan" id="role">
                                                    <option value="">select...</option>
                                                    @foreach ($roles as $position )
                                                        <option value="{{$position}}">{{$position}}</option>
                                                    @endforeach
                                                </select>
                                                @error('groupMember.role')
                                                    <span class="text-sm text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="form-group col-sm-6">
                                                <label for="phonenumber">Tell. Number <code></code></label>
                                                <input required wire:model.defer="groupMember.phonenumber" type="text" class="form-control form-control-border border-width-2  text-cyan" id="phonenumber">
                                                @error('groupMember.phonenumber')
                                                    <span class="text-sm text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="form-group col-sm-6">
                                                <label for="gender">Gender</label>
                                                <select required wire:model.defer="groupMember.gender" class="custom-select form-control-border border-width-2 text-cyan" id="gender">
                                                    <option value="">select...</option>
                                                    @foreach ($genders as $option)
                                                        <option value="{{$option}}">{{$option}}</option>
                                                    @endforeach

                                                </select>
                                                @error('groupMember.gender')
                                                    <span class="text-sm text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="form-group col-sm-12">
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" wire:model="groupMember.disabled" class="custom-control-input" id="isDisabled">
                                                    <label class="custom-control-label" for="isDisabled">The Member is Disabled.</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="btn-group float-right m-2" role="group">
                                            <button type="submit" class="btn btn-primary mr-2" wire:loading.attr="disabled">
                                                <span wire:loading wire:target="submitMember">Saving...</span>
                                                <span wire:loading.remove wire:target="submitMember"><i class="fas fa-save"></i> Save </span>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                                <div class="col-sm-8">
                                    <div class="table-responsive p-0" style="height: 350px;">
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
                                                <td>{{$member['name']}}</td>
                                                <td>{{$member['role']}}</td>
                                                <td>{{$member['gender']}}</td>
                                                <td>{{$member['member_Id']}}</td>
                                                <td>{{$member['phonenumber']}}</td>
                                                <td class="text-center">{{$member['disabled']?'Yes':'No';}}</td>
                                                <td class="text-center">
                                                    <button type="button" wire:click="selectEditMember('{{$member['member_Id']}}')" class="btn btn-sm"><i class="fas fa-edit text-primary"></i></button>
                                                    <button type="button" wire:click="removeMember('{{$member['member_Id']}}')" class="btn btn-sm"><i  class="fas fa-trash-alt text-danger"></i></button>
                                                </td>
                                            </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="btn-group float-right m-3" role="group">
                                        <button type="button" wire:click="continue" class="btn btn-primary mr-2">
                                            <span>Continue <i class="fas fa-fast-forward"></i></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @elseif ($step === 'summary')
                        <div class="content tab-pane fade show active" role="tabpanel">
                            <!-- Group card Info -->
                            <div class="card card-primary">
                                <div class="card-header">
                                <h3 class="card-title">Group Data</h3>
                                </div>
                                <!-- /.card-header -->
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <strong><i class="fas fa-layer-group"></i> Group Name<code>*</code></strong>

                                            <p class="text-muted">
                                                {{$groupData['groupName']}}
                                            </p>
                                            <hr>
                                            <strong><i class="fas fa-list-ul"></i> Loan Phase<code>*</code></strong>

                                            <p class="text-muted">
                                                {{ $ph != null? $ph->phase : ''}}
                                            </p>

                                            <hr>
                                            <strong><i class="fas fa-clock mr-1"></i> Date Formed<code>*</code></strong>

                                            <p class="text-muted">
                                                {{$groupData['dateFormed']}}
                                            </p>

                                            <hr>

                                            <strong><i class="fas fa-clock mr-1"></i> Date Registered<code>*</code></strong>

                                            <p class="text-muted">
                                                {{$groupData['dateRegistered']}}
                                            </p>
                                        </div>
                                        <div class="col-md-4">
                                            <strong><i class="far fa-copyright mr-1"></i> Registration Number<code>*</code></strong>
                                            <p class="text-muted">
                                                {{$groupData['registrationNumber']}}
                                            </p>

                                            <hr>
                                            <strong><i class="fas fa-coins"></i> Amount Applied<code>*</code> </strong>

                                            <p class="text-muted">KSH {{$amount != null? number_format($amount) : ''}}</p>

                                            <hr>

                                            <strong><i class="fas fa-university mr-1"></i> Bank<code>*</code> </strong>

                                            <p class="text-muted">Name: {{$groupData['bankName']}}</p>
                                            <p class="text-muted">Acc: {{$groupData['bankAccount']}}</p>
                                            <hr>
                                            <strong><i class="fas fa-users-cog mr-1"></i> Category<code>*</code> </strong>

                                            <p class="text-muted">{{$groupData['category'] != null? $categories[$groupData['category']] : ''}}</p>

                                        </div>
                                        <div class="col-md-4">
                                            <strong><i class="fas fa-map-marker-alt mr-1"></i> Location<code>*</code> </strong>

                                            <p class="text-muted">Ward: {{$groupLocation['ward']}}</p>
                                            <p class="text-muted">Sub-location: {{$groupLocation['sublocation']}}</p>

                                            <hr>

                                            <strong><i class="fas fa-arrows-alt mr-1"></i> Sector<code>*</code> </strong>

                                            <p class="text-muted">{{$groupData['activity'] != null? $activities[$groupData['activity']] : ''}}</p>

                                            <hr>

                                            <strong><i class="fas fa-audio-description mr-1"></i> Description<code>*</code> </strong>

                                            <p class="text-muted">{{$groupData['description']}}</p>

                                        </div>
                                    </div>
                                </div>
                                <!-- /.card-body -->
                            </div>
                            <!-- /.card -->
                            <!-- card -->
                            <div class="card card-success">
                                <div class="card-header p-2">
                                    <h3 class="card-title">Group Members</h3>
                                </div>
                                <div class="card-body">
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
                                                <td>{{$member['name']}}</td>
                                                <td>{{$member['role']}}</td>
                                                <td>{{$member['gender']}}</td>
                                                <td>{{$member['member_Id']}}</td>
                                                <td>{{$member['phonenumber']}}</td>
                                                <td class="text-center">{{$member['disabled']?'Yes':'No';}}</td>
                                                <td class="text-center">
                                                    <button type="button" wire:click="selectEditMember('{{$member['member_Id']}}')" class="btn btn-sm"><i class="fas fa-edit text-primary"></i></button>
                                                    <button type="button" wire:click="removeMember('{{$member['member_Id']}}')" class="btn btn-sm"><i  class="fas fa-trash-alt text-danger"></i></button>
                                                </td>
                                            </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="btn-group float-right m-3" role="group">
                                @can('create loan application')
                                    <button type="button" class="btn btn-primary mr-2" wire:click="saveGroupData" wire:loading.attr="disabled">
                                        <span wire:loading wire:target="saveGroupData">Saving...</span>
                                        <span wire:loading.remove wire:target="saveGroupData"><i class="fas fa-save"></i> Save Data</span>
                                    </button>
                                @endcan
                                
                            </div>
                            <!-- /.card -->
                        </div>
                        @endif
                    </div>
                @else
                    <span class="text-danger text-center">You are not authorised</span>
                @endif
                
            </div>
        </div>

    </div>

    <!--repay Modal -->
    <div class="modal fade" id="modal-defaulter">
        <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
            <h4 class="modal-title" id="member_modal_title">Member Groups</h4>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            </div>
            <div class="modal-body">
                <p>The member ID is registered with the following group(s):&hellip;</p>

                <div class="table-responsive p-0" >
                    <table class="table table-head-fixed table-bordered table-sm">
                        <thead>
                        <tr class="text-center">
                            <th>#</th>
                            <th>Group Name</th>
                            <th>Constituency</th>
                            <th>Position</th>
                            <th>Loan Phase</th>
                            <th>Amount Issued</th>
                            <th>Balance</th>
                        </tr>
                        </thead>
                        <tbody id="groupsDisplay">

                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" wire:click="addDefaulter()" class="btn btn-primary">Add Member</button>
            </div>
        </div>
        <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>
    <!-- /.Repay modal -->

    <!--defaulter members Modal -->
    <div class="modal fade" id="modal-defaulter-members">
        <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
            <h4 class="modal-title" id="member_modal_title">Member In Default Groups</h4>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            </div>
            <div class="modal-body">
                <p>These members are registered with the following group(s):&hellip;</p>

                <div id="defaultersdata"></div>

            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" wire:click="applyDefaulter()" class="btn btn-primary">Continue with Application</button>
            </div>
        </div>
        <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>
    <!-- /.defaulter members modal -->
</section>

@push('styles')
    <link rel="stylesheet" href="{{asset('plugins/bs-stepper/css/bs-stepper.min.css')}}">
    <link rel="stylesheet" href="{{asset('plugins/toastr/toastr.min.css')}}">
@endpush

@push('scripts')
    <script src="{{asset('plugins/toastr/toastr.min.js')}}"></script>
    <script>
        document.addEventListener('memberloans', function (event) {
            $("#modal-defaulter").modal('show');
            var data = event.detail.memberloans;

            $('#modal-defaulter').on('shown.bs.modal', function (e) {
                const groupsDisplay = document.getElementById('groupsDisplay');
                let htmlContent = '';
                let i = 1;
                data.forEach(item => {
                htmlContent += `
                <tr >
                    <td>${i++}</td>
                    <td>${item.group}</td>
                    <td>${item.constituency}</td>
                    <td>${item.role}</td>
                    <td>${item.phase}</td>
                    <td class='text-center'>${item.issued}</td>
                    <td class='text-center'>${item.balance}</td>
                </tr>
                `;
                });
                document.getElementById('member_modal_title').innerText = `${data[0]['member']} - ID: ${data[0]['member_id']}`;
                groupsDisplay.innerHTML = htmlContent;
            });



        });

        document.addEventListener('member_added', function (event) {
            toastr.success('New Member Added Successfully');
            $("#modal-defaulter").addClass('fade').modal('hide');
        });

        document.addEventListener('member_exists', function (event) {
            toastr.error('Member Exists in the list!!');
        });

        document.addEventListener('member_removed', function (event) {
            toastr.success('Member Removed Successfully');
        });

        document.addEventListener('editUsedId', function (event) {
            toastr.error('Member ID is already registered!!');
        });
        document.addEventListener('memberEdited', function (event) {
            toastr.success('Member Edited Successfully');
        });
        document.addEventListener('dataNotSaved', function (event) {
            toastr.error(event.detail.message);
        });

        document.addEventListener('application', function (event) {

            switch (event.detail.status) {
                case 'invalid':
                    toastr.error('You have an invalid group. Please redo the application!!');
                    break;
                case 'success':
                    toastr.success('Loan Applied Successfully');
                    $("#modal-defaulter-members").addClass('fade').modal('hide');
                    break;
                default:
                    break;
            }
        });


        document.addEventListener('defaulterMembers', function (event) {
            $("#modal-defaulter-members").modal('show');
            var data = event.detail.defaultermembers;


            $('#modal-defaulter-members').on('shown.bs.modal', function (e) {
                const membersDisplay = document.getElementById('defaultersdata');
                let htmlContent = '';
                let i = 1;
                let j = 1;
                data.forEach(membersLoans => {
                    htmlContent += `
                    <h5>${i++}. ${membersLoans[0].member['name']} - ID: ${membersLoans[0].member.member_Id}</h5>
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
                                ${membersLoans.map((groupMember, index) => `
                                    <tr >
                                        <td>${index + 1}</td>
                                        <td>${groupMember.group_loan.group.name}</td>
                                        <td>${groupMember.group_loan.loan_phase.constituency.constituency_name}</td>
                                        <td>${groupMember.role}</td>
                                        <td>${groupMember.group_loan.loan_phase.phase}</td>
                                        <td class='text-center'>${groupMember.group_loan.amount_issued}</td>
                                        <td class='text-center'>${groupMember.group_loan.amount_repaid}</td>
                                    </tr>`
                                ).join('')}
                            </tbody>
                        </table>
                    </div>
                    `;
                });
                membersDisplay.innerHTML = htmlContent;
            });

            console.log(data)



        });

    </script>
@endpush
