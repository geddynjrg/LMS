<?php

return [
    'thresholds' => [
        // Contagion rule: Trigger high risk if member defaults meet ANY of these
        'contagion' => [
            'min_members' => 3,           // Absolute number of defaulter members
            'min_percentage' => 0.30,     // Percentage of total members (30%)
        ],

        // Score escalation when contagion triggers
        'contagion_escalation' => 40,     // Add this many points to cross-default score

        // Final override: Force 100% if contagion + score below this
        'contagion_force_high' => 70,     // If score < this, force to 100%

        // Amount bonus: Log scale cap (max bonus points)
        'amount_bonus_cap' => 20,

        // Multi-group bonus: Points per unique defaulted group (capped)
        'multi_group_bonus_per' => 5,
        'multi_group_bonus_cap' => 10,
    ],

    'categories' => [
        'high' => 70,
        'medium' => 30,
        'low' => 0,
    ],

    'policy' => [
        'any_arrears' => 100,         // Force 100% if current group has any arrears
    ],
];
