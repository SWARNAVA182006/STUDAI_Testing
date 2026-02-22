<?php

declare(strict_types=1);

namespace App\Services\Ats;

use App\Models\AtsConnection;
use App\Models\AtsProvider;
use App\Models\AtsSyncLog;
use App\Services\Ats\Providers\BambooHrService;
use App\Services\Ats\Providers\GreenhouseService;
use App\Services\Ats\Providers\ICimsService;
use App\Services\Ats\Providers\LeverService;
use App\Services\Ats\Providers\SuccessFactorsService;
use App\Services\Ats\Providers\TaleoService;
use App\Services\Ats\Providers\WorkdayService;
use Illuminate\Support\Facades\Log;

/**
 * Main ATS Service - Factory and orchestration for all ATS integrations.
 */
class AtsService
{
    /**
     * Map of provider slugs to service classes.
     */
    protected array $providers = [
        'lever' => LeverService::class,
        'greenhouse' => GreenhouseService::class,
        'workday' => WorkdayService::class,
        'successfactors' => SuccessFactorsService::class,
        'bamboohr' => BambooHrService::class,
        'icims' => ICimsService::class,
        'taleo' => TaleoService::class,
    ];

    /**
     * Get a provider service instance by slug.
     */
    public function getProvider(string $slug): ?AtsProviderInterface
    {
        if (!isset($this->providers[$slug])) {
            return null;
        }

        return app($this->providers[$slug]);
    }

    /**
     * Get a provider service for a connection.
     */
    public function getProviderForConnection(AtsConnection $connection): ?AtsProviderInterface
    {
        $provider = $connection->provider;
        if (!$provider) {
            return null;
        }

        return $this->getProvider($provider->slug);
    }

    /**
     * Get all available providers.
     */
    public function getAvailableProviders(): array
    {
        return array_map(function ($class) {
            $instance = app($class);
            return [
                'slug' => $instance->getSlug(),
                'name' => $instance->getName(),
                'auth_type' => $instance->getAuthType(),
            ];
        }, $this->providers);
    }

