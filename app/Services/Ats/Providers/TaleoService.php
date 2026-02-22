<?php

declare(strict_types=1);

namespace App\Services\Ats\Providers;

use App\Models\AtsConnection;
use App\Services\Ats\BaseAtsProvider;
use Illuminate\Http\Client\PendingRequest;

/**
 * Oracle Taleo ATS Integration
 * Note: Taleo uses REST APIs with OAuth 2.0 authentication
 */
class TaleoService extends BaseAtsProvider
{
    protected string $baseUrl = ''; // Set dynamically per company

    public function getSlug(): string
    {
        return 'taleo';
    }

    public function getName(): string
    {
        return 'Oracle Taleo';
    }

    public function getAuthType(): string
    {
        return 'oauth2';
    }

    protected function getBaseUrl(AtsConnection $connection): string
    {
        $credentials = $connection->getDecryptedCredentials();
        $companyCode = $credentials['company_code'] ?? '';
        $dataCenter = $credentials['data_center'] ?? 'ch';
        return "https://{$companyCode}.taleo.net/smartorg/rest";
    }

    protected function addAuthentication(PendingRequest $client, AtsConnection $connection): PendingRequest
    {
        $credentials = $connection->getDecryptedCredentials();
        $accessToken = $credentials['access_token'] ?? '';

        return $client->withHeaders([
            'Authorization' => "Bearer {$accessToken}",
        ]);
    }

    public function getAuthorizationUrl(AtsConnection $connection): ?string
    {
        $credentials = $connection->getDecryptedCredentials();
        $companyCode = $credentials['company_code'] ?? '';
        $clientId = $credentials['client_id'] ?? '';
        $redirectUri = route('ats.callback', ['provider' => 'taleo']);

        return "https://{$companyCode}.taleo.net/smartorg/oauth/authorize?" . http_build_query([
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
        ]);
    }

    public function handleOAuthCallback(AtsConnection $connection, string $code): array
    {
        $credentials = $connection->getDecryptedCredentials();
        $companyCode = $credentials['company_code'] ?? '';

        $response = \Illuminate\Support\Facades\Http::asForm()->post(
            "https://{$companyCode}.taleo.net/smartorg/oauth/token",
            [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => route('ats.callback', ['provider' => 'taleo']),
                'client_id' => $credentials['client_id'] ?? '',
                'client_secret' => $credentials['client_secret'] ?? '',
            ]
        );

        return $response->json();
    }

    public function refreshTokens(AtsConnection $connection): array
    {
        $credentials = $connection->getDecryptedCredentials();
        $companyCode = $credentials['company_code'] ?? '';

        $response = \Illuminate\Support\Facades\Http::asForm()->post(
            "https://{$companyCode}.taleo.net/smartorg/oauth/token",
            [
                'grant_type' => 'refresh_token',
                'refresh_token' => $credentials['refresh_token'] ?? '',
                'client_id' => $credentials['client_id'] ?? '',
                'client_secret' => $credentials['client_secret'] ?? '',
            ]
        );

        return $response->json();
    }

