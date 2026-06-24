{{-- Dropdown panel (inline or teleported for modals) --}}
<div x-show="open"
     x-cloak
     x-transition:enter="animate__animated animate__fadeIn animate__fastest"
     @click.away="open = false"
     @if($teleport)
         x-anchor.bottom-start="$refs.trigger"
         class="bg-white shadow-lg"
         style="z-index: 1065; position: fixed; min-width: 280px; border-radius: 12px; border: 1px solid rgba(0,0,0,.12); overflow: hidden;"
     @else
         class="position-absolute w-100 bg-white shadow-lg mt-2"
         style="z-index: 1090; border-radius: 12px; border: 1px solid rgba(0,0,0,.12); overflow: hidden;"
     @endif
>

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
                    <span x-text="option ? option['{{ $optionLabel }}'] : ''" class="text-left" style="white-space: normal; line-height: 1.35;"></span>

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
