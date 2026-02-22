<?php

declare(strict_types=1);

namespace App\Services\Ats;

use App\Models\AtsConnection;

/**
 * Interface for all ATS provider implementations.
 * Each ATS provider (Lever, Greenhouse, etc.) must implement this interface.
 */
interface AtsProviderInterface
{
    /**
     * Get the provider slug/identifier.
     */
    public function getSlug(): string;

    /**
     * Get the provider display name.
     */
    public function getName(): string;

    /**
     * Get the authentication type (oauth2, api_key, basic).
     */
    public function getAuthType(): string;

    /**
     * Get the OAuth authorization URL (for OAuth providers).
     */
    public function getAuthorizationUrl(AtsConnection $connection): ?string;

    /**
     * Handle OAuth callback and exchange code for tokens.
     */
    public function handleOAuthCallback(AtsConnection $connection, string $code): array;

    /**
     * Refresh OAuth tokens if expired.
     */
    public function refreshTokens(AtsConnection $connection): array;

    /**
     * Test the connection to the ATS.
     */
    public function testConnection(AtsConnection $connection): bool;

    /**
     * Get all candidates from the ATS.
     *
     * @return array Array of candidate data
     */
    public function getCandidates(AtsConnection $connection, array $filters = []): array;

    /**
     * Get a specific candidate from the ATS.
     */
    public function getCandidate(AtsConnection $connection, string $externalId): ?array;

    /**
     * Create a candidate in the ATS.
     */
    public function createCandidate(AtsConnection $connection, array $data): array;

    /**
     * Update a candidate in the ATS.
     */
    public function updateCandidate(AtsConnection $connection, string $externalId, array $data): array;

    /**
     * Get all jobs/requisitions from the ATS.
     *
     * @return array Array of job data
     */
    public function getJobs(AtsConnection $connection, array $filters = []): array;

    /**
     * Get a specific job from the ATS.
     */
    public function getJob(AtsConnection $connection, string $externalId): ?array;

    /**
     * Create a job in the ATS.
     */
    public function createJob(AtsConnection $connection, array $data): array;

    /**
     * Update a job in the ATS.
     */
    public function updateJob(AtsConnection $connection, string $externalId, array $data): array;

    /**
     * Get applications for a job.
     */
    public function getApplications(AtsConnection $connection, string $jobId): array;

    /**
     * Create an application (submit candidate to job).
     */
    public function createApplication(AtsConnection $connection, string $candidateId, string $jobId, array $data = []): array;

    /**
     * Update application status.
     */
    public function updateApplicationStatus(AtsConnection $connection, string $applicationId, string $status): array;

    /**
     * Register a webhook with the ATS.
     */
    public function registerWebhook(AtsConnection $connection, string $eventType, string $webhookUrl): array;

    /**
     * Unregister a webhook from the ATS.
     */
    public function unregisterWebhook(AtsConnection $connection, string $webhookId): bool;

    /**
     * Parse incoming webhook payload.
     */
    public function parseWebhookPayload(array $payload, array $headers = []): array;

    /**
     * Map external candidate data to our format.
     */
    public function mapCandidateToLocal(array $externalData): array;

    /**
     * Map our candidate data to external format.
     */
    public function mapCandidateToExternal(array $localData): array;

    /**
     * Map external job data to our format.
     */
    public function mapJobToLocal(array $externalData): array;

    /**
     * Map our job data to external format.
     */
    public function mapJobToExternal(array $localData): array;

    /**
     * Get supported webhook event types.
     */
    public function getSupportedWebhookEvents(): array;

    /**
     * Get the API rate limits for this provider.
     */
    public function getRateLimits(): array;
}
