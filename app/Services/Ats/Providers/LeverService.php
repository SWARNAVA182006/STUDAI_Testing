<?php

declare(strict_types=1);

namespace App\Services\Ats\Providers;

use App\Models\AtsConnection;
use App\Services\Ats\BaseAtsProvider;
use Illuminate\Http\Client\PendingRequest;

/**
 * Lever ATS Integration
 * API Documentation: https://hire.lever.co/developer/documentation
 */
class LeverService extends BaseAtsProvider
{
    protected string $baseUrl = 'https://api.lever.co/v1';

    public function getSlug(): string
    {
        return 'lever';
    }

    public function getName(): string
    {
        return 'Lever';
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
            $query['created_at_start'] = strtotime($filters['since']) * 1000;
        }

        if (!empty($filters['stage'])) {
            $query['stage_id'] = $filters['stage'];
        }

        return $this->paginateAll($connection, 'opportunities', $query);
    }

    public function getCandidate(AtsConnection $connection, string $externalId): ?array
    {
        try {
            $response = $this->get($connection, "opportunities/{$externalId}");
            return $response['data'] ?? $response;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function createCandidate(AtsConnection $connection, array $data): array
    {
        $payload = $this->mapCandidateToExternal($data);
        return $this->post($connection, 'opportunities', $payload);
    }

    public function updateCandidate(AtsConnection $connection, string $externalId, array $data): array
    {
        $payload = $this->mapCandidateToExternal($data);
        return $this->put($connection, "opportunities/{$externalId}", $payload);
    }

    public function getJobs(AtsConnection $connection, array $filters = []): array
    {
        $query = [];

        if (!empty($filters['state'])) {
            $query['state'] = $filters['state'];
        }

        return $this->paginateAll($connection, 'postings', $query);
    }

    public function getJob(AtsConnection $connection, string $externalId): ?array
    {
        try {
            $response = $this->get($connection, "postings/{$externalId}");
            return $response['data'] ?? $response;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function createJob(AtsConnection $connection, array $data): array
    {
        $payload = $this->mapJobToExternal($data);
        return $this->post($connection, 'postings', $payload);
    }

    public function updateJob(AtsConnection $connection, string $externalId, array $data): array
    {
        $payload = $this->mapJobToExternal($data);
        return $this->put($connection, "postings/{$externalId}", $payload);
    }

    public function getApplications(AtsConnection $connection, string $jobId): array
    {
        return $this->paginateAll($connection, "opportunities", ['posting_id' => $jobId]);
    }

    public function createApplication(AtsConnection $connection, string $candidateId, string $jobId, array $data = []): array
    {
        return $this->post($connection, "opportunities/{$candidateId}/addPostings", [
            'postings' => [$jobId],
        ]);
    }

    public function updateApplicationStatus(AtsConnection $connection, string $applicationId, string $status): array
    {
        return $this->post($connection, "opportunities/{$applicationId}/stage", [
            'stage' => $status,
        ]);
    }

    public function registerWebhook(AtsConnection $connection, string $eventType, string $webhookUrl): array
    {
        return $this->post($connection, 'webhooks', [
            'url' => $webhookUrl,
            'configuration' => [
                'conditions' => [
                    'events' => [$eventType],
                ],
            ],
        ]);
    }

    public function unregisterWebhook(AtsConnection $connection, string $webhookId): bool
    {
        return $this->delete($connection, "webhooks/{$webhookId}");
    }

    public function parseWebhookPayload(array $payload, array $headers = []): array
    {
        return [
            'event_type' => $payload['event'] ?? null,
            'data' => $payload['data'] ?? $payload,
            'timestamp' => $payload['triggeredAt'] ?? null,
        ];
    }

    public function mapCandidateToLocal(array $externalData): array
    {
        return [
            'external_id' => $externalData['id'] ?? null,
            'name' => $externalData['name'] ?? null,
            'email' => $externalData['emails'][0] ?? null,
            'phone' => $externalData['phones'][0]['value'] ?? null,
            'resume_url' => $externalData['resume']['file']['downloadUrl'] ?? null,
            'linkedin_url' => $externalData['links'][0] ?? null,
            'stage' => $externalData['stage']['text'] ?? null,
            'source' => $externalData['sources'][0] ?? null,
            'created_at' => isset($externalData['createdAt']) ? date('Y-m-d H:i:s', $externalData['createdAt'] / 1000) : null,
            'updated_at' => isset($externalData['updatedAt']) ? date('Y-m-d H:i:s', $externalData['updatedAt'] / 1000) : null,
        ];
    }

    public function mapCandidateToExternal(array $localData): array
    {
        return [
            'name' => $localData['name'] ?? null,
            'emails' => isset($localData['email']) ? [$localData['email']] : [],
            'phones' => isset($localData['phone']) ? [['value' => $localData['phone']]] : [],
            'links' => isset($localData['linkedin_url']) ? [$localData['linkedin_url']] : [],
            'sources' => isset($localData['source']) ? [$localData['source']] : ['StudAI Career'],
        ];
    }

    public function mapJobToLocal(array $externalData): array
    {
        return [
            'external_id' => $externalData['id'] ?? null,
            'title' => $externalData['text'] ?? null,
            'description' => $externalData['descriptionHtml'] ?? $externalData['description'] ?? null,
            'location' => $externalData['categories']['location'] ?? null,
            'department' => $externalData['categories']['department'] ?? null,
            'team' => $externalData['categories']['team'] ?? null,
            'employment_type' => $externalData['categories']['commitment'] ?? null,
            'state' => $externalData['state'] ?? null,
            'created_at' => isset($externalData['createdAt']) ? date('Y-m-d H:i:s', $externalData['createdAt'] / 1000) : null,
            'updated_at' => isset($externalData['updatedAt']) ? date('Y-m-d H:i:s', $externalData['updatedAt'] / 1000) : null,
        ];
    }

    public function mapJobToExternal(array $localData): array
    {
        return [
            'text' => $localData['title'] ?? null,
            'description' => $localData['description'] ?? null,
            'categories' => [
                'location' => $localData['location'] ?? null,
                'department' => $localData['department'] ?? null,
                'team' => $localData['team'] ?? null,
                'commitment' => $localData['employment_type'] ?? null,
            ],
        ];
    }

    public function getSupportedWebhookEvents(): array
    {
        return [
            'candidateCreated',
            'candidateStageChange',
            'candidateArchived',
            'candidateHired',
            'applicationCreated',
            'interviewCreated',
            'offerCreated',
        ];
    }

    public function getRateLimits(): array
    {
        return [
            'requests_per_minute' => 100,
            'requests_per_hour' => 6000,
        ];
    }
}
