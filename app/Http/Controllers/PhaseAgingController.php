<?php

namespace App\Http\Controllers;

use App\Services\PhaseAgingAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PhaseAgingController extends Controller
{
    public function __construct(private readonly PhaseAgingAnalyticsService $aging)
    {
    }

    public function index(Request $request): View
    {
        $minDays = max(0, (int) $request->integer('min_days', 3));
        $constituencyCode = $request->string('constituency_code')->toString() ?: null;
        $rows = $this->aging->liveAging($constituencyCode, $minDays);

        return view('analytics.phase-aging', [
            'rows' => $rows,
            'minDays' => $minDays,
            'constituencyCode' => $constituencyCode,
        ]);
    }
}
