<x-action-section>
    <x-slot name="content">
        <div class="text-sm text-gray">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
        </div>

        <div class="mt-3">
            <x-danger-button data-toggle="modal" data-target="#modal-delete-acount" class="btn-danger float-right" wire:click="confirmUserDeletion" wire:loading.attr="disabled">
                {{ __('Delete Account') }}
            </x-danger-button>
        </div>

        <!-- Modal to delete account-->
        <div wire:ignore.self  class="modal fade" id="modal-delete-acount">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="account">Delete Account</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="text-lg font-medium text-gray">
                            Delete Account
                        </div>
                        <div class="mt-4 text-sm text-gray">
                            <p>Are you sure you want to delete your account? Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.</p>
                        </div>
                        <div class="mt-1 form-group row">
                            <label for="password" class="col-sm-3 col-form-label">New Password:</label>
                            <div class="col-sm-9">
                                <input type="password" class="form-control"
                                    autocomplete="current-password"
                                    placeholder="{{ __('Password') }}"
                                    x-ref="password"
                                    wire:model="password"
                                    wire:keydown.enter="deleteUser">
                                <x-input-error for="password" class="mt-2" />
                            </div>
                        </div>
                        <div class="float-right text-end">
                            {{-- <x-button class="ms-3 btn-danger"
                                wire:click="deleteUser"
                                wire:loading.attr="disabled">
                                {{ __('Delete Account') }}
                            </x-button> --}}
                        </div>
                    </div>
                </div>
                <!-- /.modal-content -->
            </div>
            <!-- /.modal-dialog -->
        </div>
        <!-- / delete acount modal -->

    </x-slot>
</x-action-section>
