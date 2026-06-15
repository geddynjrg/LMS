<x-form-section submit="updatePassword">
    <x-slot name="form">

        <!-- Current Password -->
        <div class="form-group row">
            <label for="current_password" class="col-sm-2 col-form-label">Current Password:</label>
            <div class="col-sm-10">
              <input id="current_password" type="password" class="form-control" wire:model="state.current_password" autocomplete="current-password" >
              <x-input-error for="current_password" class="mt-2" />
            </div>
        </div>

        <!--Password -->
        <div class="form-group row">
            <label for="password" class="col-sm-2 col-form-label">New Password:</label>
            <div class="col-sm-10">
              <input id="password" type="password" class="form-control" wire:model="state.password" autocomplete="new-password" >
              <x-input-error for="password" class="mt-2" />
            </div>
        </div>

        <!--Password Confirmation-->
        <div class="form-group row">
            <label for="password_confirmation" class="col-sm-2 col-form-label">Confirm Password:</label>
            <div class="col-sm-10">
              <input id="password_confirmation" type="password" class="form-control" wire:model="state.password_confirmation" autocomplete="new-password" >
              <x-input-error for="password_confirmation" class="mt-2" />
            </div>
        </div>
    </x-slot>

    <x-slot name="actions">
        <x-button wire:loading.attr="disabled" class="btn-primary" wire:target="password">
            <span wire:loading wire:target="password">{{ __('Saving...') }}</span>
            <span wire:loading.remove wire:target="password"><i class="fas fa-save"></i> {{ __('Save') }} </span>  
        </x-button>
    </x-slot>
</x-form-section>
