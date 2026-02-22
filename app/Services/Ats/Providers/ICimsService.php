<?php

declare(strict_types=1);

namespace App\Services\Ats\Providers;

use App\Models\AtsConnection;
use App\Services\Ats\BaseAtsProvider;
use Illuminate\Http\Client\PendingRequest;

/**
 * iCIMS ATS Integration
 * API Documentation: https://developer.icims.com/
 */
class ICimsService extends BaseAtsProvider
{
    protected string $baseUrl = ''; // Set dynamically per customer

    public function getSlug(): string
    {
        return 'icims';
    }

    public function getName(): string
    {
        return 'iCIMS';
    }

    public function getAuthType(): string
    {
        return 'oauth2';
    }

    protected function getBaseUrl(AtsConnection $connection): string
    {
        $credentials = $connection->getDecryptedCredentials();
        $customerId = $credentials['customer_id'] ?? '';
        return "https://api.icims.com/customers/{$customerId}";
    }

    protected function addAuthentication(PendingRequest $client, AtsConnection $connection): PendingRequest
    {
        $credentials = $connection->getDecryptedCredentials();
        $accessToken = $credentials['access_token'] ?? '';

        return $client->withToken($accessToken);
    }

    public function getAuthorizationUrl(AtsConnection $connection): ?string
    {
        $credentials = $connection->getDecryptedCredentials();
        $clientId = $credentials['client_id'] ?? '';
        $redirectUri = route('ats.callback', ['provider' => 'icims']);

        return "https://auth.icims.com/oauth2/authorize?" . http_build_query([
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scope' => 'read write',
        ]);
    }

    public function handleOAuthCallback(AtsConnection $connection, string $code): array
    {
        $credentials = $connection->getDecryptedCredentials();

        $response = \Illuminate\Support\Facades\Http::asForm()->post(
            'https://auth.icims.com/oauth2/token',
            [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => route('ats.callback', ['provider' => 'icims']),
                'client_id' => $credentials['client_id'] ?? '',
                'client_secret' => $credentials['client_secret'] ?? '',
            ]
        );

        return $response->json();
    }

