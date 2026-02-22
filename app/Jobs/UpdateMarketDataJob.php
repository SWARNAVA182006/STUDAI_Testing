<?php

namespace App\Jobs;

use App\Models\MarketDataSnapshot;
use App\Services\AI\MarketIntelligenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Update Market Data Job
 * 
 * Runs hourly to gather fresh market data and update market_data_snapshots table.
 * Analyzes job postings, identifies trends, and stores aggregated market intelligence.
 */
class UpdateMarketDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 900; // 15 minutes

    /**
     * Execute the job.
     */
    public function handle(MarketIntelligenceService $marketIntelligence): void
    {
        Log::info('UpdateMarketDataJob: Starting market data update');

        try {
            // Get market overview (aggregated data across all roles/locations)
            $overview = $marketIntelligence->getMarketOverview();
            
            // Store global market snapshot
            $this->storeMarketSnapshot('Global', null, null, $overview);
            
            // Get top roles to analyze
            $topRoles = $overview['top_roles'] ?? [];
            
            foreach ($topRoles as $roleData) {
                $role = $roleData['title'] ?? null;
                
                if (!$role) continue;
                
                // Analyze market for this role (global)
                $roleMarketData = $marketIntelligence->analyzeJobMarket($role, null, null);
                $this->storeMarketSnapshot($role, null, null, $roleMarketData);
                
                // Analyze for top locations
                $topLocations = $overview['top_locations'] ?? [];
                
                foreach (array_slice($topLocations, 0, 5) as $locationData) {
                    $location = $locationData['location'] ?? null;
                    
                    if (!$location) continue;
                    
                    // Analyze role in specific location
                    $locationMarketData = $marketIntelligence->analyzeJobMarket($role, $location, null);
                    $this->storeMarketSnapshot($role, $location, null, $locationMarketData);
                }
            }
            
            Log::info('UpdateMarketDataJob: Market data update completed successfully');
            
        } catch (Exception $e) {
            Log::error('UpdateMarketDataJob: Failed to update market data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e; // Re-throw to mark job as failed
        }
    }

    /**
     * Store market snapshot in database
     */
    protected function storeMarketSnapshot(
        string $role,
        ?string $location,
        ?string $industry,
        array $data
    ): void {
        MarketDataSnapshot::create([
            'role' => $role,
            'location' => $location,
            'industry' => $industry,
            'total_jobs' => $data['total_jobs'] ?? 0,
            'avg_salary' => $data['avg_salary'] ?? 0,
            'demand_score' => $data['demand_score'] ?? 0,
            'market_health' => $data['market_health'] ?? 'stable',
            'top_skills' => $data['top_skills'] ?? [],
            'emerging_skills' => $data['emerging_skills'] ?? [],
            'declining_skills' => $data['declining_skills'] ?? [],
            'top_companies' => $data['top_companies'] ?? [],
            'salary_trends' => $data['salary_trends'] ?? [],
            'demand_supply_ratio' => $data['demand_supply_ratio'] ?? 1.0,
            'remote_percentage' => $data['remote_percentage'] ?? 0,
            'ai_insights' => $data['ai_insights'] ?? null,
            'snapshot_date' => now()->toDateString(),
        ]);
        
        Log::info('UpdateMarketDataJob: Stored market snapshot', [
            'role' => $role,
            'location' => $location ?? 'Global',
            'total_jobs' => $data['total_jobs'] ?? 0,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('UpdateMarketDataJob: Job failed after all retries', [
            'error' => $exception->getMessage(),
        ]);
    }
}
