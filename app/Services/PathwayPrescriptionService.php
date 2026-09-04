<?php

namespace App\Services;

use App\Models\TempApplication;
use App\Services\Loan\LoanRecommendationService;

/**
 * Extends Core LMS recommendations with pathway next-steps.
 * Labour-export heuristics apply when destination appears in raw_data;
 * otherwise general loan processing steps are suggested.
 * Assistive only — does not auto-reject without officer confirmation.
 */
class PathwayPrescriptionService
{
    public const RULESET = 'core_pathway_prescription_v1';

    /** @var array<string, int> */
    private const DESTINATION_LOAN = [
        'saudi arabia' => 180000,
        'qatar' => 170000,
        'uae' => 160000,
        'kuwait' => 165000,
    ];

    public function __construct(
        protected LoanRecommendationService $loanRecommendation,
    ) {
    }

    /**
     * @param  array{min?:float|int,max?:float|int,balance?:float|int,can_borrow?:bool}  $eligibility
     * @param  array<string, mixed>|null  $risk
     * @return array<string, mixed>
     */
    public function prescribe(TempApplication $application, array $eligibility = [], ?array $risk = null): array
    {
        $base = $this->loanRecommendation->recommend($application, $eligibility);
        $raw = is_array($application->raw_data ?? null) ? $application->raw_data : [];
        $destination = $this->resolveDestination($raw);
        $trainingModules = [
            'Mandatory constituency loan training (schedule when authorised)',
            'Financial literacy / repayment planning',
        ];
        $nextSteps = [
            'Complete constituency / HQ review checklist items.',
            'Ensure GFS artefacts and phase documents are complete.',
            'Confirm member SMS phones for milestone alerts.',
            'Proceed to training only after secretariat authorisation.',
            'Disburse only after bank verification and officer confirmation.',
        ];

        $routeManual = (bool) ($base['should_decline'] ?? false);
        $riskReasons = [];
        if ($routeManual) {
            $riskReasons[] = 'Loan recommendation suggests decline — route for officer confirmation (not auto-reject).';
        }

        $riskLevel = strtolower((string) ($risk['level'] ?? $risk['risk_level'] ?? ''));
        if (in_array($riskLevel, ['high', 'critical', 'severe'], true)) {
            $routeManual = true;
            $riskReasons[] = 'Elevated group risk score — suggest manual compliance queue.';
        }

        $optimalExtra = null;
        if ($destination) {
            $perMember = self::DESTINATION_LOAN[$destination] ?? 150000;
            $optimalExtra = $perMember;
            $trainingModules = array_merge($trainingModules, [
                'Pre-departure orientation (labour-export)',
                'Visa / passport document readiness',
                'Flight logistics only after disbursement clearance',
            ]);
            $nextSteps[] = 'Verify destination corridor costs against heuristic KES '.number_format($perMember).' (assistive).';
            $nextSteps[] = 'Route high-risk overseas placements to manual compliance before offer.';
        }

        return array_merge($base, [
            'pathway_rule_set' => self::RULESET,
            'assistive' => true,
            'destination_country' => $destination,
            'optimal_destination_loan_kes' => $optimalExtra,
            'training_modules' => $trainingModules,
            'next_steps' => $nextSteps,
            'route_to_manual_compliance' => $routeManual,
            'risk_reasons' => $riskReasons,
            'disclaimer' => 'Assistive / recommend only. Officers confirm decline and compliance routing.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    protected function resolveDestination(array $raw): ?string
    {
        $candidates = [
            $raw['destination_country'] ?? null,
            $raw['country_of_destination'] ?? null,
            $raw['destination'] ?? null,
        ];
        foreach ($candidates as $c) {
            $v = strtolower(trim((string) $c));
            if ($v === '') {
                continue;
            }
            if (str_contains($v, 'saudi') || $v === 'ksa') {
                return 'saudi arabia';
            }
            if (str_contains($v, 'qatar')) {
                return 'qatar';
            }
            if (str_contains($v, 'uae') || str_contains($v, 'emirates') || str_contains($v, 'dubai')) {
                return 'uae';
            }
            if (str_contains($v, 'kuwait')) {
                return 'kuwait';
            }

            return $v;
        }

        return null;
    }
}
