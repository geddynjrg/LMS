@props([
    'model',
    'height' => '48px',
    'maxSize' => 2,      // MB
    'accept' => '.pdf,.doc,.docx'
])

@php
    $fileValue = $this->getPropertyValue($model);
@endphp

<div x-data="{
        errorMessage: '',
        uploading: false,
        progress: 0,
        localFileName: '',

        clearFile() {
            this.errorMessage = '';
            this.progress = 0;
            this.uploading = false;
            this.localFileName = '';
            if (document.getElementById('{{ $model }}')) {
                document.getElementById('{{ $model }}').value = '';
            }
        },

        handleFile(e) {
            const file = e.target.files[0];
            if (!file) return;

            this.localFileName = file.name;
            const maxSizeMB = {{ $maxSize }};
            const maxBytes = maxSizeMB * 1024 * 1024;

            this.errorMessage = '';

            if (file.size > maxBytes) {
                this.errorMessage = `This file is too large (${(file.size / (1024 * 1024)).toFixed(2)}MB). Maximum allowed is ${maxSizeMB}MB.`;
                this.uploading = false;
                e.target.value = '';
                return false;
            }
            return true;
        }
    }"
    x-on:livewire-upload-start="errorMessage = ''; uploading = true; progress = 0;"
    x-on:livewire-upload-finish="uploading = false; progress = 100;"
    x-on:livewire-upload-error="
        uploading = false;
        if (!errorMessage) { errorMessage = 'Upload failed. Check your connection or file size.'; }
    "
    x-on:livewire-upload-progress="progress = $event.detail.progress"
    class="position-relative w-100">

    <div class="position-relative" style="height: {{ $height }};">
        @if(!$fileValue)
            <input type="file"
                wire:model="{{ $model }}"
                id="{{ $model }}"
                @change.stop="if(!handleFile($event)) return;"
                class="position-absolute w-100 h-100"
                style="opacity: 0; z-index: 5; cursor: pointer; top: 0; left: 0;"
                accept="{{ $accept }}">
        @endif
        <div {{ $attributes->merge(['class' => 'form-control d-flex align-items-center shadow-none proxy-box']) }}
            {{-- We use a simple boolean check for the background color --}}
            :class="{
                'border-danger bg-danger-subtle': errorMessage,
                'border-success bg-success-subtle': !errorMessage && (localFileName && !uploading && !errorMessage) || @json($fileValue != null)
            }"
            style="height: {{ $height }}; border-radius: 12px; padding: 0 15px; position: absolute; top: 0; left: 0; width: 100%; z-index: 1;">

            {{-- 1. STATE: FILE IS SELECTED (Local or Server) --}}
            <template x-if="localFileName || @json($fileValue != null)">
                <div class="d-flex align-items-center w-100">
                    <i class="fas fa-file-alt fa-lg mr-2" :class="errorMessage ? 'text-danger' : 'text-primary'"></i>

                    <div class="d-flex flex-column overflow-hidden">
                        <span class="text-dark text-xs text-truncate" style="max-width: 180px;"
                            x-text="localFileName ? localFileName : '{{ $fileValue ? $this->getFileMetadata($fileValue)['name'] : '' }}'">
                        </span>

                        <small class="x-small" :class="errorMessage ? 'text-danger' : 'text-muted'">
                            <template x-if="!errorMessage && errorMessage.length == 0">
                                <span>Ready to upload</span>
                            </template>
                        </small>
                    </div>

                    {{-- Reset Button --}}
                    <button type="button"
                            wire:click="$set('{{ $model }}', null)"
                            @click="clearFile()"
                            class="btn btn-sm ml-auto p-0 text-success"
                            style="z-index: 10;">
                        <i class="fas fa-times-circle fa-lg"></i>
                    </button>
                </div>
            </template>

            {{-- 2. STATE: EMPTY (Nothing selected) --}}
            <template x-if="!localFileName && @json($fileValue == null)">
                <div class="d-flex align-items-center w-100 text-muted">
                    <i class="fas fa-cloud-upload-alt mr-3 fa-lg opacity-50"></i>
                    <span class="small">Click to attach document...</span>
                </div>
            </template>

        </div>
    </div>
    <template x-if="errorMessage && errorMessage.length > 0">
        <div x-transition:enter="animate__animated animate__fadeIn animate__faster"
            x-transition:leave="animate__animated animate__fadeOut animate__faster"
            class="text-success d-block mt-2 animate__animated animate__headShake x-small">

            <i class="fas fa-info-circle mr-2 text-success"></i>

            <b x-text="errorMessage.includes('too large') ? 'Limit Reached: ' : 'Notice: '"></b>
            <span x-text="errorMessage"></span>
        </div>
    </template>

    <div x-show="!errorMessage">
        @error($model)
            <div class="text-success d-block mt-2 animate__animated animate__headShake x-small" >
                <i class="fas fa-info-circle mr-2"></i> {{ $message }}
            </div>
        @enderror
    </div>
</div>
