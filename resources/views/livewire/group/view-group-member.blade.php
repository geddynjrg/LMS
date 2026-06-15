<div>
    @if($loading)
        <div class="text-center py-4">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p class="mt-2">Loading member data...</p>
        </div>
    @elseif($member)
        <!-- Tabs Navigation -->
        <ul class="nav nav-tabs" id="memberTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link {{ $activeTab === 'edit' ? 'active' : '' }}"
                   wire:click="switchTab('edit')"
                   id="edit-tab" data-toggle="tab" href="#edit" role="tab">
                    <i class="fas fa-edit mr-1"></i>Edit Member
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $activeTab === 'groups' ? 'active' : '' }}"
                   wire:click="switchTab('groups')"
                   id="groups-tab" data-toggle="tab" href="#groups" role="tab">
                    <i class="fas fa-users mr-1"></i>Member Groups ({{ count($groups) }})
                </a>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content mt-3" id="memberTabsContent">
            <!-- Edit Tab -->
            <div class="tab-pane fade {{ $activeTab === 'edit' ? 'show active' : '' }}" id="edit" role="tabpanel">
                <form wire:submit.prevent="updateMember">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name">Full Name</label>
                                <input type="text" class="form-control" id="name"
                                       wire:model="name" required>
                                @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="member_Id">Member ID</label>
                                <input type="text" class="form-control" id="member_Id"
                                       wire:model="member_Id" readonly style="background-color: #f8f9fa;">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="number">Contact Number</label>
                                <input type="text" class="form-control" id="number"
                                       wire:model="number">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="gender">Gender</label>
                                <select class="form-control" id="gender" wire:model="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                                @error('gender') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="disabled"
                                   wire:model="disabled" value="1">
                            <label class="form-check-label" for="disabled">Person With Disability (PWD)</label>
                        </div>
                    </div>

                    <!-- Flash Messages -->
                    @if (session()->has('message'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('message') }}
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                    @endif

                    @if (session()->has('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                    @endif

                    <div class="form-group text-right">
                        {{-- <button type="button" class="btn btn-danger mr-2"
                                onclick="confirm('Are you sure you want to delete this member?') && $wire.deleteMember()">
                            <i class="fas fa-trash mr-1"></i>Delete Member
                        </button> --}}
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i>Update Member
                        </button>
                    </div>
                </form>
            </div>

            <!-- Groups Tab -->
            <div class="tab-pane fade {{ $activeTab === 'groups' ? 'show active' : '' }}" id="groups" role="tabpanel">
                @if(count($groups) > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-sm">
                            <thead class="thead-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Group Name</th>
                                    <th>Constituency</th>
                                    <th>Position</th>
                                    <th>Loan Phase</th>
                                    <th>Amount Issued</th>
                                    <th>Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($groups as $index => $group)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td class="text-capitalize">{{ $group['group_name'] }}</td>
                                        <td>{{ $group['constituency'] }}</td>
                                        <td class="text-capitalize">{{ $group['position'] }}</td>
                                        <td>{{ $group['loan_phase'] }}</td>
                                        <td>KSh {{ number_format($group['amount_issued']) }}</td>
                                        <td>KSh {{ number_format($group['balance']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-users fa-2x text-muted mb-3"></i>
                        <p class="text-muted">This member is not part of any groups.</p>
                    </div>
                @endif
            </div>
        </div>
    @else
        <div class="alert alert-danger text-center">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            Member not found or has been deleted.
        </div>
    @endif
</div>
