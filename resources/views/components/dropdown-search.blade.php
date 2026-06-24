@props([
    'model',
    'options' => [],
    'placeholder' => 'Select an option',
    'optionLabel' => '',
    'optionValue' => '',
    'icon' => 'fa fa-layer-group',
    'size' => 48,
    'teleport' => false,
])

@php
    $useTeleport = filter_var($teleport, FILTER_VALIDATE_BOOLEAN);
@endphp

<div x-data="{
        open: false,
        search: '',
        value: @entangle($model).live,
        options: {{ json_encode($options) }},

        get displayLabel() {
            if (!this.value) return '{{ $placeholder }}';
            let found = this.options?.find(o => o && o['{{ $optionValue }}'] == this.value);
            return found && found['{{ $optionLabel }}'] ? found['{{ $optionLabel }}'] : '{{ $placeholder }}';
        },

        get filteredOptions() {
            if (!this.options || !Array.isArray(this.options)) return [];
            if (!this.search) return this.options;
            return this.options.filter(option =>
                option && option['{{ $optionLabel }}'] &&
                String(option['{{ $optionLabel }}']).toLowerCase().includes(this.search.toLowerCase())
            );
        },

        init() {
            this.$watch('open', value => {
                if (value) {
                    this.$nextTick(() => {
                        if (this.$refs.searchField) this.$refs.searchField.focus();
                    });
                }
            });

            this.$watch('options', (newOptions) => {
                if (newOptions && Array.isArray(newOptions)) {
                    this.options = newOptions;
                } else {
                    this.options = [];
                }
            }, { deep: true });
        },

        select(opt) {
            if (!opt) return;
            this.value = opt['{{ $optionValue }}'];
            this.open = false;
            this.search = '';
        },

        clear() {
            this.value = null;
            this.search = '';
            this.open = false;
        },

        getSelectedColor() {
            if (!this.value || !this.options) return '#64748b';
            let found = this.options?.find(o => o && o['{{ $optionValue }}'] == this.value);
            return found?.color || '#64748b';
        }
    }"
    @keydown.escape.window="open = false"
    x-effect="options = {{ json_encode($options) }}"
    class="position-relative w-100">

    {{-- Trigger Button --}}
    <div x-ref="trigger"
         @click="open = !open"
         class="form-control d-flex align-items-center justify-content-between"
         :class="open ? 'border-primary shadow-sm' : ''"
         style='height: {{ $size }}px; border-radius: 12px; cursor: pointer; transition: all 0.2s; padding-right: 12px; position: relative;'>

        <div class="d-flex align-items-center flex-grow-1 h-100 overflow-hidden">
            <div class="position-relative d-flex align-items-center justify-content-center mr-2" style="min-width: 25px;">
                <i class="{{ $icon }} icon-premium"
                   style="font-size: 0.9rem; transition: 0.2s;"
                   :class="value ? 'opacity-50' : ''"></i>

                <template x-if="value">
                    <span :style="'background-color: ' + getSelectedColor()"
                        class="rounded-circle shadow-sm position-absolute"
                        style="width: 10px; height: 10px; border: 1.5px solid white; bottom: -2px; right: -2px;">
                    </span>
                </template>
            </div>

            <span x-text="displayLabel"
                :class="!value ? 'text-muted' : 'text-dark font-weight-bold'"
                class="text-truncate"
                style="font-size: 0.95rem;"></span>
        </div>

        <div class="d-flex align-items-center">
            <div wire:loading wire:target="{{ $model }}" class="mr-2">
                <i class="fas fa-circle-notch fa-spin text-primary" style="font-size: 0.9rem;"></i>
            </div>

            <template x-if="value">
                <button wire:loading.remove wire:target="{{ $model }}"
                        @click.stop="clear()"
                        type="button"
                        class="btn btn-link btn-sm text-muted p-0 mr-2 hover-danger border-0 shadow-none custom-input"
                        title="Clear selection">
                    <i class="fas fa-times-circle"></i>
                </button>
            </template>

            <i wire:loading.remove wire:target="{{ $model }}"
               @click="open = !open"
               class="fas fa-chevron-down text-muted small"
               :style="open ? 'transform: rotate(180deg)' : ''"
               style="transition: transform 0.2s;"></i>
        </div>
    </div>

    @if($useTeleport)
        <template x-teleport="body">
            @include('components.partials.dropdown-search-panel', [
                'model' => $model,
                'optionLabel' => $optionLabel,
                'optionValue' => $optionValue,
                'teleport' => true,
            ])
        </template>
    @else
        @include('components.partials.dropdown-search-panel', [
            'model' => $model,
            'optionLabel' => $optionLabel,
            'optionValue' => $optionValue,
            'teleport' => false,
        ])
    @endif
</div>

<style>
    .custom-input:hover {
        border-color: #dee2e6 !important;
        background-color: #f8f9fa;
    }
    .hover-danger:hover { color: #dc3545 !important; text-decoration: none; }
    .custom-scrollbar::-webkit-scrollbar { width: 5px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #d1d1d1; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #b1b1b1; }
    .dropdown-item { font-size: 0.95rem; color: #4a5568; }
    .dropdown-item:hover { background-color: #f0f7ff !important; color: var(--color-primary) !important; }
    .rounded-lg { border-radius: 8px !important; }
    .icon-premium { color: #64748b !important; opacity: 0.7; }
    .border-primary .icon-premium { color: #475569 !important; opacity: 1; }
</style>
