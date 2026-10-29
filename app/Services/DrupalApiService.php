<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

class DrupalApiService
{
    use DrupalMemberOtpFallback;

    protected ?string $baseUrl;
    protected ?string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(
            (string) (config('drupal.base_url') ?: env('DRUPAL_API_URL', 'https://loans-staging-admin.uwezo.go.ke')),
            '/'
        );
        $this->apiKey  = config('drupal.api_key', env('DRUPAL_API_KEY'));

        if (empty($this->baseUrl)) {
            throw new \RuntimeException('Drupal API base URL is not configured');
        }
        if (empty($this->apiKey)) {
            throw new \RuntimeException('Drupal API key is not configured');
        }
    }

    /**
     * Drupal bases to query (primary + peers such as prod-admin).
     *
     * @return list<string>
     */
    public function drupalBases(): array
    {
        return $this->drupalOtpFallbackBases();
    }

    /**
     * Fetch a single application from the loans portal (Drupal) API.
     * Tries the primary host, then configured peer hosts (staging/prod).
     */
    public function getApplication(string $appId): ?array
    {
        $lastStatus = null;
        $lastBody = null;

        foreach ($this->drupalBases() as $base) {
            $response = Http::withHeaders([
                'X-API-Key'    => $this->apiKey,
                'Accept'       => 'application/json',
            ])->timeout(30)->get("{$base}/api/v1/application/{$appId}");

            $lastStatus = $response->status();
            $lastBody = $response->body();

            if (!$response->successful()) {
                continue;
            }

            $payload = $response->json();
            if (is_array($payload['data'] ?? null)) {
                $data = $payload['data'];
                $data['_drupal_base'] = $base;

                return $data;
            }
        }

        Log::warning('Failed to fetch application from Drupal', [
            'application_id' => $appId,
            'status' => $lastStatus,
            'body' => $lastBody,
        ]);

        return null;
    }

    /**
     * Update a single application's status in Drupal.
     * When the app was found on a peer, prefer that base; also fan-out draft returns.
     */
    public function updateApplicationStatus(string $appId, array $data, ?string $preferredBase = null): Response
    {
        $bases = $this->drupalBases();
        if ($preferredBase) {
            $preferredBase = rtrim($preferredBase, '/');
            $bases = array_values(array_unique(array_merge([$preferredBase], $bases)));
        }

        $targetStatus = $data['status'] ?? null;
        $fanOut = $targetStatus === 'draft';

        $lastResponse = null;
        $succeeded = false;

        foreach ($bases as $base) {
            $fullUrl = "{$base}/api/v1/lms/application/{$appId}";

            Log::debug('Sending PATCH to Drupal', [
                'url'      => $fullUrl,
                'method'   => 'PATCH',
                'payload'  => $data,
            ]);

            $response = Http::withHeaders([
                'X-API-Key'     => $this->apiKey,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ])->patch($fullUrl, $data);

            Log::debug('Drupal response received', [
                'url' => $fullUrl,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            $lastResponse = $response;

            if ($this->isSuccessful($response)) {
                $succeeded = true;
                if (!$fanOut) {
                    return $response;
                }
            } elseif ($response->status() === 404 && $fanOut) {
                continue;
            } elseif (!$fanOut) {
                return $response;
            }
        }

        if ($succeeded && $lastResponse) {
            // Prefer a successful response object for callers.
            return $lastResponse;
        }

        return $lastResponse ?? Http::response(['error' => 'No Drupal host available'], 503);
    }

    /**
     * Batch update multiple application statuses
     */
    public function batchUpdateStatuses(array $updates): Response
    {
        return Http::withHeaders([
            'X-API-Key'     => $this->apiKey,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ])->post("{$this->baseUrl}/api/v1/lms/applications/status-batch", [
            'updates' => $updates,
        ]);
    }

    /**
     * Check if response was successful
     */
    public function isSuccessful(Response $response): bool
    {
        return $response->successful() && ($response->json('success') ?? false);
    }
}
