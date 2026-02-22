<?php

namespace App\Jobs;

use App\Models\Company;
use App\Models\TeamDynamic;
use App\Services\AI\Scout\TeamDynamicsAnalyzerService;
use App\Notifications\TeamHealthReportNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class RefreshTeamDynamicsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 1200; // 20 minutes

    /**
     * The company ID to process (null for all companies).
     *
     * @var int|null
     */
    protected $companyId;

    /**
     * Create a new job instance.
     *
     * @param int|null $companyId
     */
    public function __construct(?int $companyId = null)
    {
        $this->companyId = $companyId;
    }

    /**
     * Execute the job.
     *
     * @param TeamDynamicsAnalyzerService $dynamicsService
     * @return void
     * @throws Exception
     */
    public function handle(TeamDynamicsAnalyzerService $dynamicsService): void
    {
        try {
            Log::info('Starting team dynamics refresh', [
                'company_id' => $this->companyId,
                'attempt' => $this->attempts()
            ]);

            // Get companies with existing team dynamics records
            $companiesQuery = Company::query()
                ->whereHas('teamDynamics');

            if ($this->companyId) {
                $companiesQuery->where('id', $this->companyId);
            }

            $companies = $companiesQuery->get();

            $totalRefreshed = 0;
            $healthReports = [];

            foreach ($companies as $company) {
                try {
                    $companyResults = $this->processCompanyTeams($company, $dynamicsService);
                    $totalRefreshed += $companyResults['refreshed'];
                    
                    if ($companyResults['health_report']) {
                        $healthReports[$company->id] = $companyResults['health_report'];
                    }
                } catch (Exception $e) {
                    Log::warning('Failed to refresh team dynamics for company', [
                        'company_id' => $company->id,
                        'error' => $e->getMessage()
                    ]);
                    continue; // Continue with next company
                }
            }

            Log::info('Team dynamics refresh completed', [
                'companies_processed' => $companies->count(),
                'total_teams_refreshed' => $totalRefreshed
            ]);

            // Archive old data
            $this->archiveOldTeamDynamics();

            // Send health reports
            $this->sendHealthReports($healthReports);

        } catch (Exception $e) {
            Log::error('Team dynamics refresh job failed', [
                'company_id' => $this->companyId,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Re-throw to trigger retry
            throw $e;
        }
    }

    /**
     * Process team dynamics for a single company.
     *
     * @param Company $company
     * @param TeamDynamicsAnalyzerService $dynamicsService
     * @return array
     */
    protected function processCompanyTeams(Company $company, TeamDynamicsAnalyzerService $dynamicsService): array
    {
        $refreshed = 0;
        $departments = ['Engineering', 'Product', 'Design', 'Marketing', 'Sales', 'HR', 'Operations'];
        $healthScores = [];

        foreach ($departments as $department) {
            try {
                // Check if department has team members
                $teamSize = $company->users()
                    ->where('department', $department)
                    ->count();

                if ($teamSize < 3) {
                    // Skip departments with less than 3 members
                    continue;
                }

                // Analyze team dynamics
                $analysisResult = $dynamicsService->analyzeTeamDynamics(
                    $company->id,
                    $department
                );

                if (!$analysisResult['success']) {
                    Log::warning('Team dynamics analysis failed', [
                        'company_id' => $company->id,
                        'department' => $department,
                        'message' => $analysisResult['message'] ?? 'Unknown error'
                    ]);
                    continue;
                }

                $dynamicsData = $analysisResult['data'];

                // Update or create team dynamics record
                $teamDynamic = TeamDynamic::updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'department' => $department
                    ],
                    [
                        'team_size' => $teamSize,
                        'collaboration_score' => $dynamicsData['team_health']['collaboration_score'] ?? null,
                        'psychological_safety_score' => $dynamicsData['team_health']['psychological_safety_score'] ?? null,
                        'team_health_score' => $dynamicsData['team_health']['team_health_score'] ?? null,
                        'communication_effectiveness' => $dynamicsData['communication_effectiveness'] ?? null,
                        'conflict_resolution_score' => $dynamicsData['conflict_resolution_score'] ?? null,
                        'innovation_score' => $dynamicsData['innovation_score'] ?? null,
                        'autonomy_level' => $dynamicsData['autonomy_level'] ?? null,
                        'leadership_style' => $dynamicsData['leadership_style'] ?? null,
                        'team_composition' => $dynamicsData['team_composition'] ?? [],
                        'skill_distribution' => $dynamicsData['skill_distribution'] ?? [],
                        'collaboration_patterns' => $dynamicsData['collaboration_patterns'] ?? [],
                        'onboarding_success_rate' => $dynamicsData['onboarding_success_rate'] ?? null,
                        'average_tenure' => $dynamicsData['average_tenure'] ?? null,
                        'ideal_new_hire_traits' => $dynamicsData['ideal_candidate']['ideal_traits'] ?? [],
                        'skill_gaps_to_fill' => $dynamicsData['ideal_candidate']['skill_gaps'] ?? [],
                        'ai_recommendations' => $dynamicsData['ai_recommendations'] ?? null,
                        'analyzed_at' => now(),
                    ]
                );

                $healthScores[$department] = [
                    'team_health_score' => $teamDynamic->team_health_score,
                    'psychological_safety_score' => $teamDynamic->psychological_safety_score,
                    'collaboration_score' => $teamDynamic->collaboration_score,
                    'team_size' => $teamSize
                ];

                $refreshed++;

            } catch (Exception $e) {
                Log::warning('Failed to refresh team dynamics for department', [
                    'company_id' => $company->id,
                    'department' => $department,
                    'error' => $e->getMessage()
                ]);
                continue;
            }
        }

        // Generate health report
        $healthReport = null;
        if (!empty($healthScores)) {
            $healthReport = $this->generateHealthReport($company, $healthScores);
        }

        return [
            'refreshed' => $refreshed,
            'health_report' => $healthReport
        ];
    }

    /**
     * Generate health report for company.
     *
     * @param Company $company
     * @param array $healthScores
     * @return array
     */
    protected function generateHealthReport(Company $company, array $healthScores): array
    {
        // Calculate overall metrics
        $totalTeams = count($healthScores);
        $avgHealthScore = collect($healthScores)->avg('team_health_score');
        $avgPsychSafety = collect($healthScores)->avg('psychological_safety_score');
        $avgCollaboration = collect($healthScores)->avg('collaboration_score');

        // Identify at-risk teams (health score < 60)
        $atRiskTeams = collect($healthScores)
            ->filter(fn($scores) => ($scores['team_health_score'] ?? 0) < 60)
            ->keys()
            ->toArray();

        // Identify high-performing teams (health score >= 85)
        $highPerformingTeams = collect($healthScores)
            ->filter(fn($scores) => ($scores['team_health_score'] ?? 0) >= 85)
            ->keys()
            ->toArray();

        return [
            'company_id' => $company->id,
            'company_name' => $company->name,
            'total_teams' => $totalTeams,
            'average_health_score' => round($avgHealthScore, 1),
            'average_psychological_safety' => round($avgPsychSafety, 1),
            'average_collaboration' => round($avgCollaboration, 1),
            'at_risk_teams' => $atRiskTeams,
            'high_performing_teams' => $highPerformingTeams,
            'department_scores' => $healthScores,
            'generated_at' => now()->toDateTimeString()
        ];
    }

    /**
     * Archive old team dynamics data.
     *
     * @return void
     */
    protected function archiveOldTeamDynamics(): void
    {
        try {
            $cutoffDate = Carbon::now()->subMonths(12);

            // Find old records
            $oldRecords = TeamDynamic::where('analyzed_at', '<', $cutoffDate)->get();

            if ($oldRecords->isEmpty()) {
                return;
            }

            // Archive to separate table or delete based on retention policy
            // For now, we'll soft delete (assuming soft deletes trait is added)
            foreach ($oldRecords as $record) {
                // You could move to archive table here
                // For simplicity, we'll just add a flag
                $record->update(['archived_at' => now()]);
            }

            Log::info('Archived old team dynamics data', [
                'cutoff_date' => $cutoffDate->toDateString(),
                'records_archived' => $oldRecords->count()
            ]);

        } catch (Exception $e) {
            // Don't fail job if archival fails
            Log::warning('Failed to archive old team dynamics', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send health reports to HR managers.
     *
     * @param array $healthReports
     * @return void
     */
    protected function sendHealthReports(array $healthReports): void
    {
        foreach ($healthReports as $companyId => $report) {
            try {
                $company = Company::find($companyId);
                if (!$company) {
                    continue;
                }

                // Find HR manager or admin
                $hrManager = $company->users()
                    ->where('account_type', 'employer')
                    ->where('role', 'hr_manager')
                    ->first();

                if (!$hrManager) {
                    $hrManager = $company->users()
                        ->where('account_type', 'employer')
                        ->where('role', 'admin')
                        ->first();
                }

                if ($hrManager) {
                    $hrManager->notify(new TeamHealthReportNotification($report));
                    
                    Log::info('Team health report sent', [
                        'company_id' => $companyId,
                        'user_id' => $hrManager->id,
                        'teams_analyzed' => $report['total_teams']
                    ]);
                }

            } catch (Exception $e) {
                // Don't fail job if notification fails
                Log::warning('Failed to send team health report', [
                    'company_id' => $companyId,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return array
     */
    public function backoff(): array
    {
        // Exponential backoff: 5min, 15min, 30min
        return [300, 900, 1800];
    }

    /**
     * Handle a job failure.
     *
     * @param Exception $exception
     * @return void
     */
    public function failed(Exception $exception): void
    {
        Log::error('Team dynamics refresh job permanently failed', [
            'company_id' => $this->companyId,
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage()
        ]);

        // Update team dynamics with error status
        try {
            $query = TeamDynamic::query();
            
            if ($this->companyId) {
                $query->where('company_id', $this->companyId);
            }

            $query->update([
                'last_error' => $exception->getMessage(),
                'last_error_at' => now()
            ]);
        } catch (Exception $e) {
            Log::error('Failed to update team dynamics error status', [
                'company_id' => $this->companyId,
                'error' => $e->getMessage()
            ]);
        }
    }
}