    public function refreshTokens(AtsConnection $connection): array
    {
        $credentials = $connection->getDecryptedCredentials();

        $response = \Illuminate\Support\Facades\Http::asForm()->post(
            'https://auth.icims.com/oauth2/token',
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
            $this->get($connection, 'search/people', ['searchrelationships' => 'applicantwf']);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getCandidates(AtsConnection $connection, array $filters = []): array
    {
        $this->baseUrl = $this->getBaseUrl($connection);
        $query = [
            'searchrelationships' => 'applicantwf',
        ];

        if (!empty($filters['updated_since'])) {
            $query['modifydate'] = $filters['updated_since'];
        }

        $searchResults = $this->get($connection, 'search/people', $query);
        $personIds = $searchResults['searchResults'] ?? [];

        $candidates = [];
        foreach (array_slice($personIds, 0, 100) as $person) {
            $candidate = $this->getCandidate($connection, (string) $person['id']);
            if ($candidate) {
                $candidates[] = $candidate;
            }
        }

        return $candidates;
    }

    public function getCandidate(AtsConnection $connection, string $externalId): ?array
    {
        try {
            $this->baseUrl = $this->getBaseUrl($connection);
            return $this->get($connection, "people/{$externalId}");
        } catch (\Exception $e) {
            return null;
        }
    }

    public function createCandidate(AtsConnection $connection, array $data): array
    {
        $this->baseUrl = $this->getBaseUrl($connection);
        $payload = $this->mapCandidateToExternal($data);
        return $this->post($connection, 'people', $payload);
    }

    public function updateCandidate(AtsConnection $connection, string $externalId, array $data): array
    {
        $this->baseUrl = $this->getBaseUrl($connection);
        $payload = $this->mapCandidateToExternal($data);
        return $this->patch($connection, "people/{$externalId}", $payload);
    }

    public function getJobs(AtsConnection $connection, array $filters = []): array
    {
        $this->baseUrl = $this->getBaseUrl($connection);
        $query = [];

        if (!empty($filters['status'])) {
            $query['status'] = $filters['status'];
        }

        $searchResults = $this->get($connection, 'search/jobs', $query);
        $jobIds = $searchResults['searchResults'] ?? [];

        $jobs = [];
        foreach (array_slice($jobIds, 0, 100) as $job) {
            $jobData = $this->getJob($connection, (string) $job['id']);
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
            return $this->get($connection, "jobs/{$externalId}");
        } catch (\Exception $e) {
            return null;
        }
    }

    public function createJob(AtsConnection $connection, array $data): array
    {
        $this->baseUrl = $this->getBaseUrl($connection);
        $payload = $this->mapJobToExternal($data);
        return $this->post($connection, 'jobs', $payload);
    }

    public function updateJob(AtsConnection $connection, string $externalId, array $data): array
    {
        $this->baseUrl = $this->getBaseUrl($connection);
        $payload = $this->mapJobToExternal($data);
        return $this->patch($connection, "jobs/{$externalId}", $payload);
    }

    public function getApplications(AtsConnection $connection, string $jobId): array
    {
        $this->baseUrl = $this->getBaseUrl($connection);
        $response = $this->get($connection, "jobs/{$jobId}/applicantworkflows");
        return $response['applicantWorkflows'] ?? [];
    }

    public function createApplication(AtsConnection $connection, string $candidateId, string $jobId, array $data = []): array
    {
        $this->baseUrl = $this->getBaseUrl($connection);
        return $this->post($connection, 'applicantworkflows', [
            'person' => ['id' => $candidateId],
            'job' => ['id' => $jobId],
            'source' => $data['source'] ?? ['value' => 'StudAI Career'],
        ]);
    }

    public function updateApplicationStatus(AtsConnection $connection, string $applicationId, string $status): array
    {
        $this->baseUrl = $this->getBaseUrl($connection);
        return $this->patch($connection, "applicantworkflows/{$applicationId}", [
            'status' => ['id' => $status],
        ]);
    }

    public function registerWebhook(AtsConnection $connection, string $eventType, string $webhookUrl): array
    {
        $this->baseUrl = $this->getBaseUrl($connection);
        return $this->post($connection, 'subscriptions', [
            'url' => $webhookUrl,
            'eventType' => $eventType,
        ]);
    }

    public function unregisterWebhook(AtsConnection $connection, string $webhookId): bool
    {
        $this->baseUrl = $this->getBaseUrl($connection);
        return $this->delete($connection, "subscriptions/{$webhookId}");
    }

    public function parseWebhookPayload(array $payload, array $headers = []): array
    {
        return [
            'event_type' => $payload['eventType'] ?? null,
            'data' => $payload['payload'] ?? $payload,
            'timestamp' => $payload['timestamp'] ?? null,
        ];
    }

    public function mapCandidateToLocal(array $externalData): array
    {
        return [
            'external_id' => (string) ($externalData['id'] ?? null),
            'name' => trim(($externalData['firstname'] ?? '') . ' ' . ($externalData['lastname'] ?? '')),
            'first_name' => $externalData['firstname'] ?? null,
            'last_name' => $externalData['lastname'] ?? null,
            'email' => $externalData['email'] ?? $externalData['emails'][0]['address'] ?? null,
            'phone' => $externalData['phones'][0]['number'] ?? null,
            'address' => $externalData['addresses'][0] ?? null,
            'source' => $externalData['source']['value'] ?? null,
            'created_at' => $externalData['createdate'] ?? null,
            'updated_at' => $externalData['modifydate'] ?? null,
        ];
    }

    public function mapCandidateToExternal(array $localData): array
    {
        $nameParts = explode(' ', $localData['name'] ?? '', 2);

        return [
            'firstname' => $localData['first_name'] ?? $nameParts[0] ?? null,
            'lastname' => $localData['last_name'] ?? $nameParts[1] ?? null,
            'emails' => isset($localData['email']) ? [['address' => $localData['email'], 'type' => 'Personal']] : [],
            'phones' => isset($localData['phone']) ? [['number' => $localData['phone'], 'type' => 'Cell']] : [],
            'source' => ['value' => $localData['source'] ?? 'StudAI Career'],
        ];
    }

    public function mapJobToLocal(array $externalData): array
    {
        return [
            'external_id' => (string) ($externalData['id'] ?? null),
            'title' => $externalData['jobtitle'] ?? null,
            'description' => $externalData['overview'] ?? null,
            'department' => $externalData['department']['value'] ?? null,
            'location' => $externalData['location']['value'] ?? null,
            'hiring_manager' => $externalData['hiringmanager']['value'] ?? null,
            'status' => $externalData['status']['value'] ?? null,
            'posted_date' => $externalData['posteddate'] ?? null,
            'created_at' => $externalData['createdate'] ?? null,
            'updated_at' => $externalData['modifydate'] ?? null,
        ];
    }

    public function mapJobToExternal(array $localData): array
    {
        return [
            'jobtitle' => $localData['title'] ?? null,
            'overview' => $localData['description'] ?? null,
            'department' => isset($localData['department_id']) ? ['id' => $localData['department_id']] : null,
            'location' => isset($localData['location_id']) ? ['id' => $localData['location_id']] : null,
        ];
    }

    public function getSupportedWebhookEvents(): array
    {
        return [
            'person.created',
            'person.updated',
            'job.created',
            'job.updated',
            'applicantworkflow.created',
            'applicantworkflow.updated',
            'applicantworkflow.statuschanged',
        ];
    }

    public function getRateLimits(): array
    {
        return [
            'requests_per_minute' => 100,
            'requests_per_hour' => 5000,
        ];
    }
}
