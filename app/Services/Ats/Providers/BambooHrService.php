<?php

declare(strict_types=1);

namespace App\Services\Ats\Providers;

use App\Models\AtsConnection;
use App\Services\Ats\BaseAtsProvider;
use Illuminate\Http\Client\PendingRequest;

/**
 * BambooHR ATS Integration
 * API Documentation: https://documentation.bamboohr.com/reference
 */
class BambooHrService extends BaseAtsProvider
{
    protected string $baseUrl = ''; // Set dynamically per company

    public function getSlug(): string
    {
        return 'bamboohr';
    }

    public function getName(): string
    {
        return 'BambooHR';
    }

    public function getAuthType(): string
    {
        return 'api_key';
    }

    protected function getBaseUrl(AtsConnection $connection): string
    {
        $credentials = $connection->getDecryptedCredentials();
        $subdomain = $credentials['subdomain'] ?? '';
        return "https://api.bamboohr.com/api/gateway.php/{$subdomain}/v1";
    }

    protected function addAuthentication(PendingRequest $client, AtsConnection $connection): PendingRequest
    {
        $credentials = $connection->getDecryptedCredentials();
        $apiKey = $credentials['api_key'] ?? '';

        return $client->withBasicAuth($apiKey, 'x');
    }

    public function testConnection(AtsConnection $connection): bool
    {
        try {
            $this->baseUrl = $this->getBaseUrl($connection);
            $this->get($connection, 'employees/directory');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getCandidates(AtsConnection $connection, array $filters = []): array
    {
        $this->baseUrl = $this->getBaseUrl($connection);
        $query = [];

        if (!empty($filters['status'])) {
            $query['statusId'] = $filters['status'];
        }

        if (!empty($filters['job_id'])) {
            $query['jobId'] = $filters['job_id'];
        }

        $response = $this->get($connection, 'applicant_tracking/applications', $query);
        return $response['applications'] ?? $response;
    }

    public function getCandidate(AtsConnection $connection, string $externalId): ?array
    {
        try {
            $this->baseUrl = $this->getBaseUrl($connection);
            return $this->get($connection, "applicant_tracking/applications/{$externalId}");
        } catch (\Exception $e) {
            return null;
        }
    }

    public function createCandidate(AtsConnection $connection, array $data): array
    {
        $this->baseUrl = $this->getBaseUrl($connection);
        $payload = $this->mapCandidateToExternal($data);
        return $this->post($connection, 'applicant_tracking/applications', $payload);
    }

    public function updateCandidate(AtsConnection $connection, string $externalId, array $data): array
    {
        $this->baseUrl = $this->getBaseUrl($connection);
        $payload = $this->mapCandidateToExternal($data);
        return $this->put($connection, "applicant_tracking/applications/{$externalId}", $payload);
    }

    public function getJobs(AtsConnection $connection, array $filters = []): array
    {
        $this->baseUrl = $this->getBaseUrl($connection);
        $query = [];

        if (!empty($filters['status'])) {
            $query['status'] = $filters['status'];
        }

        $response = $this->get($connection, 'applicant_tracking/jobs', $query);
        return $response['jobs'] ?? $response;
    }

    public function getJob(AtsConnection $connection, string $externalId): ?array
    {
        try {
            $this->baseUrl = $this->getBaseUrl($connection);
            return $this->get($connection, "applicant_tracking/jobs/{$externalId}");
        } catch (\Exception $e) {
            return null;
        }
    }

    public function createJob(AtsConnection $connection, array $data): array
    {
        $this->baseUrl = $this->getBaseUrl($connection);
        $payload = $this->mapJobToExternal($data);
        return $this->post($connection, 'applicant_tracking/jobs', $payload);
    }

    public function updateJob(AtsConnection $connection, string $externalId, array $data): array
    {
        $this->baseUrl = $this->getBaseUrl($connection);
        $payload = $this->mapJobToExternal($data);
        return $this->put($connection, "applicant_tracking/jobs/{$externalId}", $payload);
    }

    public function getApplications(AtsConnection $connection, string $jobId): array
    {
        $this->baseUrl = $this->getBaseUrl($connection);
        return $this->getCandidates($connection, ['job_id' => $jobId]);
    }

    public function createApplication(AtsConnection $connection, string $candidateId, string $jobId, array $data = []): array
    {
        $this->baseUrl = $this->getBaseUrl($connection);
        return $this->post($connection, 'applicant_tracking/applications', [
            'applicantId' => $candidateId,
            'jobId' => $jobId,
            'source' => $data['source'] ?? 'StudAI Career',
        ]);
    }

    public function updateApplicationStatus(AtsConnection $connection, string $applicationId, string $status): array
    {
        $this->baseUrl = $this->getBaseUrl($connection);
        return $this->put($connection, "applicant_tracking/applications/{$applicationId}/status", [
            'statusId' => $status,
        ]);
    }

    public function registerWebhook(AtsConnection $connection, string $eventType, string $webhookUrl): array
    {
        $this->baseUrl = $this->getBaseUrl($connection);
        return $this->post($connection, 'webhooks', [
            'url' => $webhookUrl,
            'events' => [$eventType],
        ]);
    }

    public function unregisterWebhook(AtsConnection $connection, string $webhookId): bool
    {
        $this->baseUrl = $this->getBaseUrl($connection);
        return $this->delete($connection, "webhooks/{$webhookId}");
    }

    public function parseWebhookPayload(array $payload, array $headers = []): array
    {
        return [
            'event_type' => $payload['type'] ?? null,
            'data' => $payload['data'] ?? $payload,
            'timestamp' => $payload['timestamp'] ?? null,
        ];
    }

    public function mapCandidateToLocal(array $externalData): array
    {
        return [
            'external_id' => (string) ($externalData['id'] ?? $externalData['applicationId'] ?? null),
            'name' => trim(($externalData['firstName'] ?? '') . ' ' . ($externalData['lastName'] ?? '')),
            'first_name' => $externalData['firstName'] ?? null,
            'last_name' => $externalData['lastName'] ?? null,
            'email' => $externalData['email'] ?? null,
            'phone' => $externalData['phone'] ?? null,
            'resume_url' => $externalData['resumeUrl'] ?? null,
            'status' => $externalData['status']['label'] ?? $externalData['status'] ?? null,
            'job_title' => $externalData['jobTitle'] ?? null,
            'applied_date' => $externalData['appliedDate'] ?? null,
            'created_at' => $externalData['dateCreated'] ?? null,
            'updated_at' => $externalData['dateModified'] ?? null,
        ];
    }

    public function mapCandidateToExternal(array $localData): array
    {
        $nameParts = explode(' ', $localData['name'] ?? '', 2);

        return [
            'firstName' => $localData['first_name'] ?? $nameParts[0] ?? null,
            'lastName' => $localData['last_name'] ?? $nameParts[1] ?? null,
            'email' => $localData['email'] ?? null,
            'phone' => $localData['phone'] ?? null,
            'source' => $localData['source'] ?? 'StudAI Career',
        ];
    }

    public function mapJobToLocal(array $externalData): array
    {
        return [
            'external_id' => (string) ($externalData['id'] ?? null),
            'title' => $externalData['title'] ?? null,
            'description' => $externalData['description'] ?? null,
            'department' => $externalData['department']['label'] ?? $externalData['department'] ?? null,
            'location' => $externalData['location']['city'] ?? null,
            'employment_type' => $externalData['employmentType'] ?? null,
            'minimum_experience' => $externalData['minimumExperience'] ?? null,
            'status' => $externalData['status']['label'] ?? $externalData['status'] ?? null,
            'created_at' => $externalData['dateCreated'] ?? null,
            'updated_at' => $externalData['dateModified'] ?? null,
        ];
    }

    public function mapJobToExternal(array $localData): array
    {
        return [
            'title' => $localData['title'] ?? null,
            'description' => $localData['description'] ?? null,
            'departmentId' => $localData['department_id'] ?? null,
            'locationId' => $localData['location_id'] ?? null,
            'employmentType' => $localData['employment_type'] ?? null,
            'minimumExperience' => $localData['minimum_experience'] ?? null,
        ];
    }

    public function getSupportedWebhookEvents(): array
    {
        return [
            'employee.created',
            'employee.updated',
            'application.created',
            'application.updated',
            'application.statusChanged',
            'job.created',
            'job.updated',
        ];
    }

    public function getRateLimits(): array
    {
        return [
            'requests_per_minute' => 50,
            'requests_per_hour' => 500,
        ];
    }
}
