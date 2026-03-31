<?php

return [
    'thresholds' => [
        'contagion' => [
            'warning_members'     => 2,          // early alert / medium risk
            'warning_percentage'  => 0.10,       // 10%

            'high_members'        => 3,          // high risk trigger
            'high_percentage'     => 0.20,       // 20% (lowered from 30% for sensitivity)

            'critical_members'    => 5,          // very high / force 100
            'critical_percentage' => 0.30,

            'small_group_threshold' => 15,       // groups ≤15 members use tighter rules
            'small_group_high_members' => 2,
        ],

        // Severity scaling (instead of fixed escalation)
        'contagion_base_score' => 40,            // Starting point when threshold met
        'contagion_max_score'  => 100,

        // External arrears severity multiplier (per defaulter average)
        'external_arrears_severity' => [
            'multiplier_low'    => 1.0,          // < KES 50k avg
            'multiplier_medium' => 1.5,          // KES 50k–150k
            'multiplier_high'   => 2.0,          // KES 150k–400k
            'multiplier_extreme' => 2.5,         // >400k avg per defaulter
            'thresholds_kes'    => [50000, 150000, 400000],
        ],

        // Multi-group defaults — stronger signal
        'multi_group_bonus_per_unique_group' => 8,
        'multi_group_bonus_max'              => 24,   // e.g. 4+ groups → +24

        // Group size adjustment (smaller groups penalized more)
        'size_penalty_below_members' => 15,
        'size_penalty_factor'        => 1.3,     // 30% uplift for small groups
    ],

    'contagion' => [
        'base_score_on_trigger' => 40,           // starting point when warning/high triggered
        'severity_multipliers' => [               // avg external arrears per defaulter (KES)
            'low'     => 1.0,     // < 50k
            'medium'  => 1.5,     // 50k–150k
            'high'    => 2.0,     // 150k–400k
            'extreme' => 2.5,     // >400k
        ],
        'severity_thresholds_kes' => [50000, 150000, 400000],

        'multi_group_bonus_per_unique' => 8,     // per member who defaulted in >1 group
        'multi_group_bonus_max'        => 24,    // cap total uplift  // e.g. 4+ groups → +24

        'size_penalty_factor'          => 1.4,   // 40% uplift for small groups
    ],

    'categories' => [
        'critical' => ['min' => 80, 'label' => 'Critical Risk', 'color' => 'danger'],
        'high'     => ['min' => 50, 'label' => 'High Risk',     'color' => 'orange'],
        'medium'   => ['min' => 20, 'label' => 'Medium Risk',   'color' => 'warning'],
        'low'      => ['min' => 1,  'label' => 'Low Risk',      'color' => 'info'],
        'none'     => ['min' => 0,  'label' => 'No Risk',       'color' => 'success'],
    ],

    'policy' => [
        'any_arrears' => 100,
        'financial_hard_block_above_ratio' => 0.10,
    ],

    'financial' => [
        'buckets' => [
            // CRITICAL: 50%+ of loan is in arrears. Complete stop.
            ['min_ratio' => 0.50, 'score' => 100, 'severity' => 'Critical',    'category' => 'High Risk',   'color' => 'danger'],

            // SEVERE: 30% - 50%. Significant history of default.
            ['min_ratio' => 0.30, 'score' => 80,  'severity' => 'Severe',      'category' => 'High Risk',   'color' => 'danger'],

            // ELEVATED: 15% - 30%.
            ['min_ratio' => 0.15, 'score' => 60,  'severity' => 'Elevated',    'category' => 'Medium Risk', 'color' => 'warning'],

            // MINIMAL: 1% - 15%. Minor late payments or small balances.
            ['min_ratio' => 0.001, 'score' => 30,  'severity' => 'Noticeable',  'category' => 'Medium Risk', 'color' => 'info'],

            // CLEAN: 0% Arrears
            ['min_ratio' => 0.00, 'score' => 0,   'severity' => 'Negligible',  'category' => 'Low Risk',    'color' => 'success'],
        ],
    ],

    'historical' => [
        'base_weight'          => 1.0,
        'months_per_point'     => 4.1667,   // 100 / 24 = 100 points
        'severity_cap_per_loan'=> 100,      // allow one very bad loan to reach full score
        'recency_floor'        => 0.3,      // older loans contribution
        'half_life_months'     => 36,       // 3-year half-life
    ],

    'member_exposure' => [
        'buckets' => [
            ['min_avg_kes' => 500000, 'score' => 100, 'severity' => 'Extreme',   'category' => 'High Risk'],
            ['min_avg_kes' => 300000, 'score' => 100, 'severity' => 'Severe',    'category' => 'High Risk'],
            ['min_avg_kes' => 150000, 'score' =>  90, 'severity' => 'High',      'category' => 'High Risk'],
            ['min_avg_kes' =>  80000, 'score' =>  70, 'severity' => 'Moderate',  'category' => 'High Risk'],
            ['min_avg_kes' =>  30000, 'score' =>  50, 'severity' => 'Elevated',  'category' => 'Medium Risk'],
            ['min_avg_kes' =>      1, 'score' =>  30, 'severity' => 'Present',   'category' => 'Medium Risk'],
        ],
        // Optional fallback if no buckets defined
        'default_score_per_100k' => 25,   // e.g. 200k avg → 50 points
    ],
];
