@props([
    'model',
    'options' => [],
    'placeholder' => 'Select options',
    'optionLabel' => '',
    'optionValue' => '',
    'icon' => 'fas fa-list-ul',
])

<div x-data="{
        open: false,
        search: '',
        value: @entangle($model).live,
        options: {{ json_encode($options) }},

        get displayLabel() {
            if (!this.value || this.value.length === 0) return '{{ $placeholder }}';
            if (this.value.length === 1) {
                let found = this.options?.find(o => o['{{ $optionValue }}'] == this.value[0]);
                return found ? found['{{ $optionLabel }}'] : '{{ $placeholder }}';
            }
            return this.value.length + ' Selected';
        },

        get filteredOptions() {
            if (!this.options || !Array.isArray(this.options)) return [];
            if (!this.search) return this.options;
            return this.options.filter(option =>
                option && option['{{ $optionLabel }}'] &&
                String(option['{{ $optionLabel }}']).toLowerCase().includes(this.search.toLowerCase())
            );
        },

        toggle(opt) {
            if (!opt) return;
            let id = opt['{{ $optionValue }}'];
            // Initialize as array if null/undefined
            if (!Array.isArray(this.value)) this.value = [];

            if (this.value.includes(id)) {
                this.value = this.value.filter(v => v != id);
            } else {
                this.value.push(id);
            }
        },

        isSelected(id) {
            return Array.isArray(this.value) && this.value.includes(id);
        },

        clear() {
            this.value = [];
            this.search = '';
        },

        selectAll() {
            this.value = this.options.map(o => o['{{ $optionValue }}']);
        }
    }"
    x-effect="options = {{ json_encode($options) }}"
    @keydown.escape.window="open = false"
    class="position-relative w-100">

    {{-- Trigger Button --}}
    <div @click="open = !open"
         class="form-control d-flex align-items-center justify-content-between"
         :class="open ? 'border-primary shadow-sm' : ''"
         style="height: 48px; border-radius: 12px; cursor: pointer; transition: all 0.2s; padding-right: 12px;">

        <div class="d-flex align-items-center flex-grow-1 h-100 overflow-hidden">
            <div class="position-relative d-flex align-items-center justify-content-center mr-2" style="min-width: 25px;">
                <i class="{{ $icon }} text-info" :class="value.length > 0 ? 'opacity-100' : 'opacity-50'"></i>
                <template x-if="value.length > 0">
                    <span class="badge badge-primary position-absolute rounded-circle d-flex align-items-center justify-content-center"
                          style="width: 16px; height: 16px; font-size: 0.6rem; top: -8px; right: -8px; border: 2px solid white;">
                        <span x-text="value.length"></span>
                    </span>
                </template>
            </div>

            <span x-text="displayLabel"
                :class="value.length === 0 ? 'text-muted' : 'text-dark font-weight-bold'"
                class="text-truncate" style="font-size: 0.95rem;"></span>
        </div>

        <div class="d-flex align-items-center">
            <template x-if="value.length > 0">
                <button @click.stop="clear()" type="button" class="btn btn-link btn-sm text-muted p-0 mr-2 border-0 shadow-none">
                    <i class="fas fa-times-circle"></i>
                </button>
            </template>
            <i class="fas fa-chevron-down text-muted small" :style="open ? 'transform: rotate(180deg)' : ''" style="transition: transform 0.2s;"></i>
        </div>
    </div>

    {{-- Dropdown Panel --}}
    <div x-show="open" x-cloak x-transition @click.away="open = false"
         class="position-absolute w-100 bg-white shadow-lg mt-2"
         style="z-index: 1090; border-radius: 12px; border: 1px solid rgba(0,0,0,.12); overflow: hidden;">

        {{-- Search & Bulk Actions --}}
        <div class="p-2 border-bottom bg-white">
            <div class="input-group input-group-sm bg-light rounded-pill px-2 mb-2" style="border: 1px solid #eee;">
                <div class="input-group-prepend border-0">
                    <span class="input-group-text bg-transparent border-0 text-muted"><i class="fas fa-search"></i></span>
                </div>
                <input type="text" x-model="search" @click.stop class="form-control border-0 bg-transparent shadow-none py-2" placeholder="Search...">
            </div>
            <div class="d-flex justify-content-between px-2">
                <button @click="selectAll()" type="button" class="btn btn-link btn-sm p-0 text-primary font-weight-bold" style="font-size: 0.75rem;">Select All</button>
                <button @click="clear()" type="button" class="btn btn-link btn-sm p-0 text-muted font-weight-bold" style="font-size: 0.75rem;">Clear All</button>
            </div>
        </div>

        <div style="max-height: 250px; overflow-y: auto;" class="py-1">
            <template x-for="(option, index) in filteredOptions" :key="index">
                <div class="px-1">
                    <button type="button" @click="toggle(option)"
                            :class="isSelected(option['{{ $optionValue }}']) ? 'bg-primary-light' : ''"
                            class="dropdown-item d-flex align-items-center justify-content-between py-2 px-3 mb-1 rounded-lg border-0">

                        <div class="d-flex align-items-center">
                            <i :class="isSelected(option['{{ $optionValue }}']) ? 'fas fa-check-square text-primary' : 'far fa-square text-muted'" class="mr-2"></i>
                            <span x-text="option['{{ $optionLabel }}']" :class="isSelected(option['{{ $optionValue }}']) ? 'font-weight-bold text-primary' : ''"></span>
                        </div>
                    </button>
                </div>
            </template>
        </div>
    </div>
</div>

<style>
    .bg-primary-light { background-color: rgba(37, 99, 235, 0.08); }
</style>
