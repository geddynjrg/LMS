@props([
    'model',
    'options' => [],
    'placeholder' => 'Select an option',
    'optionLabel' => '',
    'optionValue' => '',
    'icon' => 'fa fa-layer-group',
    'size' => 48,
])

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
            // Watch for when the dropdown opens to focus the search
            this.$watch('open', value => {
                if (value) {
                    this.$nextTick(() => {
                        if (this.$refs.searchField) this.$refs.searchField.focus();
                    });
                }
            });

            // Watch for options changes from Livewire
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
    <div @click="open = !open"
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

            {{-- Use displayLabel getter here --}}
            <span x-text="displayLabel"
                :class="!value ? 'text-muted' : 'text-dark font-weight-bold'"
                class="text-truncate"
                style="font-size: 0.95rem;"></span>
        </div>

        <div class="d-flex align-items-center">
            {{-- Loading Spinner (Shows only when Livewire is updating) --}}
            <div wire:loading wire:target="{{ $model }}" class="mr-2">
                <i class="fas fa-circle-notch fa-spin text-primary" style="font-size: 0.9rem;"></i>
            </div>

            {{-- Clear Button (Hidden while loading) --}}
            <template x-if="value">
                <button wire:loading.remove wire:target="{{ $model }}"
                        @click.stop="clear()"
                        type="button"
                        class="btn btn-link btn-sm text-muted p-0 mr-2 hover-danger border-0 shadow-none custom-input"
                        title="Clear selection">
                    <i class="fas fa-times-circle"></i>
                </button>
            </template>

            {{-- Chevron Icon (Hidden while loading) --}}
            <i wire:loading.remove wire:target="{{ $model }}"
               @click="open = !open"
               class="fas fa-chevron-down text-muted small"
               :style="open ? 'transform: rotate(180deg)' : ''"
               style="transition: transform 0.2s;"></i>
        </div>
    </div>

    {{-- Dropdown Panel --}}
    <div x-show="open"
         x-cloak
         x-transition:enter="animate__animated animate__fadeIn animate__fastest"
         @click.away="open = false"
         class="position-absolute w-100 bg-white shadow-lg mt-2"
         style="z-index: 1090; border-radius: 12px; border: 1px solid rgba(0,0,0,.12); overflow: hidden;">

        <div class="p-2 border-bottom bg-white">
            <div class="input-group input-group-sm bg-light rounded-pill px-2" style="border: 1px solid #eee;">
                <div class="input-group-prepend border-0">
                    <span class="input-group-text bg-transparent border-0 text-muted">
                        <i class="fas fa-search"></i>
                    </span>
                </div>
                <input type="text"
                       x-model="search"
                       x-ref="searchField"
                       @click.stop
                       class="form-control border-0 bg-transparent shadow-none py-2"
                       placeholder="Filter options..."
                       style="font-size: 0.9rem;">
            </div>
        </div>

        <div style="max-height: 220px; overflow-y: auto;" class="py-1 custom-scrollbar">
            <template x-for="(option, index) in filteredOptions" :key="index">
                <div x-show="option" class="w-100">
                    <button type="button"
                            @click="select(option)"
                            class="dropdown-item d-flex align-items-center justify-content-between py-2 px-3 mb-1 mx-1 rounded-lg"
                            style="width: calc(100% - 10px); transition: 0.2s; border: none;">
                       <span x-text="option ? option['{{ $optionLabel }}'] : ''" class="text-truncate"></span>

            {{-- Use x-if for the checkmark to avoid reference errors --}}
            <template x-if="option && value == option['{{ $optionValue }}']">
                <i class="fas fa-check-circle text-primary"></i>
            </template>
                    </button>
                </div>
            </template>

            <div x-show="!filteredOptions || filteredOptions.length === 0" class="text-center py-4 text-muted">
                <i class="fas fa-search d-block mb-2 opacity-25 fa-2x"></i>
                <div class="small px-3">
                    No results found
                    <template x-if="search">
                        <span>for "<span class="font-weight-bold text-dark" x-text="search"></span>"</span>
                    </template>
                </div>
            </div>
        </div>
    </div>
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
