<div class="card card-default">
    <div class="card-body">
        @if ($loading)
            <div class="text-center">
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                <span>Loading...</span>
            </div>
        @else
        @can('delete member')
        @if($member != null)

        <p class="text-bold text-muted"><strong class="text-body"> Name <code>:</code> </strong>{{$member->member->name}}</p>
        <hr>

        <p class="text-bold text-muted"><strong class="text-body"> ID Number <code>:</code> </strong> {{$member->member->member_Id}}</p>
        <hr>

        <p class="text-bold text-muted"><strong class="text-body"> Phone Number <code>:</code> </strong>{{$member->member->number}}</p>
        <hr>

        <p class="text-bold text-muted"><strong class="text-body"> Position In Group <code>:</code> </strong> {{$member->role}}</p>
        <hr>

        <p class="text-bold text-muted"><strong class="text-body"> Gender <code>:</code> </strong> {{$member->member->gender}}</p>
        <hr>
        <div class="btn-group float-right m-2" role="group">
            <button type="button" class="btn btn-primary mr-2" wire:loading.attr="disabled" wire:click="deleteMember()">
                <span wire:loading >Deleting...</span>
                <span wire:loading.remove ><i class="fas fa-user-times"></i> Confirm Delete </span>
            </button>
        </div>
        @endif
        @endcan
        @endif
    </div>
</div>
