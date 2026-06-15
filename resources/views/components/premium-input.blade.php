@props([
    'model',
    'type' => 'text',
    'placeholder' => '',
    'icon' => 'fas fa-edit',
    'label' => null,
    'required' => false,
    'readonly' => false
])

<div class="w-100"
     x-data="{
        focused: false,
        value: @entangle($model).live,
        type: '{{ $type }}'
     }">

    @if($label)
        <label class="text-uppercase text-muted font-weight-black small tracking-wider mb-2">
            {{ $label }} @if($required)<span class="text-danger">*</span>@endif
        </label>
    @endif

    <div class="input-group premium-input-group transition-all"
         :class="{ 'premium-focus': focused, 'is-invalid-wrapper': {{ $errors->has($model) ? 'true' : 'false' }} }">

        {{-- Icon Prepend --}}
        <div class="input-group-prepend">
            <span class="input-group-text bg-white border-right-0 transition-all"
                  :class="focused ? 'text-primary border-primary' : 'text-info'"
                  style="border-radius: 12px 0 0 12px; border: 1px solid #ced4da;">
                <i class="{{ $icon }}" :class="focused ? 'animate__animated animate__pulse' : ''"></i>
            </span>
        </div>

        {{-- Main Input --}}
        <input :type="type"
               x-model="value"
               @focus="focused = true"
               @blur="focused = false"
               placeholder="{{ $placeholder }}"
               {{ $readonly ? 'readonly' : '' }}
               {{ $attributes->merge(['class' => 'form-control form-control-lg shadow-none border-left-0']) }}
               style="height: 48px; border-radius: 0 12px 12px 0; font-size: 0.95rem; @if($readonly) background-color: #f8f9fa; @endif">

        {{-- Interactive Suffix (Clear or Toggle Password) --}}
        <div class="input-group-append" x-show="value && !@json($readonly)">
            <span class="input-group-text bg-white border-left-0" style="border-radius: 0 12px 12px 0; border: 1px solid #ced4da;">
                @if($type === 'password')
                    <button type="button" @click="type = (type === 'password' ? 'text' : 'password')" class="btn btn-link btn-sm p-0 text-muted shadow-none">
                        <i class="fas" :class="type === 'password' ? 'fa-eye' : 'fa-eye-slash'"></i>
                    </button>
                @else
                    <button type="button" @click="value = ''" class="btn btn-link btn-sm p-0 text-muted shadow-none">
                        <i class="fas fa-times-circle"></i>
                    </button>
                @endif
            </span>
        </div>
    </div>

    @error($model)
        <span class="text-danger small font-weight-bold mt-1 d-block animate__animated animate__fadeIn">
            <i class="fas fa-exclamation-circle mr-1"></i> {{ $message }}
        </span>
    @enderror
</div>

