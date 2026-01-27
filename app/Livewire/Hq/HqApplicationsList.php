<?php

namespace App\Livewire\Hq;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\PhaseApplication;
use Illuminate\Support\Facades\DB;

class HqApplicationsList extends Component
{

use WithPagination;

    public $search = '';
    public $filterStatus = '';
    protected $paginationTheme = 'bootstrap';

    public function render()
    {
        // Stats specific to the Applications page
        $stats = [
            'total' => PhaseApplication::count(),
            'pending' => PhaseApplication::where('status', 'pending')->count(),
            'appraised' => PhaseApplication::where('status', 'appraised')->count(),
            'amt_requested' => PhaseApplication::sum('amount_applied'),
        ];

        $applications = PhaseApplication::with(['linkedGroup'])
            // ->when($this->search, function($query) {
            //     $query->whereHas('group', function($q) {
            //         $q->where('group_name', 'like', '%'.$this->search.'%');
            //     })->orWhere('application_ref', 'like', '%'.$this->search.'%');
            // })
            // ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('livewire.hq.hq-applications-list', [
            'applications' => $applications,
            'stats' => $stats
        ]);
    }
}
