<div x-data="{
    search: '',
    isMatch(category, permissions) {
        const s = this.search.toLowerCase();
        if (s === '') return true;
        if (category.toLowerCase().includes(s)) return true;
        return permissions.some(p => p.name.toLowerCase().includes(s));
    }
}">
    {{-- Role Alert --}}
    @unless(auth()->user()->hasRole('super-admin'))
        <div class="alert alert-light border-left border-info shadow-sm mb-4">
            <i class="fas fa-info-circle text-info mr-2"></i>
            <small class="text-muted">As a <strong>Constituency Officer</strong>, your management range is limited to specific staff roles.</small>
        </div>
    @endunless

    {{-- Search & Bulk Actions - Stay sticky/visible at top --}}
    <div class="row mb-4 align-items-center">
        <div class="col-md-7">
            <div class="input-group shadow-sm">
                <div class="input-group-prepend">
                    <span class="input-group-text bg-white border-right-0"><i class="fas fa-search text-muted"></i></span>
                </div>
                <input type="text" x-model="search" class="form-control border-left-0" placeholder="Filter permissions (e.g. Receipt, Member)...">
            </div>
        </div>
        <div class="col-md-5 text-right">
            <div class="btn-group shadow-sm">
                <button wire:click="syncAllPermissions" class="btn btn-sm btn-outline-primary px-3">Grant All</button>
                <button wire:click="revokeAllPermissions" class="btn btn-sm btn-outline-danger px-3">Revoke All</button>
            </div>
        </div>
    </div>

    {{-- SCROLLABLE CONTAINER --}}
    <div class="permission-scroll-area pr-2" wire:ignore.self style="max-height: 600px; overflow-y: auto; overflow-x: hidden;">
        <div class="row">
            @foreach($categorizedPermissions as $category => $permissions)
                <div class="col-12 mb-3" wire:key="cat-group-{{ Str::slug($category) }}-{{ count($permissions) }}" x-show="isMatch('{{ $category }}', {{ json_encode($permissions) }})">
                    <div class="card card-outline card-info shadow-none border mb-0">
                    <div class="card-header py-2 d-flex justify-content-between align-items-center bg-light">
                        <h3 class="card-title font-weight-bold text-muted" style="font-size: 0.85rem; letter-spacing: 0.5px;">
                            <i class="fas fa-th-large mr-2 text-info small"></i>{{ strtoupper($category) }}
                        </h3>
                        {{-- Select All Button --}}
                        <button type="button"
                                wire:click="syncPermissionGroup('{{ $category }}')"
                                wire:loading.attr="disabled"
                                class="btn btn-xs btn-link text-primary p-0 font-weight-bold"
                                style="text-decoration: none;">
                            <span wire:loading.remove wire:target="syncPermissionGroup('{{ $category }}')">
                                <i class="fas fa-check-circle mr-1"></i>Select All
                            </span>
                            <span wire:loading wire:target="syncPermissionGroup('{{ $category }}')">
                                <i class="fas fa-spinner fa-spin"></i>
                            </span>
                        </button>
                    </div>

                    <div class="card-body p-3">
                        <div class="row">
                            @foreach($permissions as $permission)
                                {{-- Use a simpler ID structure for the key --}}
                                <div class="col-sm-6 col-md-4 col-lg-3 mb-2"
                                     wire:key="p-wrap-{{ $permission->id }}"
                                     x-show="search === '' || '{{ strtolower($permission->name) }}'.includes(search.toLowerCase())">

                                    <div class="custom-control custom-checkbox p-0 border rounded hover-highlight transition-all d-flex align-items-center"
                                         style="min-height: 42px;">

                                        <input type="checkbox"
                                               class="custom-control-input position-static m-0 ml-3"
                                               wire:key="p-input-{{ $permission->id }}"
                                               id="perm-{{ $permission->id }}"
                                               wire:click="togglePermission('{{ $permission->name }}')"
                                               {{ $user->hasPermissionTo($permission->name) ? 'checked' : '' }}
                                               style="cursor: pointer; width: 1.2rem; height: 1.2rem;">

                                        <label class="font-weight-normal mb-0 py-2 pr-2 pl-2 flex-grow-1"
                                               for="perm-{{ $permission->id }}"
                                               style="cursor: pointer; font-size: 0.88rem; line-height: 1.2;">
                                            {{ Str::headline(collect(explode(' ', $permission->name))->first()) }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                </div>
            @endforeach
        </div>

        {{-- Empty Search State inside scroll area --}}
        <div x-show="search !== '' && !document.querySelectorAll('.custom-control:not([style*=\'display: none\'])').length" class="text-center py-5" x-cloak>
            <i class="fas fa-search-minus fa-3x text-light mb-3"></i>
            <p class="text-muted">No permissions matching your search.</p>
        </div>
    </div>
</div>

<style>
    /* Custom Scrollbar for a cleaner look */
    .permission-scroll-area::-webkit-scrollbar {
        width: 6px;
    }
    .permission-scroll-area::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    .permission-scroll-area::-webkit-scrollbar-thumb {
        background: #ced4da;
        border-radius: 10px;
    }
    .permission-scroll-area::-webkit-scrollbar-thumb:hover {
        background: #a8adb2;
    }

    .hover-highlight {
        background-color: #ffffff;
        border: 1px solid #e9ecef !important;
    }

    .hover-highlight:hover {
        background-color: #f8f9fa;
        border-color: #007bff !important; /* Blue border on hover */
        transform: translateY(-1px);
    }

    .transition-all {
        transition: all 0.2s ease-in-out;
    }

    /* Boost the checkbox label padding */
    .custom-control-label::before,
    .custom-control-label::after {
        top: 0.6rem; /* Centers the checkbox vertically in the padded box */
        left: 0.5rem;
    }

    .custom-checkbox .custom-control-label {
        padding-top: 0.25rem;
    }
    .custom-control-input.position-static {
        opacity: 1 !important;
        z-index: 1;
    }
</style>
