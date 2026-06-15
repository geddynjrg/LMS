@props([
    'model',
    'label' => '',
    'placeholder' => '',
    'icon' => 'fas fa-pen-nib',
    'type' => 'text'
])

<div class="form-group">
    @if($label)
        <label class="small font-weight-bold text-uppercase text-muted mb-2">
            <i class="{{ $icon }} mr-1 text-primary"></i> {{ $label }}
        </label>
    @endif

    <div class="premium-input-wrapper position-relative d-flex align-items-center bg-white"
         style="height: 48px; border-radius: 12px; border: 1px solid #ced4da; transition: all 0.2s;">

        <div class="d-flex align-items-center justify-content-center ml-3 mr-1" style="min-width: 25px;">
            <i class="{{ $icon }} text-muted opacity-50" style="font-size: 0.9rem;"></i>
        </div>

        <input type="{{ $type }}"
               class="form-control border-0 shadow-none bg-transparent h-100"
               style="font-size: 0.95rem; border-radius: 12px; padding-left: 5px;"
               placeholder="{{ $placeholder }}"
               wire:model.live="{{ $model }}">

        <div x-data="{ val: @entangle($model) }" x-show="val" x-cloak>
            <button type="button"
                    @click="val = ''"
                    class="btn btn-link btn-sm text-muted p-0 mr-3 border-0 shadow-none hover-danger">
                <i class="fas fa-times-circle"></i>
            </button>
        </div>
    </div>

    @error($model)
        <span class="text-danger small mt-1 d-block" style="font-size: 0.75rem;">
            <i class="fas fa-exclamation-triangle mr-1"></i> {{ $message }}
        </span>
    @enderror
</div>

<style>
    /* Add this to your main CSS or a style block if not already present */
    .premium-input-wrapper:focus-within {
        border-color: #007bff !important;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.1) !important;
    }
    .hover-danger:hover { color: #dc3545 !important; }
</style>