    /**
     * Test connection to an ATS.
     */
    public function testConnection(AtsConnection $connection): bool
    {
        $provider = $this->getProviderForConnection($connection);
        if (!$provider) {
            return false;
        }

        try {
            return $provider->testConnection($connection);
        } catch (\Exception $e) {
            Log::error('ATS connection test failed', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Sync candidates from an ATS.
     */
    public function syncCandidates(AtsConnection $connection, array $filters = []): AtsSyncLog
    {
        $provider = $this->getProviderForConnection($connection);
        $syncLog = AtsSyncLog::startSync($connection->id, 'candidates', 'inbound');

        if (!$provider) {
            $syncLog->fail('Provider not found');
            return $syncLog;
        }

        try {
            $candidates = $provider->getCandidates($connection, $filters);
            $created = 0;
            $updated = 0;
            $failed = 0;

            foreach ($candidates as $externalData) {
                try {
                    $localData = $provider->mapCandidateToLocal($externalData);
                    $mapping = $connection->candidateMappings()
                        ->where('external_candidate_id', $localData['external_id'])
                        ->first();

                    if ($mapping) {
                        // Update existing mapping
                        $mapping->update([
                            'external_data' => $externalData,
                            'last_synced_at' => now(),
                            'sync_status' => 'synced',
                        ]);
                        $updated++;
                    } else {
                        // Create new mapping
                        $connection->candidateMappings()->create([
                            'external_candidate_id' => $localData['external_id'],
                            'sync_direction' => 'inbound',
                            'sync_status' => 'synced',
                            'external_data' => $externalData,
                            'last_synced_at' => now(),
                        ]);
                        $created++;
                    }
                } catch (\Exception $e) {
                    $failed++;
                    Log::warning('Failed to sync candidate', [
                        'connection_id' => $connection->id,
                        'external_id' => $localData['external_id'] ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $syncLog->complete(count($candidates), $created, $updated, $failed);

            // Update connection sync status
            $connection->update([
                'last_sync_at' => now(),
                'sync_status' => $failed > 0 ? 'partial' : 'synced',
            ]);

        } catch (\Exception $e) {
            $syncLog->fail($e->getMessage());
            $connection->update([
                'sync_status' => 'error',
                'sync_error' => $e->getMessage(),
            ]);
        }

        return $syncLog;
    }

    /**
     * Sync jobs from an ATS.
     */
    public function syncJobs(AtsConnection $connection, array $filters = []): AtsSyncLog
    {
        $provider = $this->getProviderForConnection($connection);
        $syncLog = AtsSyncLog::startSync($connection->id, 'jobs', 'inbound');

        if (!$provider) {
            $syncLog->fail('Provider not found');
            return $syncLog;
        }

        try {
            $jobs = $provider->getJobs($connection, $filters);
            $created = 0;
            $updated = 0;
            $failed = 0;

            foreach ($jobs as $externalData) {
                try {
                    $localData = $provider->mapJobToLocal($externalData);
                    $mapping = $connection->jobMappings()
                        ->where('external_job_id', $localData['external_id'])
                        ->first();

                    if ($mapping) {
                        // Update existing mapping
                        $mapping->update([
                            'external_data' => $externalData,
                            'last_synced_at' => now(),
                            'sync_status' => 'synced',
                        ]);
                        $updated++;
                    } else {
                        // Create new mapping
                        $connection->jobMappings()->create([
                            'external_job_id' => $localData['external_id'],
                            'sync_direction' => 'inbound',
                            'sync_status' => 'synced',
                            'external_data' => $externalData,
                            'last_synced_at' => now(),
                        ]);
                        $created++;
                    }
                } catch (\Exception $e) {
                    $failed++;
                    Log::warning('Failed to sync job', [
                        'connection_id' => $connection->id,
                        'external_id' => $localData['external_id'] ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $syncLog->complete(count($jobs), $created, $updated, $failed);

            // Update connection sync status
            $connection->update([
                'last_sync_at' => now(),
                'sync_status' => $failed > 0 ? 'partial' : 'synced',
            ]);

        } catch (\Exception $e) {
            $syncLog->fail($e->getMessage());
            $connection->update([
                'sync_status' => 'error',
                'sync_error' => $e->getMessage(),
            ]);
        }

        return $syncLog;
    }

    /**
     * Push a candidate to an ATS.
     */
    public function pushCandidate(AtsConnection $connection, array $candidateData): array
    {
        $provider = $this->getProviderForConnection($connection);
        if (!$provider) {
            throw new \Exception('Provider not found');
        }

        return $provider->createCandidate($connection, $candidateData);
    }

    /**
     * Push a job to an ATS.
     */
    public function pushJob(AtsConnection $connection, array $jobData): array
    {
        $provider = $this->getProviderForConnection($connection);
        if (!$provider) {
            throw new \Exception('Provider not found');
        }

        return $provider->createJob($connection, $jobData);
    }

    /**
     * Get OAuth authorization URL.
     */
    public function getAuthorizationUrl(AtsConnection $connection): ?string
    {
        $provider = $this->getProviderForConnection($connection);
        if (!$provider) {
            return null;
        }

        return $provider->getAuthorizationUrl($connection);
    }

    /**
     * Handle OAuth callback.
     */
    public function handleOAuthCallback(AtsConnection $connection, string $code): array
    {
        $provider = $this->getProviderForConnection($connection);
        if (!$provider) {
            throw new \Exception('Provider not found');
        }

        $tokens = $provider->handleOAuthCallback($connection, $code);

        // Store tokens in connection credentials
        $credentials = $connection->getDecryptedCredentials();
        $credentials['access_token'] = $tokens['access_token'] ?? null;
        $credentials['refresh_token'] = $tokens['refresh_token'] ?? null;
        $credentials['token_expires_at'] = isset($tokens['expires_in'])
            ? now()->addSeconds($tokens['expires_in'])->toDateTimeString()
            : null;

        $connection->setEncryptedCredentials($credentials);
        $connection->update([
            'is_active' => true,
            'last_sync_at' => now(),
        ]);

        return $tokens;
    }

    /**
     * Refresh OAuth tokens if needed.
     */
    public function refreshTokensIfNeeded(AtsConnection $connection): bool
    {
        $credentials = $connection->getDecryptedCredentials();
        $expiresAt = $credentials['token_expires_at'] ?? null;

        if (!$expiresAt || now()->greaterThan($expiresAt)) {
            $provider = $this->getProviderForConnection($connection);
            if (!$provider) {
                return false;
            }

            try {
                $tokens = $provider->refreshTokens($connection);
                $credentials['access_token'] = $tokens['access_token'] ?? $credentials['access_token'];
                $credentials['refresh_token'] = $tokens['refresh_token'] ?? $credentials['refresh_token'];
                $credentials['token_expires_at'] = isset($tokens['expires_in'])
                    ? now()->addSeconds($tokens['expires_in'])->toDateTimeString()
                    : null;

                $connection->setEncryptedCredentials($credentials);
                return true;
            } catch (\Exception $e) {
                Log::error('Failed to refresh ATS tokens', [
                    'connection_id' => $connection->id,
                    'error' => $e->getMessage(),
                ]);
                return false;
            }
        }

        return true;
    }

    /**
     * Get sync statistics for a connection.
     */
    public function getSyncStats(AtsConnection $connection): array
    {
        return [
            'total_candidates' => $connection->candidateMappings()->count(),
            'synced_candidates' => $connection->candidateMappings()->synced()->count(),
            'failed_candidates' => $connection->candidateMappings()->failed()->count(),
            'total_jobs' => $connection->jobMappings()->count(),
            'synced_jobs' => $connection->jobMappings()->synced()->count(),
            'failed_jobs' => $connection->jobMappings()->failed()->count(),
            'last_sync' => $connection->last_sync_at,
            'sync_status' => $connection->sync_status,
        ];
    }
}
