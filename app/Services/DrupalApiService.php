<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

class DrupalApiService
{
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
     * Fetch a single application from the loans portal (Drupal) API.
     */
    public function getApplication(string $appId): ?array
    {
        $response = Http::withHeaders([
            'X-API-Key'    => $this->apiKey,
            'Accept'       => 'application/json',
        ])->timeout(30)->get("{$this->baseUrl}/api/v1/application/{$appId}");

        if (!$response->successful()) {
            Log::warning('Failed to fetch application from Drupal', [
                'application_id' => $appId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        $payload = $response->json();

        return is_array($payload['data'] ?? null) ? $payload['data'] : null;
    }

    /**
     * Update a single application's status in Drupal
     */
    // public function updateApplicationStatus(string $appId, array $data): Response
    // {
    //     return Http::withHeaders([
    //         'X-API-Key'     => $this->apiKey,
    //         'Content-Type'  => 'application/json',
    //         'Accept'        => 'application/json',
    //     ])->patch("{$this->baseUrl}/api/v1/lms/application/{$appId}", $data);
    // }
    public function updateApplicationStatus(string $appId, array $data): Response
    {
        $fullUrl = "{$this->baseUrl}/api/v1/lms/application/{$appId}";

        Log::debug('Sending PATCH to Drupal', [
            'url'      => $fullUrl,
            'method'   => 'PATCH',
            'headers'  => [
                'X-API-Key'    => substr($this->apiKey, 0, 8) . '...', // mask key
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ],
            'payload'  => $data,
        ]);

        $response = Http::withHeaders([
            'X-API-Key'     => $this->apiKey,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ])->patch($fullUrl, $data);

        Log::debug('Drupal response received', [
            'status' => $response->status(),
            'body'   => $response->body(),
        ]);

        return $response;
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
