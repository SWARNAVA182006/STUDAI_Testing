<?php

declare(strict_types=1);

namespace App\Services\Ats\Providers;

use App\Models\AtsConnection;
use App\Services\Ats\BaseAtsProvider;
use Illuminate\Http\Client\PendingRequest;

/**
 * SAP SuccessFactors ATS Integration
 * API Documentation: https://api.sap.com/package/SABORC
 */
class SuccessFactorsService extends BaseAtsProvider
{
    protected string $baseUrl = ''; // Set dynamically per company

    public function getSlug(): string
    {
        return 'successfactors';
    }

    public function getName(): string
    {
        return 'SAP SuccessFactors';
    }

    public function getAuthType(): string
    {
        return 'oauth2';
    }

    protected function getBaseUrl(AtsConnection $connection): string
    {
        $credentials = $connection->getDecryptedCredentials();
        $apiServer = $credentials['api_server'] ?? 'api.successfactors.com';
        return "https://{$apiServer}/odata/v2";
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
        $apiServer = $credentials['api_server'] ?? 'api.successfactors.com';
        $clientId = $credentials['client_id'] ?? '';
        $redirectUri = route('ats.callback', ['provider' => 'successfactors']);

        return "https://{$apiServer}/oauth/authorize?" . http_build_query([
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
        ]);
    }

    public function handleOAuthCallback(AtsConnection $connection, string $code): array
    {
        $credentials = $connection->getDecryptedCredentials();
        $apiServer = $credentials['api_server'] ?? 'api.successfactors.com';

        $response = \Illuminate\Support\Facades\Http::asForm()->post(
            "https://{$apiServer}/oauth/token",
            [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => route('ats.callback', ['provider' => 'successfactors']),
                'client_id' => $credentials['client_id'] ?? '',
                'client_secret' => $credentials['client_secret'] ?? '',
            ]
        );

        return $response->json();
    }

