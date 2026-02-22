<?php

declare(strict_types=1);

namespace App\Services\Ats;

use App\Models\AtsConnection;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Base class for all ATS provider implementations.
 * Provides common functionality shared across providers.
 */
abstract class BaseAtsProvider implements AtsProviderInterface
{
    protected string $baseUrl = '';
    protected int $timeout = 30;
    protected int $retries = 3;

    /**
     * Create an authenticated HTTP client for the ATS.
     */
    protected function getHttpClient(AtsConnection $connection): PendingRequest
    {
        $client = Http::timeout($this->timeout)
            ->retry($this->retries, 100)
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]);

        return $this->addAuthentication($client, $connection);
    }

    /**
     * Add authentication to the HTTP client.
     */
    abstract protected function addAuthentication(PendingRequest $client, AtsConnection $connection): PendingRequest;

    /**
     * Get the full URL for an API endpoint.
     */
    protected function url(string $endpoint): string
    {
        return rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');
    }

    /**
     * Make a GET request to the ATS API.
     */
    protected function get(AtsConnection $connection, string $endpoint, array $query = []): array
    {
        try {
            $response = $this->getHttpClient($connection)
                ->get($this->url($endpoint), $query);

            if ($response->failed()) {
                Log::error("ATS API GET failed", [
                    'provider' => $this->getSlug(),
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \Exception("ATS API request failed: " . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error("ATS API GET exception", [
                'provider' => $this->getSlug(),
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Make a POST request to the ATS API.
     */
    protected function post(AtsConnection $connection, string $endpoint, array $data = []): array
    {
        try {
            $response = $this->getHttpClient($connection)
                ->post($this->url($endpoint), $data);

            if ($response->failed()) {
                Log::error("ATS API POST failed", [
                    'provider' => $this->getSlug(),
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \Exception("ATS API request failed: " . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error("ATS API POST exception", [
                'provider' => $this->getSlug(),
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Make a PUT request to the ATS API.
     */
    protected function put(AtsConnection $connection, string $endpoint, array $data = []): array
    {
        try {
            $response = $this->getHttpClient($connection)
                ->put($this->url($endpoint), $data);

            if ($response->failed()) {
                Log::error("ATS API PUT failed", [
                    'provider' => $this->getSlug(),
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \Exception("ATS API request failed: " . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error("ATS API PUT exception", [
                'provider' => $this->getSlug(),
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Make a PATCH request to the ATS API.
     */
    protected function patch(AtsConnection $connection, string $endpoint, array $data = []): array
    {
        try {
            $response = $this->getHttpClient($connection)
                ->patch($this->url($endpoint), $data);

            if ($response->failed()) {
                Log::error("ATS API PATCH failed", [
                    'provider' => $this->getSlug(),
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \Exception("ATS API request failed: " . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error("ATS API PATCH exception", [
                'provider' => $this->getSlug(),
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Make a DELETE request to the ATS API.
     */
    protected function delete(AtsConnection $connection, string $endpoint): bool
    {
        try {
            $response = $this->getHttpClient($connection)
                ->delete($this->url($endpoint));

            if ($response->failed()) {
                Log::error("ATS API DELETE failed", [
                    'provider' => $this->getSlug(),
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error("ATS API DELETE exception", [
                'provider' => $this->getSlug(),
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Default implementation for OAuth authorization URL (override in OAuth providers).
     */
    public function getAuthorizationUrl(AtsConnection $connection): ?string
    {
        return null;
    }

    /**
     * Default implementation for OAuth callback (override in OAuth providers).
     */
    public function handleOAuthCallback(AtsConnection $connection, string $code): array
    {
        return [];
    }

    /**
     * Default implementation for token refresh (override in OAuth providers).
     */
    public function refreshTokens(AtsConnection $connection): array
    {
        return [];
    }

    /**
     * Get the API rate limits for this provider.
     */
    public function getRateLimits(): array
    {
        return [
            'requests_per_minute' => 60,
            'requests_per_hour' => 1000,
        ];
    }

    /**
     * Paginate through all results from an API endpoint.
     */
    protected function paginateAll(AtsConnection $connection, string $endpoint, array $query = [], string $dataKey = 'data'): array
    {
        $allResults = [];
        $page = 1;
        $perPage = 100;

        do {
            $query['page'] = $page;
            $query['per_page'] = $perPage;

            $response = $this->get($connection, $endpoint, $query);
            $results = $response[$dataKey] ?? $response;

            if (empty($results)) {
                break;
            }

            $allResults = array_merge($allResults, $results);
            $page++;

            // Safety limit
            if ($page > 100) {
                break;
            }
        } while (count($results) === $perPage);

        return $allResults;
    }
}
