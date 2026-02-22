<?php

declare(strict_types=1);

namespace App\Services\Ats\Providers;

use App\Models\AtsConnection;
use App\Services\Ats\BaseAtsProvider;
use Illuminate\Http\Client\PendingRequest;

/**
 * Workday ATS Integration
 * Note: Workday uses SOAP/REST hybrid APIs and requires tenant-specific configuration.
 */
class WorkdayService extends BaseAtsProvider
{
    protected string $baseUrl = ''; // Set dynamically per tenant

    public function getSlug(): string
    {
        return 'workday';
    }

    public function getName(): string
    {
        return 'Workday';
    }

    public function getAuthType(): string
    {
        return 'oauth2';
    }

    protected function getBaseUrl(AtsConnection $connection): string
    {
        $credentials = $connection->getDecryptedCredentials();
        $tenant = $credentials['tenant'] ?? '';
        $dataCenter = $credentials['data_center'] ?? 'wd5';

        return "https://{$dataCenter}.myworkday.com/ccx/api/v1/{$tenant}";
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
        $tenant = $credentials['tenant'] ?? '';
        $dataCenter = $credentials['data_center'] ?? 'wd5';
        $redirectUri = route('ats.callback', ['provider' => 'workday']);

        return "https://{$dataCenter}.myworkday.com/ccx/oauth2/{$tenant}/authorize?" . http_build_query([
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scope' => 'staffing',
        ]);
    }

    public function handleOAuthCallback(AtsConnection $connection, string $code): array
    {
        $credentials = $connection->getDecryptedCredentials();
        $tenant = $credentials['tenant'] ?? '';
        $dataCenter = $credentials['data_center'] ?? 'wd5';

        $response = \Illuminate\Support\Facades\Http::asForm()->post(
            "https://{$dataCenter}.myworkday.com/ccx/oauth2/{$tenant}/token",
            [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => route('ats.callback', ['provider' => 'workday']),
                'client_id' => $credentials['client_id'] ?? '',
                'client_secret' => $credentials['client_secret'] ?? '',
            ]
        );

        return $response->json();
    }

