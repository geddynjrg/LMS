<?php

namespace App\Livewire\Hq;

use Livewire\Component;
use App\Models\PhaseApplication;
use App\Models\ConstituencyBatch; // Assuming you have a batch/submission model
use Illuminate\Support\Facades\DB;
use App\Models\LoanPhase;
use App\Models\GroupLoan;

class HqDashboard extends Component
{
    public function render()
    {
        // 1. High-Level Financial Metrics
        $stats = [
            'total_applications' => PhaseApplication::count(),
            'total_groups_loaned' => GroupLoan::where('loan_status', 'financed')->distinct('group_id')->count(),
            'total_member_loans' => DB::table('group_members')->count(), // Based on group_members table
            'pending_review' => LoanPhase::where('status', 'submitted_to_hq')->count(),
            'youth_groups' => GroupLoan::where('category', 'like', '%Youth%')->count(),
            'women_groups' => GroupLoan::where('category', 'like', '%Women%')->count(),
            'pwd_groups' => GroupLoan::where('category', 'like', '%PWD%')->count(),
            'defaulters' => GroupLoan::where('is_defaulted', 1)->count(),
        ];

        $activities = GroupLoan::select('sector as name', DB::raw('count(*) as count'))
            ->groupBy('sector')
            ->orderByDesc('count')
            ->limit(3)
            ->get();

        $countyStats = DB::table('counties')
            ->join('loan_phases', 'counties.county_code', '=', 'loan_phases.constituency_code')
            ->select('counties.county_name', DB::raw('count(*) as total'))
            ->groupBy('counties.county_name')
            ->orderByDesc('total')
            ->limit(3)
            ->get();

        // 2. Demographic Distribution
        $demographics = [
            'youth' => [
                'count' => GroupLoan::where('category', 'like', '%Youth%')->count(),
                'color' => 'info'
            ],
            'women' => [
                'count' => GroupLoan::where('category', 'like', '%Women%')->count(),
                'color' => 'maroon'
            ],
            'pwd' => [
                'count' => GroupLoan::where('category', 'like', '%PWD%')->count(),
                'color' => 'teal'
            ],
        ];

        // 3. Actionable Queue: Recent Submissions from Constituencies
        // This allows HQ to jump straight into the review process
        $recentSubmissions = LoanPhase::with('constituency')
            ->where('status', 'submitted_to_hq')
            ->orderBy('submitted_to_hq_at', 'desc')
            ->limit(5)
            ->get();

        $countyApps = DB::table('counties')
            ->join('constituencies', 'counties.county_code', '=', 'constituencies.county_code')
            ->join('loan_phases', 'constituencies.constituency_code', '=', 'loan_phases.constituency_code')
            ->select('counties.county_name', DB::raw('count(loan_phases.id) as total'))
            ->groupBy('counties.county_name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $recentPhases = LoanPhase::with(['constituency', 'constituencyOfficer']) // Assuming 'officer' relation exists
            ->whereIn('status', ['submitted_to_hq', 'in_review'])
            ->orderBy('submitted_to_hq_at', 'desc')
            ->limit(3)
            ->get();

        $financials = [
            'total_disbursed' => GroupLoan::where('loan_status', 'financed')->sum('amount_approved'),
            'total_approved_pending' => GroupLoan::where('loan_status', 'approved')->sum('amount_approved'),
            'total_defaulted_amt' => GroupLoan::where('is_defaulted', 1)->sum('total_arrears'), // or current balance
            'total_repaid' => DB::table('loan_receipts')->sum('amount'), // If you have a receipts table
        ];

        return view('livewire.hq.hq-dashboard', [
            'stats' => $stats,
            'demographics' => $demographics,
            'recentSubmissions' => $recentSubmissions,
            'activities' => $activities,
            'countyStats' => $countyStats,
            'countyApps' => $countyApps,
            'recentPhases' => $recentPhases,
            'financials' => $financials,
        ]);
    }
}
