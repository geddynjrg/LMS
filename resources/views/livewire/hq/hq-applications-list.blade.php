<div>
    <div class="row mb-4">
        @foreach([
            ['label' => 'Total Apps', 'val' => $stats['total'], 'color' => 'info', 'icon' => 'fa-list'],
            ['label' => 'Pending HQ', 'val' => $stats['pending'], 'color' => 'warning', 'icon' => 'fa-pause-circle'],
            ['label' => 'Approved', 'val' => $stats['appraised'], 'color' => 'success', 'icon' => 'fa-check-double'],
            ['label' => 'Global Request', 'val' => 'KES '.number_format($stats['amt_requested']/1000000, 1).'M', 'color' => 'primary', 'icon' => 'fa-money-bill-wave']
        ] as $item)
        <div class="col-md-3">
            <div class="card card-outline card-{{ $item['color'] }} shadow-sm">
                <div class="card-body p-3 text-center">
                    <h5 class="text-muted small text-uppercase font-weight-bold mb-1">{{ $item['label'] }}</h5>
                    <h3 class="font-weight-bold mb-0">{{ $item['val'] }}</h3>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white">
            <div class="row">
                <div class="col-md-8">
                    <input wire:model.live="search" type="text" class="form-control" placeholder="Search by Group or Ref Number...">
                </div>
                <div class="col-md-4">
                    <select wire:model.live="filterStatus" class="form-control">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending Review</option>
                        <option value="appraised">Appraised/Approved</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover">
                <thead class="bg-light">
                    <tr>
                        <th>Ref</th>
                        <th>Group Name</th>
                        <th>County/Const</th>
                        <th class="text-right">Amount</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($applications as $app)
                    <tr>
                        <td><span class="badge badge-light border">{{ $app->application_ref }}</span></td>
                        <td class="text-bold text-dark">{{ $app->linkedGroup?->group_name }}</td>
                        <td>{{ '$app->constituency->county->county_name' }} / {{ '$app->constituency->constituency_name' }}</td>
                        <td class="text-right text-primary font-weight-bold">KES {{ number_format($app->amount_applied) }}</td>
                        <td>
                            <span class="badge @if($app->status == 'pending') badge-warning @else badge-success @endif">
                                {{ ucfirst($app->status) }}
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-xs btn-outline-info"><i class="fas fa-eye"></i> Details</button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No applications found matching your criteria.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">
            {{ $applications->links() }}
        </div>
    </div>
</div>
