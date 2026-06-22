<div class="card border-0 animate-on-load" x-data="{
    memberSearch: '',
    evaluateRow(el) {
        const text = el.innerText.toLowerCase();
        return text.includes(this.memberSearch.toLowerCase());
    }
}">
    <div class="card-header bg-white border-0 py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center">
        <div class="mb-3 mb-md-0">
            <h6 class="mb-0 font-weight-bold text-dark text-uppercase" style="letter-spacing: 1px;">
                <i class="fas fa-users mr-2 text-primary"></i> Group Members
            </h6>
            <small class="text-muted">{{ $this->application->groupMembers->count() }} total members</small>
        </div>

        <div class="search-wrapper" style="width: 100%; max-width: 350px;">
            <div class="input-group input-group-merge shadow-sm">
                <div class="input-group-prepend">
                    <span class="input-group-text bg-white border-right-0">
                        <i class="fas fa-search text-muted"></i>
                    </span>
                </div>
                <input type="text"
                       x-model="memberSearch"
                       class="form-control border-left-0"
                       placeholder="Search name, ID, or phone...">
                <div class="input-group-append" x-show="memberSearch.length > 0" x-cloak>
                    <button class="btn btn-white border-left-0 text-muted" @click="memberSearch = ''">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive p-0"
            style="max-height: 550px; transition: opacity 0.3s ease;"
            wire:key="pending-applications">
            <table id="table-data" class="table align-middle mb-0 p-0 table-head-fixed">
                <thead>
                    <tr>
                        <x-table-header :sortable="false">
                            Member Identity
                        </x-table-header>
                        <x-table-header :sortable="false">
                            Contact & Bio
                        </x-table-header>
                        <x-table-header :sortable="false">
                            Verification
                        </x-table-header>
                        <x-table-header :sortable="false">
                            Documents
                        </x-table-header>
                        <x-table-header :sortable="false">
                            Action
                        </x-table-header>
                    </tr>
                </thead>
                <tbody>
                    @foreach($this->application->groupMembers as $member)
                    <tr x-show="evaluateRow($el)"
                        x-transition:enter.duration.200ms>
                        <td class="pl-4 py-3">
                            <div class="d-flex align-items-center">
                                <div class="avatar-circle rounded-circle mr-3 d-flex align-items-center justify-content-center font-weight-bold" style="width: 40px; height: 40px;">
                                    {{ substr($member->name, 0, 1) }}
                                </div>
                                <div>
                                    <div class="text-gray-900">{{ $member->name }}</div>
                                    <code class="text-xs text-gray-500">{{ $member->id_number }}</code>
                                </div>
                            </div>
                        </td>
                        <td class="pl-4 py-3">
                            <div class="">
                                <div class="text-gray-900">{{ $member->phone_number }}</div>
                                <div class="text-xs text-gray-500">{{ $member->position ?? 'Member' }}</div>
                            </div>
                        </td>
                        <td class="pl-4 py-3">
                            @if($member->is_defaulter)
                                <span class="bg-red-50 border-red-200 text-red-700 border d-inline-flex align-items-center font-medium px-2.5 py-1 rounded-lg text-xs whitespace-nowrap">
                                    Defaulter
                                </span>
                            @else
                                <span class="bg-gray-50 border-gray-200 text-gray-700 border d-inline-flex align-items-center font-medium px-2.5 py-1 rounded-lg text-xs whitespace-nowrap">
                                    Clean Record
                                </span>
                            @endif
                        </td>
                        <td class="pl-4 py-3">
                             <div class="btn-group" role="group">
                                <a href="{{ $this->getMemberIdViewUrl($member->photo_member_id['path'] ?? $member->photo_member_id) }}"
                                target="_blank"
                                class="align-items-center btn btn-outline-info d-inline-flex p-1 rounded-lg"
                                title="View National ID">
                                    <i class="fas fa-eye mr-1"></i> View ID
                                </a>
                             </div>
                        </td>
                        <td class="pl-4 py-3">
                            <x-modal-trigger
                                target="viewMember({{ $member->id }})"
                                label=""
                                title="View"
                                icon="fas fa-eye"
                                variant="outline-primary"
                                defaultLabel=""
                            />
                        </td>
                    </tr>
                    @endforeach

                    <tr x-show="memberSearch !== '' && Array.from($el.parentElement.children).filter(r => r.style.display !== 'none').length === 0" x-cloak>
                        <td colspan="5" class="text-center py-5">
                            <i class="fas fa-search-minus fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No members found matching "<span x-text="memberSearch" class="font-weight-bold"></span>"</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
