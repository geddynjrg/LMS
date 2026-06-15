<div class="card card-default">
    <div class="card-body">
        @if ($loading)
            <div class="text-center">
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                <span>Loading...</span>
            </div>

        @else


        @if ($groupLoan != null)
        <form wire:submit.prevent="submitData">
            <div class="row">
                <div class="form-group col-sm-6">
                    <label for="name">Group Name<code>*</code></label>
                    <input wire:model="groupData.groupName" required ="text" class="form-control form-control-border border-width-2  text-cyan text-capitalize" id="name" placeholder="Enter Group Name">
                    @error('groupData.groupName')
                        <span class="text-sm text-red text-bold">{{$message}}</span>
                    @enderror
                </div>
                <div class="col-sm-6 row">
                    <div class="form-group col-sm-6">
                        <label for="formed">Date Group Formed<code>*</code></label>
                        <input required wire:model="groupData.dateFormed" type="date" class="form-control form-control-border border-width-2  text-cyan" id="formed">
                        @error('groupData.dateFormed')
                            <span class="text-sm text-red text-bold">{{$message}}</span>
                        @enderror
                    </div>
                    <div class="form-group col-sm-6">
                        <label for="registration">Date of Registration<code>*</code></label>
                        <input required wire:model="groupData.dateRegistered" type="date" class="form-control form-control-border border-width-2  text-cyan" id="registration">
                        @error('groupData.dateRegistered')
                            <span class="text-sm text-red text-bold">{{$message}}</span>
                        @enderror
                    </div>
                </div>
                <div class="form-group col-sm-4">
                    <label for="serial">Reg.No./Serial No.<code>*</code></label>
                    <input required wire:model="groupData.registrationNumber" type="text" class="form-control form-control-border border-width-2  text-cyan" id="serial" placeholder="Enter Social Security Number">
                    @error('groupData.registrationNumber')
                        <span class="text-sm text-red text-bold">{{$message}}</span>
                    @enderror
                </div>
                <div class="form-group col-sm-4">
                    <label for="bank">Bank<code> - branch*</code></label>
                    <input required wire:model="groupData.bankName" type="text" class="form-control form-control-border border-width-2 text-cyan text-capitalize" id="bank" placeholder="Equity, Voi Branch">
                    @error('groupData.bankName')
                        <span class="text-sm text-red text-bold">{{$message}}</span>
                    @enderror
                </div>
                <div class="form-group col-sm-4">
                    <label for="account">Bank Acc. No.<code>*</code></label>
                    <input required wire:model="groupData.bankAccount" type="text" min="1" class="form-control form-control-border border-width-2  text-cyan" id="account" placeholder="Enter Bank Acc. No.">
                    @error('groupData.bankAccount')
                        <span class="text-sm text-red text-bold">{{$message}}</span>
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
                        <span class="text-sm text-red text-bold">{{$message}}</span>
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
                        <span class="text-sm text-red text-bold">{{$message}}</span>
                    @enderror
                </div>
                <div class="form-group col-sm-4">
                    <label for="activitydesc">Brief Activity of the Group.<code>*</code></label>
                    <textarea required wire:model="groupData.description" id="activitydesc" class="form-control form-control-border border-width-2 text-cyan" rows="1" placeholder="describe activity briefly"></textarea>
                    @error('groupData.description')
                        <span class="text-sm text-red text-bold">{{$message}}</span>
                    @enderror
                </div>

            </div>
            <div class="row">
                <div class="form-group {{$groupLoan['loan_status'] == 'financed'?'col-sm-6':'col-sm-4'}}">
                    <label for="phase">Select Loan Phase <code>*</code></label>
                    <select required wire:model.defer="phase" class="custom-select form-control-border text-cyan" id="phase">
                        <option value="">Select Phase</option>
                        @foreach ($phases as $phase)
                            <option value={{$phase->id}}>{{$phase->phase}}</option>
                        @endforeach
                    </select>
                    @error('phase')
                        <span class="text-sm text-red text-bold">{{$message}}</span>
                    @enderror
                </div>
                <div class="form-group  {{$groupLoan['loan_status'] == 'financed'?'col-sm-6':'col-sm-4'}}">
                    <label for="applicationdate">Date of Application<code>*</code></label>
                    <input required wire:model.defer="applicationDate" type="date" class="form-control form-control-border border-width-2 text-cyan" id="applicationdate">
                    @error('applicationDate')
                        <span class="text-sm text-red text-bold">{{$message}}</span>
                    @enderror
                </div>
                @if ($groupLoan['loan_status'] !== 'financed')
                <div class="form-group col-sm-4">
                    <label for="amount_applied">Amount Applying<code>*</code></label>
                    <input required wire:model.defer="amount_applied" type="number" min="1" class="form-control form-control-border border-width-2  text-cyan" id="amount_applied" placeholder="Enter Amount Applying for.">
                    @error('amount_applied')
                        <span class="text-sm text-red text-bold">{{$message}}</span>
                    @enderror
                </div>
                @endif

            </div>
            @if ($groupLoan['loan_status'] == 'approved' || $this->groupLoan['loan_status'] == 'financed')
            <div class="row">
                <div class="form-group {{$groupLoan['loan_status'] == 'financed'?'col-sm-6':'col-sm-4'}}">
                    <label for="code">Group code <code>*</code></label>
                    <input required type="text" wire:model.defer="code"  class="form-control form-control-border border-width-2  text-cyan text-capitalize" id="code" placeholder="(254)">
                    @error('code')
                        <span class="text-sm text-danger">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group {{$groupLoan['loan_status'] == 'financed'?'col-sm-6':'col-sm-4'}}">
                    <label for="identifier">Group Identifier<code>*</code></label>
                    <input required type="text" wire:model="identifier" class="form-control form-control-border border-width-2  text-cyan text-capitalize" id="identifier" placeholder="(MZA254)">
                    @error('identifier')
                        <span class="text-sm text-danger">{{ $message }}</span>
                    @enderror
                </div>
                @if ($groupLoan['loan_status'] == 'approved')
                    <div class="form-group col-sm-4">
                        <label for="amount">Amount Approved <code>*</code></label>
                        <input required type="number" wire:model.defer="amount_approved" class="form-control form-control-border border-width-2  text-cyan text-capitalize" id="amount" placeholder="(100,000)">
                        @error('amount_approved')
                            <span class="text-sm text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                @endif
            </div>
            @endif


            <div class="row">
                <div class="form-group col-sm-4">
                    <label for="box">P.O Box<code>*</code></label>
                    <input required type="number" wire:model="groupLocation.boxNumber" class="form-control form-control-border border-width-2  text-cyan " id="box" placeholder="Group P.O. Box">
                    @error('groupLocation.boxNumber')
                        <span class="text-sm text-red text-bold">{{$message}}</span>
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
                        <span class="text-sm text-red text-bold">{{$message}}</span>
                    @enderror
                </div>
                <div class="form-group col-sm-4">
                    <label for="sublocation">Sub-Location<code>*</code></label>
                    <input type="text" wire:model="groupLocation.sublocation" class="form-control form-control-border border-width-2  text-cyan text-capitalize" id="sublocation" placeholder="Enter Sub-Location">
                    @error('groupLocation.sublocation')
                        <span class="text-sm text-red text-bold">{{$message}}</span>
                    @enderror
                </div>
            </div>
            <div class="btn-group float-right m-2" role="group">
                <button type="submit" class="btn btn-primary mr-2" wire:loading.attr="disabled">
                    <span wire:loading wire:target="submitData">Editing...</span>
                    <span wire:loading.remove wire:target="submitData"><i class="far fa-edit"></i> Save </span>
                </button>
            </div>
        </form>
        @endif
    @endif
    </div>
</div>
