<?php

declare(strict_types=1);

namespace App\Services\Ats\Providers;

use App\Models\AtsConnection;
use App\Services\Ats\BaseAtsProvider;
use Illuminate\Http\Client\PendingRequest;

/**
 * Greenhouse ATS Integration
 * API Documentation: https://developers.greenhouse.io/harvest.html
 */
class GreenhouseService extends BaseAtsProvider
{
    protected string $baseUrl = 'https://harvest.greenhouse.io/v1';

    public function getSlug(): string
    {
        return 'greenhouse';
    }

    public function getName(): string
    {
        return 'Greenhouse';
    }

    public function getAuthType(): string
    {
        return 'api_key';
    }

    protected function addAuthentication(PendingRequest $client, AtsConnection $connection): PendingRequest
    {
        $credentials = $connection->getDecryptedCredentials();
        $apiKey = $credentials['api_key'] ?? '';

        return $client->withBasicAuth($apiKey, '');
    }

    public function testConnection(AtsConnection $connection): bool
    {
        try {
            $this->get($connection, 'users');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getCandidates(AtsConnection $connection, array $filters = []): array
    {
        $query = [];

        if (!empty($filters['since'])) {
            $query['created_after'] = $filters['since'];
        }

        if (!empty($filters['job_id'])) {
            $query['job_id'] = $filters['job_id'];
        }

        return $this->paginateAll($connection, 'candidates', $query);
    }

    public function getCandidate(AtsConnection $connection, string $externalId): ?array
    {
        try {
            return $this->get($connection, "candidates/{$externalId}");
        } catch (\Exception $e) {
            return null;
        }
    }

    public function createCandidate(AtsConnection $connection, array $data): array
    {
        $payload = $this->mapCandidateToExternal($data);
        return $this->post($connection, 'candidates', $payload);
    }

    public function updateCandidate(AtsConnection $connection, string $externalId, array $data): array
    {
        $payload = $this->mapCandidateToExternal($data);
        return $this->patch($connection, "candidates/{$externalId}", $payload);
    }

    public function getJobs(AtsConnection $connection, array $filters = []): array
    {
        $query = [];

        if (!empty($filters['status'])) {
            $query['status'] = $filters['status'];
        }

        if (!empty($filters['department_id'])) {
            $query['department_id'] = $filters['department_id'];
        }

        return $this->paginateAll($connection, 'jobs', $query);
    }

    public function getJob(AtsConnection $connection, string $externalId): ?array
    {
        try {
            return $this->get($connection, "jobs/{$externalId}");
        } catch (\Exception $e) {
            return null;
        }
    }

    public function createJob(AtsConnection $connection, array $data): array
    {
        $payload = $this->mapJobToExternal($data);
        return $this->post($connection, 'jobs', $payload);
    }

    public function updateJob(AtsConnection $connection, string $externalId, array $data): array
    {
        $payload = $this->mapJobToExternal($data);
        return $this->patch($connection, "jobs/{$externalId}", $payload);
    }

    public function getApplications(AtsConnection $connection, string $jobId): array
    {
        return $this->paginateAll($connection, "applications", ['job_id' => $jobId]);
    }

    public function createApplication(AtsConnection $connection, string $candidateId, string $jobId, array $data = []): array
    {
        return $this->post($connection, 'applications', [
            'job_id' => (int) $jobId,
            'candidate_id' => (int) $candidateId,
            'source_id' => $data['source_id'] ?? null,
            'initial_stage_id' => $data['stage_id'] ?? null,
        ]);
    }

    public function updateApplicationStatus(AtsConnection $connection, string $applicationId, string $status): array
    {
        return $this->post($connection, "applications/{$applicationId}/move", [
            'from_stage_id' => null,
            'to_stage_id' => $status,
        ]);
    }

    public function registerWebhook(AtsConnection $connection, string $eventType, string $webhookUrl): array
    {
        // Greenhouse webhooks are configured through the UI, not API
        return [
            'message' => 'Greenhouse webhooks must be configured through the Greenhouse admin panel',
            'webhook_url' => $webhookUrl,
            'event' => $eventType,
        ];
    }

    public function unregisterWebhook(AtsConnection $connection, string $webhookId): bool
    {
        // Greenhouse webhooks are managed through the UI
        return true;
    }

    public function parseWebhookPayload(array $payload, array $headers = []): array
    {
        return [
            'event_type' => $payload['action'] ?? null,
            'data' => $payload['payload'] ?? $payload,
            'timestamp' => $headers['X-Greenhouse-Event-Timestamp'] ?? null,
        ];
    }

    public function mapCandidateToLocal(array $externalData): array
    {
        return [
            'external_id' => (string) ($externalData['id'] ?? null),
            'name' => trim(($externalData['first_name'] ?? '') . ' ' . ($externalData['last_name'] ?? '')),
            'first_name' => $externalData['first_name'] ?? null,
            'last_name' => $externalData['last_name'] ?? null,
            'email' => $externalData['email_addresses'][0]['value'] ?? null,
            'phone' => $externalData['phone_numbers'][0]['value'] ?? null,
            'resume_url' => $externalData['attachments'][0]['url'] ?? null,
            'linkedin_url' => $externalData['social_media_addresses'][0]['value'] ?? null,
            'company' => $externalData['company'] ?? null,
            'title' => $externalData['title'] ?? null,
            'created_at' => $externalData['created_at'] ?? null,
            'updated_at' => $externalData['updated_at'] ?? null,
        ];
    }

    public function mapCandidateToExternal(array $localData): array
    {
        $nameParts = explode(' ', $localData['name'] ?? '', 2);

        return [
            'first_name' => $localData['first_name'] ?? $nameParts[0] ?? null,
            'last_name' => $localData['last_name'] ?? $nameParts[1] ?? null,
            'email_addresses' => isset($localData['email']) ? [['value' => $localData['email'], 'type' => 'personal']] : [],
            'phone_numbers' => isset($localData['phone']) ? [['value' => $localData['phone'], 'type' => 'mobile']] : [],
            'social_media_addresses' => isset($localData['linkedin_url']) ? [['value' => $localData['linkedin_url']]] : [],
            'company' => $localData['company'] ?? null,
            'title' => $localData['title'] ?? null,
        ];
    }

    public function mapJobToLocal(array $externalData): array
    {
        return [
            'external_id' => (string) ($externalData['id'] ?? null),
            'title' => $externalData['name'] ?? null,
            'description' => $externalData['notes'] ?? null,
            'status' => $externalData['status'] ?? null,
            'confidential' => $externalData['confidential'] ?? false,
            'departments' => array_map(fn($d) => $d['name'], $externalData['departments'] ?? []),
            'offices' => array_map(fn($o) => $o['name'], $externalData['offices'] ?? []),
            'hiring_team' => $externalData['hiring_team'] ?? [],
            'created_at' => $externalData['created_at'] ?? null,
            'updated_at' => $externalData['updated_at'] ?? null,
        ];
    }

    public function mapJobToExternal(array $localData): array
    {
        return [
            'name' => $localData['title'] ?? null,
            'notes' => $localData['description'] ?? null,
            'requisition_id' => $localData['requisition_id'] ?? null,
            'department_id' => $localData['department_id'] ?? null,
            'office_ids' => $localData['office_ids'] ?? [],
        ];
    }

    public function getSupportedWebhookEvents(): array
    {
        return [
            'application_updated',
            'candidate_hired',
            'candidate_rejected',
            'candidate_unhired',
            'job_created',
            'job_updated',
            'offer_created',
            'offer_updated',
        ];
    }

    public function getRateLimits(): array
    {
        return [
            'requests_per_second' => 50,
            'requests_per_minute' => 250,
        ];
    }
}
