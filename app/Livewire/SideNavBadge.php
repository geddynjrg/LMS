<?php
namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\DB;

class SideNavBadge extends Component
{
    public $constituencyCode;
    public $type; // 'applications', 'loans', 'groups', 'reports', etc.

    #[On('app-updated')]
    public function refreshSideNavBadge()
    {
        // dd('here');
    }

    public function render()
    {
        $count = $this->getCount();

        return view('livewire.side-nav-badge', ['count' => $count]);
    }

    private function getCount()
    {
        if (!$this->constituencyCode) return 0;

        $query = DB::table('temp_applications')
                   ->where('constituency_code', $this->constituencyCode);

        // Run a different query based on the 'type' you passed in
        return (int) match ($this->type) {
            // 'in_constituency_review' => DB::table('phase_applications')->where('status', 'pending')->count(),
            'not_linked'   => $query->where('status', 'under_constituency_review')->count(),
            'pending_loan' => DB::table('loan_phases')->where('status', 'pending')->count(),
            'new_groups'   => DB::table('groups')->where('active', 0)->count(),
            'constituency_tasks' => DB::table('loan_phases')
                ->where('constituency_code', $this->constituencyCode)
                ->where('status', 'draft')
                ->count(),
            default        => 0,
        };
    }
}
