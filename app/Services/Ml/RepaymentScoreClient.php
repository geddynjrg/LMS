<?php

namespace App\Services\Ml;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RepaymentScoreClient
{
    public function isEnabled(): bool
    {
        return (bool) config('ml.enabled', true) && filled(config('ml.url'));
    }

    /**
     * Score an LMS application (intake + optional earlywarn).
     *
     * @return array<string, mixed>|null
     */
    public function scoreIntake(string $applicationId): ?array
    {
        if (!$this->isEnabled() || $applicationId === '') {
            return null;
        }

        $cacheKey = 'ml_score_intake:' . $applicationId;
        $ttl = max(30, (int) config('ml.cache_seconds', 300));

        return Cache::remember($cacheKey, $ttl, function () use ($applicationId) {
            try {
                $response = Http::timeout((int) config('ml.timeout', 8))
                    ->acceptJson()
                    ->post(config('ml.url') . '/v1/score/intake', [
                        'application_id' => $applicationId,
                    ]);

                if (!$response->successful()) {
                    Log::warning('ML score intake failed', [
                        'application_id' => $applicationId,
                        'status' => $response->status(),
                        'body' => substr($response->body(), 0, 500),
                    ]);
                    return null;
                }

                return $response->json();
            } catch (\Throwable $e) {
                Log::warning('ML score intake exception', [
                    'application_id' => $applicationId,
                    'error' => $e->getMessage(),
                ]);
                return null;
            }
        });
    }

    /**
     * Persist ML payload under assessment_data without wiping rule-engine fields.
     */
    public function mergeIntoAssessmentData(?array $assessmentData, ?array $mlPayload): array
    {
        $data = is_array($assessmentData) ? $assessmentData : [];
        if (!$mlPayload) {
            return $data;
        }

        $data['ml_intake'] = $mlPayload['intake'] ?? null;
        $data['ml_earlywarn'] = $mlPayload['earlywarn'] ?? null;
        $data['ml_scored_at'] = $mlPayload['scored_at'] ?? now()->toIso8601String();
        $data['ml_model_version'] = $mlPayload['model_version'] ?? null;
        $data['ml_disclaimer'] = 'Uwezo ML (TransUnion-style scale — not a CRB report)';

        return $data;
    }
}
