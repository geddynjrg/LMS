<x-action-section>
    <x-slot name="content">
        <div class="text-sm text-gray">
            {{ __('If necessary, you may log out of all of your other browser sessions across all of your devices. Some of your recent sessions are listed below; however, this list may not be exhaustive. If you feel your account has been compromised, you should also update your password.') }}
        </div>

        @if (count($this->sessions) > 0)
            <div class="mt-3 ">
                <!-- Other Browser Sessions -->
                @foreach ($this->sessions as $session)
                    <div class="flex items-center">
                        <div>
                            @if ($session->agent->isDesktop())
                                <i class="fas fa-desktop"></i>
                            @else
                                <i class="fas fa-mobile-alt"></i>
                            @endif
                        </div>

                        <div class="ms-3">
                            <div class="text-sm text-gray">
                                {{ $session->agent->platform() ? $session->agent->platform() : __('Unknown') }} - {{ $session->agent->browser() ? $session->agent->browser() : __('Unknown') }}
                            </div>

                            <div>
                                <div class="text-sm text-gray">
                                    {{ $session->ip_address }},

                                    @if ($session->is_current_device)
                                        <span class="text-green text-bold">{{ __('This device') }}</span>
                                    @else
                                        {{ __('Last active') }} {{ $session->last_active }}
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="flex items-center float-right">
            <x-button data-toggle="modal" data-target="#modal-logout" class="btn-danger" wire:loading.attr="disabled">
                {{ __('Log Out Other Browser Sessions') }}
            </x-button>
        </div>

        <!-- modal to show logout -->
        <div wire:ignore.self  class="modal fade" id="modal-logout">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="logout">Log-Out Other Devices</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="text-lg font-medium text-gray">
                            Log Out Other Browser Sessions
                        </div>
                        <div class="mt-4 text-sm text-gray">
                            <p>Please enter your password to confirm you would like to log out of your other browser sessions across all of your devices.'</p>
                        </div>
                        <div class="mt-1 form-group row">
                            <label for="password" class="col-sm-3 col-form-label">New Password:</label>
                            <div class="col-sm-9">
                                <input type="password" class="form-control" autocomplete="current-password"
                                        placeholder="{{ __('Password') }}"
                                        x-ref="password"
                                        wire:model="password"
                                        wire:keydown.enter="logoutOtherBrowserSessions">
                                <x-input-error for="password" class="mt-2" />
                            </div>
                        </div>
                        <div class="float-right text-end">
                            <x-button class="ms-3 btn-danger"
                                wire:click="logoutOtherBrowserSessions"
                                wire:loading.attr="disabled">
                                {{ __('Log Out Other Browser Sessions') }}
                            </x-button>
                        </div>
                    </div>
                </div>
                <!-- /.modal-content -->
            </div>
            <!-- /.modal-dialog -->
        </div>
        <!-- /.logout modal -->
    </x-slot>
</x-action-section>