    public function testConnection(AtsConnection $connection): bool
    {
        try {
            $this->baseUrl = $this->getBaseUrl($connection);
            $this->get($connection, 'candidate/search', ['count' => 1]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getCandidates(AtsConnection $connection, array $filters = []): array
    {
        $this->baseUrl = $this->getBaseUrl($connection);
        $query = ['count' => 100];

        if (!empty($filters['status'])) {
            $query['status'] = $filters['status'];
        }

        if (!empty($filters['since'])) {
            $query['lastUpdatedDate'] = $filters['since'];
        }

        $searchResults = $this->get($connection, 'candidate/search', $query);
        $candidateIds = $searchResults['response']['candidates'] ?? [];

        $candidates = [];
        foreach (array_slice($candidateIds, 0, 100) as $candidate) {
            $candidateData = $this->getCandidate($connection, (string) $candidate['id']);
            if ($candidateData) {
                $candidates[] = $candidateData;
            }
        }

        return $candidates;
    }

    public function getCandidate(AtsConnection $connection, string $externalId): ?array
    {
        try {
            $this->baseUrl = $this->getBaseUrl($connection);
            $response = $this->get($connection, "candidate/{$externalId}");
            return $response['response']['candidate'] ?? $response;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function createCandidate(AtsConnection $connection, array $data): array
    {
        $this->baseUrl = $this->getBaseUrl($connection);
        $payload = $this->mapCandidateToExternal($data);
        return $this->post($connection, 'candidate', ['candidate' => $payload]);
    }

    public function updateCandidate(AtsConnection $connection, string $externalId, array $data): array
    {
        $this->baseUrl = $this->getBaseUrl($connection);
        $payload = $this->mapCandidateToExternal($data);
        return $this->put($connection, "candidate/{$externalId}", ['candidate' => $payload]);
    }

    public function getJobs(AtsConnection $connection, array $filters = []): array
    {
        $this->baseUrl = $this->getBaseUrl($connection);
        $query = ['count' => 100];

        if (!empty($filters['status'])) {
            $query['status'] = $filters['status'];
        }

        $searchResults = $this->get($connection, 'requisition/search', $query);
        $requisitionIds = $searchResults['response']['requisitions'] ?? [];

        $jobs = [];
        foreach (array_slice($requisitionIds, 0, 100) as $requisition) {
            $jobData = $this->getJob($connection, (string) $requisition['id']);
            if ($jobData) {
                $jobs[] = $jobData;
            }
        }

        return $jobs;
    }

    public function getJob(AtsConnection $connection, string $externalId): ?array
    {
        try {
            $this->baseUrl = $this->getBaseUrl($connection);
            $response = $this->get($connection, "requisition/{$externalId}");
            return $response['response']['requisition'] ?? $response;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function createJob(AtsConnection $connection, array $data): array
    {
        $this->baseUrl = $this->getBaseUrl($connection);
        $payload = $this->mapJobToExternal($data);
        return $this->post($connection, 'requisition', ['requisition' => $payload]);
    }

    public function updateJob(AtsConnection $connection, string $externalId, array $data): array
    {
        $this->baseUrl = $this->getBaseUrl($connection);
        $payload = $this->mapJobToExternal($data);
        return $this->put($connection, "requisition/{$externalId}", ['requisition' => $payload]);
    }

    public function getApplications(AtsConnection $connection, string $jobId): array
    {
        $this->baseUrl = $this->getBaseUrl($connection);
        $response = $this->get($connection, "requisition/{$jobId}/submissions");
        return $response['response']['submissions'] ?? [];
    }

    public function createApplication(AtsConnection $connection, string $candidateId, string $jobId, array $data = []): array
    {
        $this->baseUrl = $this->getBaseUrl($connection);
        return $this->post($connection, 'submission', [
            'submission' => [
                'candidate' => ['id' => $candidateId],
                'requisition' => ['id' => $jobId],
                'source' => $data['source'] ?? 'StudAI Career',
            ],
        ]);
    }

    public function updateApplicationStatus(AtsConnection $connection, string $applicationId, string $status): array
    {
        $this->baseUrl = $this->getBaseUrl($connection);
        return $this->put($connection, "submission/{$applicationId}/status", [
            'status' => $status,
        ]);
    }

    public function registerWebhook(AtsConnection $connection, string $eventType, string $webhookUrl): array
    {
        // Taleo webhooks require configuration through Taleo Connect
        return [
            'message' => 'Taleo webhooks require configuration through Taleo Connect Client',
            'event' => $eventType,
        ];
    }

    public function unregisterWebhook(AtsConnection $connection, string $webhookId): bool
    {
        return true;
    }

    public function parseWebhookPayload(array $payload, array $headers = []): array
    {
        return [
            'event_type' => $payload['eventType'] ?? null,
            'data' => $payload['data'] ?? $payload,
            'timestamp' => $payload['timestamp'] ?? null,
        ];
    }

    public function mapCandidateToLocal(array $externalData): array
    {
        return [
            'external_id' => (string) ($externalData['id'] ?? null),
            'name' => trim(($externalData['firstName'] ?? '') . ' ' . ($externalData['lastName'] ?? '')),
            'first_name' => $externalData['firstName'] ?? null,
            'last_name' => $externalData['lastName'] ?? null,
            'email' => $externalData['email'] ?? null,
            'phone' => $externalData['phoneNumber'] ?? null,
            'address' => $externalData['address'] ?? null,
            'city' => $externalData['city'] ?? null,
            'state' => $externalData['state'] ?? null,
            'country' => $externalData['country'] ?? null,
            'status' => $externalData['status'] ?? null,
            'source' => $externalData['source'] ?? null,
            'created_at' => $externalData['createdDate'] ?? null,
            'updated_at' => $externalData['lastUpdatedDate'] ?? null,
        ];
    }

    public function mapCandidateToExternal(array $localData): array
    {
        $nameParts = explode(' ', $localData['name'] ?? '', 2);

        return [
            'firstName' => $localData['first_name'] ?? $nameParts[0] ?? null,
            'lastName' => $localData['last_name'] ?? $nameParts[1] ?? null,
            'email' => $localData['email'] ?? null,
            'phoneNumber' => $localData['phone'] ?? null,
            'address' => $localData['address'] ?? null,
            'city' => $localData['city'] ?? null,
            'state' => $localData['state'] ?? null,
            'country' => $localData['country'] ?? null,
            'source' => $localData['source'] ?? 'StudAI Career',
        ];
    }

    public function mapJobToLocal(array $externalData): array
    {
        return [
            'external_id' => (string) ($externalData['id'] ?? null),
            'requisition_id' => $externalData['requisitionNumber'] ?? null,
            'title' => $externalData['title'] ?? null,
            'description' => $externalData['description'] ?? null,
            'location' => $externalData['location'] ?? null,
            'department' => $externalData['department'] ?? null,
            'job_family' => $externalData['jobFamily'] ?? null,
            'status' => $externalData['status'] ?? null,
            'openings' => $externalData['openings'] ?? null,
            'created_at' => $externalData['createdDate'] ?? null,
            'updated_at' => $externalData['lastUpdatedDate'] ?? null,
        ];
    }

    public function mapJobToExternal(array $localData): array
    {
        return [
            'title' => $localData['title'] ?? null,
            'description' => $localData['description'] ?? null,
            'location' => $localData['location'] ?? null,
            'department' => $localData['department'] ?? null,
            'jobFamily' => $localData['job_family'] ?? null,
            'openings' => $localData['openings'] ?? 1,
        ];
    }

    public function getSupportedWebhookEvents(): array
    {
        return [
            'candidate.created',
            'candidate.updated',
            'requisition.created',
            'requisition.updated',
            'requisition.filled',
            'submission.created',
            'submission.statusChanged',
            'offer.created',
            'offer.accepted',
        ];
    }

    public function getRateLimits(): array
    {
        return [
            'requests_per_minute' => 60,
            'requests_per_hour' => 1000,
        ];
    }
}