    public function refreshTokens(AtsConnection $connection): array
    {
        $credentials = $connection->getDecryptedCredentials();
        $apiServer = $credentials['api_server'] ?? 'api.successfactors.com';

        $response = \Illuminate\Support\Facades\Http::asForm()->post(
            "https://{$apiServer}/oauth/token",
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
            $this->get($connection, 'User', ['$top' => 1]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getCandidates(AtsConnection $connection, array $filters = []): array
    {
        $this->baseUrl = $this->getBaseUrl($connection);
        $query = ['$top' => 100];

        if (!empty($filters['since'])) {
            $query['$filter'] = "lastModifiedDateTime gt datetime'" . $filters['since'] . "'";
        }

        return $this->paginateAll($connection, 'Candidate', $query, 'd');
    }

    public function getCandidate(AtsConnection $connection, string $externalId): ?array
    {
        try {
            $this->baseUrl = $this->getBaseUrl($connection);
            $response = $this->get($connection, "Candidate(candidateId={$externalId})");
            return $response['d'] ?? $response;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function createCandidate(AtsConnection $connection, array $data): array
    {
        $this->baseUrl = $this->getBaseUrl($connection);
        $payload = $this->mapCandidateToExternal($data);
        return $this->post($connection, 'Candidate', $payload);
    }

    public function updateCandidate(AtsConnection $connection, string $externalId, array $data): array
    {
        $this->baseUrl = $this->getBaseUrl($connection);
        $payload = $this->mapCandidateToExternal($data);
        return $this->patch($connection, "Candidate(candidateId={$externalId})", $payload);
    }

    public function getJobs(AtsConnection $connection, array $filters = []): array
    {
        $this->baseUrl = $this->getBaseUrl($connection);
        $query = ['$top' => 100];

        if (!empty($filters['status'])) {
            $query['$filter'] = "status eq '{$filters['status']}'";
        }

        return $this->paginateAll($connection, 'JobRequisition', $query, 'd');
    }

    public function getJob(AtsConnection $connection, string $externalId): ?array
    {
        try {
            $this->baseUrl = $this->getBaseUrl($connection);
            $response = $this->get($connection, "JobRequisition(jobReqId={$externalId})");
            return $response['d'] ?? $response;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function createJob(AtsConnection $connection, array $data): array
    {
        $this->baseUrl = $this->getBaseUrl($connection);
        $payload = $this->mapJobToExternal($data);
        return $this->post($connection, 'JobRequisition', $payload);
    }

    public function updateJob(AtsConnection $connection, string $externalId, array $data): array
    {
        $this->baseUrl = $this->getBaseUrl($connection);
        $payload = $this->mapJobToExternal($data);
        return $this->patch($connection, "JobRequisition(jobReqId={$externalId})", $payload);
    }

    public function getApplications(AtsConnection $connection, string $jobId): array
    {
        $this->baseUrl = $this->getBaseUrl($connection);
        return $this->paginateAll($connection, 'JobApplication', [
            '$filter' => "jobReqId eq '{$jobId}'",
        ], 'd');
    }

    public function createApplication(AtsConnection $connection, string $candidateId, string $jobId, array $data = []): array
    {
        $this->baseUrl = $this->getBaseUrl($connection);
        return $this->post($connection, 'JobApplication', [
            'jobReqId' => $jobId,
            'candidateId' => $candidateId,
            'source' => $data['source'] ?? 'StudAI Career',
        ]);
    }

    public function updateApplicationStatus(AtsConnection $connection, string $applicationId, string $status): array
    {
        $this->baseUrl = $this->getBaseUrl($connection);
        return $this->patch($connection, "JobApplication(applicationId={$applicationId})", [
            'status' => $status,
        ]);
    }

    public function registerWebhook(AtsConnection $connection, string $eventType, string $webhookUrl): array
    {
        // SuccessFactors uses Intelligent Services for event subscriptions
        return [
            'message' => 'SuccessFactors webhooks require configuration through Intelligent Services',
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
            'external_id' => $externalData['candidateId'] ?? null,
            'name' => trim(($externalData['firstName'] ?? '') . ' ' . ($externalData['lastName'] ?? '')),
            'first_name' => $externalData['firstName'] ?? null,
            'last_name' => $externalData['lastName'] ?? null,
            'email' => $externalData['primaryEmail'] ?? null,
            'phone' => $externalData['cellPhone'] ?? $externalData['homePhone'] ?? null,
            'city' => $externalData['city'] ?? null,
            'state' => $externalData['state'] ?? null,
            'country' => $externalData['country'] ?? null,
            'created_at' => $externalData['createdDateTime'] ?? null,
            'updated_at' => $externalData['lastModifiedDateTime'] ?? null,
        ];
    }

    public function mapCandidateToExternal(array $localData): array
    {
        $nameParts = explode(' ', $localData['name'] ?? '', 2);

        return [
            'firstName' => $localData['first_name'] ?? $nameParts[0] ?? null,
            'lastName' => $localData['last_name'] ?? $nameParts[1] ?? null,
            'primaryEmail' => $localData['email'] ?? null,
            'cellPhone' => $localData['phone'] ?? null,
            'city' => $localData['city'] ?? null,
            'state' => $localData['state'] ?? null,
            'country' => $localData['country'] ?? null,
        ];
    }

    public function mapJobToLocal(array $externalData): array
    {
        return [
            'external_id' => $externalData['jobReqId'] ?? null,
            'requisition_id' => $externalData['jobReqId'] ?? null,
            'title' => $externalData['jobTitle'] ?? $externalData['positionTitle'] ?? null,
            'description' => $externalData['jobDescription'] ?? null,
            'department' => $externalData['department'] ?? null,
            'location' => $externalData['location'] ?? null,
            'status' => $externalData['status'] ?? null,
            'created_at' => $externalData['createdDateTime'] ?? null,
            'updated_at' => $externalData['lastModifiedDateTime'] ?? null,
        ];
    }

    public function mapJobToExternal(array $localData): array
    {
        return [
            'jobTitle' => $localData['title'] ?? null,
            'positionTitle' => $localData['title'] ?? null,
            'jobDescription' => $localData['description'] ?? null,
            'department' => $localData['department'] ?? null,
            'location' => $localData['location'] ?? null,
        ];
    }

    public function getSupportedWebhookEvents(): array
    {
        return [
            'RCM_JOBREQUISITION_CREATED',
            'RCM_JOBREQUISITION_UPDATED',
            'RCM_JOBAPPLICATION_CREATED',
            'RCM_JOBAPPLICATION_UPDATED',
            'RCM_CANDIDATE_CREATED',
            'RCM_CANDIDATE_UPDATED',
            'RCM_OFFER_CREATED',
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