    public function refreshTokens(AtsConnection $connection): array
    {
        $credentials = $connection->getDecryptedCredentials();
        $tenant = $credentials['tenant'] ?? '';
        $dataCenter = $credentials['data_center'] ?? 'wd5';

        $response = \Illuminate\Support\Facades\Http::asForm()->post(
            "https://{$dataCenter}.myworkday.com/ccx/oauth2/{$tenant}/token",
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
            $this->get($connection, 'workers');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getCandidates(AtsConnection $connection, array $filters = []): array
    {
        $this->baseUrl = $this->getBaseUrl($connection);
        return $this->paginateAll($connection, 'recruiting/jobApplications', $filters);
    }

    public function getCandidate(AtsConnection $connection, string $externalId): ?array
    {
        try {
            $this->baseUrl = $this->getBaseUrl($connection);
            return $this->get($connection, "recruiting/jobApplications/{$externalId}");
        } catch (\Exception $e) {
            return null;
        }
    }

    public function createCandidate(AtsConnection $connection, array $data): array
    {
        $this->baseUrl = $this->getBaseUrl($connection);
        $payload = $this->mapCandidateToExternal($data);
        return $this->post($connection, 'recruiting/jobApplications', $payload);
    }

    public function updateCandidate(AtsConnection $connection, string $externalId, array $data): array
    {
        $this->baseUrl = $this->getBaseUrl($connection);
        $payload = $this->mapCandidateToExternal($data);
        return $this->patch($connection, "recruiting/jobApplications/{$externalId}", $payload);
    }

    public function getJobs(AtsConnection $connection, array $filters = []): array
    {
        $this->baseUrl = $this->getBaseUrl($connection);
        return $this->paginateAll($connection, 'recruiting/jobRequisitions', $filters);
    }

    public function getJob(AtsConnection $connection, string $externalId): ?array
    {
        try {
            $this->baseUrl = $this->getBaseUrl($connection);
            return $this->get($connection, "recruiting/jobRequisitions/{$externalId}");
        } catch (\Exception $e) {
            return null;
        }
    }

    public function createJob(AtsConnection $connection, array $data): array
    {
        $this->baseUrl = $this->getBaseUrl($connection);
        $payload = $this->mapJobToExternal($data);
        return $this->post($connection, 'recruiting/jobRequisitions', $payload);
    }

    public function updateJob(AtsConnection $connection, string $externalId, array $data): array
    {
        $this->baseUrl = $this->getBaseUrl($connection);
        $payload = $this->mapJobToExternal($data);
        return $this->patch($connection, "recruiting/jobRequisitions/{$externalId}", $payload);
    }

    public function getApplications(AtsConnection $connection, string $jobId): array
    {
        $this->baseUrl = $this->getBaseUrl($connection);
        return $this->paginateAll($connection, "recruiting/jobRequisitions/{$jobId}/applications", []);
    }

    public function createApplication(AtsConnection $connection, string $candidateId, string $jobId, array $data = []): array
    {
        $this->baseUrl = $this->getBaseUrl($connection);
        return $this->post($connection, "recruiting/jobRequisitions/{$jobId}/apply", [
            'candidateId' => $candidateId,
            'source' => $data['source'] ?? 'StudAI Career',
        ]);
    }

    public function updateApplicationStatus(AtsConnection $connection, string $applicationId, string $status): array
    {
        $this->baseUrl = $this->getBaseUrl($connection);
        return $this->patch($connection, "recruiting/jobApplications/{$applicationId}/disposition", [
            'status' => $status,
        ]);
    }

    public function registerWebhook(AtsConnection $connection, string $eventType, string $webhookUrl): array
    {
        // Workday integrations typically use ISU (Integration System User) and scheduled reports
        return [
            'message' => 'Workday webhooks require ISU configuration through Workday Studio',
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
            'external_id' => $externalData['id'] ?? null,
            'name' => $externalData['name']['descriptor'] ?? null,
            'email' => $externalData['emailAddresses'][0]['emailAddress'] ?? null,
            'phone' => $externalData['phoneNumbers'][0]['phoneNumber'] ?? null,
            'status' => $externalData['status']['descriptor'] ?? null,
            'stage' => $externalData['stage']['descriptor'] ?? null,
            'created_at' => $externalData['effectiveDate'] ?? null,
        ];
    }

    public function mapCandidateToExternal(array $localData): array
    {
        return [
            'name' => ['descriptor' => $localData['name'] ?? null],
            'emailAddresses' => isset($localData['email']) ? [['emailAddress' => $localData['email']]] : [],
            'phoneNumbers' => isset($localData['phone']) ? [['phoneNumber' => $localData['phone']]] : [],
        ];
    }

    public function mapJobToLocal(array $externalData): array
    {
        return [
            'external_id' => $externalData['id'] ?? null,
            'requisition_id' => $externalData['requisitionID'] ?? null,
            'title' => $externalData['jobPostingTitle']['descriptor'] ?? null,
            'description' => $externalData['jobDescription'] ?? null,
            'location' => $externalData['primaryLocation']['descriptor'] ?? null,
            'status' => $externalData['status']['descriptor'] ?? null,
            'created_at' => $externalData['createdDate'] ?? null,
        ];
    }

    public function mapJobToExternal(array $localData): array
    {
        return [
            'jobPostingTitle' => ['descriptor' => $localData['title'] ?? null],
            'jobDescription' => $localData['description'] ?? null,
            'primaryLocation' => isset($localData['location']) ? ['descriptor' => $localData['location']] : null,
        ];
    }

    public function getSupportedWebhookEvents(): array
    {
        return [
            'job_requisition_created',
            'job_requisition_updated',
            'job_requisition_filled',
            'application_created',
            'application_status_changed',
            'offer_extended',
        ];
    }

    public function getRateLimits(): array
    {
        return [
            'requests_per_minute' => 30,
            'requests_per_hour' => 500,
        ];
    }
}
