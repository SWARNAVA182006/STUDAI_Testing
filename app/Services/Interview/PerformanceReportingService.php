<?php

namespace App\Services\Interview;

use App\Models\InterviewSession;
use App\Models\InterviewPerformanceReport;
use App\Models\InterviewCoachingTip;
use App\Models\CompanyInterviewData;
use App\Services\AI\OpenAIService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PerformanceReportingService
{
    public function __construct(
        protected OpenAIService $openAIService
    ) {}

    /**
     * Generate comprehensive performance report for a completed interview session
     */
    public function generateReport(InterviewSession $session): InterviewPerformanceReport
    {
        if ($session->status !== 'completed') {
            throw new \Exception('Cannot generate report for incomplete session');
        }

        // Calculate all metrics
        $categoryScores = $this->calculateCategoryScores($session);
        $strengths = $this->identifyStrengths($session);
        $weaknesses = $this->identifyWeaknesses($session);
        $fillerWordAnalysis = $this->analyzeFillerWords($session);
        $starScore = $this->evaluateSTARMethodology($session);
        $companyFit = $this->assessCompanyFit($session);
        $improvements = $this->generateActionableImprovements($session);
        $practiceAreas = $this->identifyPracticeAreas($session);
        $comparisonMetrics = $this->generateComparisonMetrics($session);

        // Create the performance report
        $report = InterviewPerformanceReport::create([
            'interview_session_id' => $session->id,
            'overall_score' => $session->overall_score,
            'category_scores' => $categoryScores,
            'strengths' => $strengths,
            'weaknesses' => $weaknesses,
            'filler_word_analysis' => $fillerWordAnalysis,
            'star_methodology_score' => $starScore,
            'company_fit_analysis' => $companyFit,
            'actionable_improvements' => $improvements,
            'recommended_practice_areas' => $practiceAreas,
            'comparison_metrics' => $comparisonMetrics,
        ]);

        // Generate AI-powered insights
        $this->generateAIInsights($report, $session);

        return $report->fresh();
    }

    /**
     * Calculate scores by category
     */
    protected function calculateCategoryScores(InterviewSession $session): array
    {
        $responses = $session->questions()
            ->with('response')
            ->get()
            ->pluck('response')
            ->filter();

        if ($responses->isEmpty()) {
            return [];
        }

        // Group by question type
        $behavioral = $responses->filter(fn($r) => 
            $r->interviewQuestion->question_type === 'behavioral'
        );
        $technical = $responses->filter(fn($r) => 
            $r->interviewQuestion->question_type === 'technical'
        );
        $situational = $responses->filter(fn($r) => 
            $r->interviewQuestion->question_type === 'situational'
        );

        $scores = [];

        // Behavioral questions
        if ($behavioral->isNotEmpty()) {
            $scores['behavioral'] = [
                'average_score' => round($behavioral->avg('overall_score'), 2),
                'confidence' => round($behavioral->avg('confidence_score'), 2),
                'structure' => round($behavioral->avg('structure_score'), 2),
                'count' => $behavioral->count(),
            ];
        }

        // Technical questions
        if ($technical->isNotEmpty()) {
            $scores['technical'] = [
                'average_score' => round($technical->avg('overall_score'), 2),
                'content' => round($technical->avg('content_score'), 2),
                'clarity' => round($technical->avg('clarity_score'), 2),
                'count' => $technical->count(),
            ];
        }

        // Situational questions
        if ($situational->isNotEmpty()) {
            $scores['situational'] = [
                'average_score' => round($situational->avg('overall_score'), 2),
                'problem_solving' => round($situational->avg('content_score'), 2),
                'communication' => round($situational->avg('clarity_score'), 2),
                'count' => $situational->count(),
            ];
        }

        // Overall metrics
        $scores['overall'] = [
            'confidence' => round($responses->avg('confidence_score'), 2),
            'clarity' => round($responses->avg('clarity_score'), 2),
            'structure' => round($responses->avg('structure_score'), 2),
            'content' => round($responses->avg('content_score'), 2),
        ];

        return $scores;
    }

    /**
     * Identify key strengths
     */
    protected function identifyStrengths(InterviewSession $session): array
    {
        $strengths = [];
        $categoryScores = $this->calculateCategoryScores($session);

        // Check overall metrics
        $overall = $categoryScores['overall'] ?? [];
        
        if (($overall['confidence'] ?? 0) >= 85) {
            $strengths[] = [
                'area' => 'Confidence',
                'score' => $overall['confidence'],
                'description' => 'You demonstrated strong confidence throughout your answers',
            ];
        }

        if (($overall['clarity'] ?? 0) >= 85) {
            $strengths[] = [
                'area' => 'Communication Clarity',
                'score' => $overall['clarity'],
                'description' => 'Your answers were clear and well-articulated',
            ];
        }

        if (($overall['structure'] ?? 0) >= 85) {
            $strengths[] = [
                'area' => 'Answer Structure',
                'score' => $overall['structure'],
                'description' => 'You structured your answers logically and coherently',
            ];
        }

        // Check category performance
        foreach (['behavioral', 'technical', 'situational'] as $category) {
            if (isset($categoryScores[$category])) {
                $score = $categoryScores[$category]['average_score'];
                
                if ($score >= 85) {
                    $strengths[] = [
                        'area' => ucfirst($category) . ' Questions',
                        'score' => $score,
                        'description' => "Excellent performance on {$category} questions",
                    ];
                }
            }
        }

        // Check STAR methodology
        $starScore = $this->evaluateSTARMethodology($session);
        if (($starScore['overall_compliance'] ?? 0) >= 80) {
            $strengths[] = [
                'area' => 'STAR Methodology',
                'score' => $starScore['overall_compliance'],
                'description' => 'Strong use of STAR format in behavioral responses',
            ];
        }

        // Check filler words
        $fillerAnalysis = $this->analyzeFillerWords($session);
        if (($fillerAnalysis['overall_percentage'] ?? 10) < 3) {
            $strengths[] = [
                'area' => 'Professional Delivery',
                'score' => 95,
                'description' => 'Minimal use of filler words, very professional delivery',
            ];
        }

        // Sort by score and return top strengths
        usort($strengths, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($strengths, 0, 5);
    }

    /**
     * Identify areas for improvement
     */
    protected function identifyWeaknesses(InterviewSession $session): array
    {
        $weaknesses = [];
        $categoryScores = $this->calculateCategoryScores($session);

        // Check overall metrics
        $overall = $categoryScores['overall'] ?? [];
        
        if (($overall['confidence'] ?? 100) < 70) {
            $weaknesses[] = [
                'area' => 'Confidence',
                'score' => $overall['confidence'],
                'description' => 'Work on projecting more confidence in your delivery',
                'impact' => 'high',
            ];
        }

        if (($overall['clarity'] ?? 100) < 70) {
            $weaknesses[] = [
                'area' => 'Communication Clarity',
                'score' => $overall['clarity'],
                'description' => 'Focus on clearer articulation and sentence structure',
                'impact' => 'high',
            ];
        }

        if (($overall['structure'] ?? 100) < 70) {
            $weaknesses[] = [
                'area' => 'Answer Structure',
                'score' => $overall['structure'],
                'description' => 'Improve the logical organization of your answers',
                'impact' => 'high',
            ];
        }

        if (($overall['content'] ?? 100) < 70) {
            $weaknesses[] = [
                'area' => 'Content Depth',
                'score' => $overall['content'],
                'description' => 'Provide more detailed and relevant examples',
                'impact' => 'high',
            ];
        }

        // Check category performance
        foreach (['behavioral', 'technical', 'situational'] as $category) {
            if (isset($categoryScores[$category])) {
                $score = $categoryScores[$category]['average_score'];
                
                if ($score < 70) {
                    $weaknesses[] = [
                        'area' => ucfirst($category) . ' Questions',
                        'score' => $score,
                        'description' => "Need more practice with {$category} questions",
                        'impact' => 'medium',
                    ];
                }
            }
        }

        // Check STAR methodology
        $starScore = $this->evaluateSTARMethodology($session);
        if (($starScore['overall_compliance'] ?? 100) < 60) {
            $weaknesses[] = [
                'area' => 'STAR Methodology',
                'score' => $starScore['overall_compliance'],
                'description' => 'Incomplete use of STAR format in behavioral answers',
                'impact' => 'high',
            ];
        }

        // Check filler words
        $fillerAnalysis = $this->analyzeFillerWords($session);
        if (($fillerAnalysis['overall_percentage'] ?? 0) > 5) {
            $weaknesses[] = [
                'area' => 'Filler Words',
                'score' => max(0, 100 - ($fillerAnalysis['overall_percentage'] * 10)),
                'description' => 'Reduce filler words for more professional delivery',
                'impact' => 'medium',
            ];
        }

        // Sort by impact and score
        usort($weaknesses, function($a, $b) {
            $impactOrder = ['high' => 0, 'medium' => 1, 'low' => 2];
            $impactCompare = $impactOrder[$a['impact']] <=> $impactOrder[$b['impact']];
            
            return $impactCompare !== 0 ? $impactCompare : $a['score'] <=> $b['score'];
        });

        return array_slice($weaknesses, 0, 5);
    }

    /**
     * Analyze filler word usage
     */
    protected function analyzeFillerWords(InterviewSession $session): array
    {
        $responses = $session->questions()->with('response')->get()->pluck('response')->filter();

        if ($responses->isEmpty()) {
            return [];
        }

        $totalWords = $responses->sum(fn($r) => $r->calculateWordCount());
        $totalFillerWords = $responses->sum(fn($r) => $r->getFillerWordCount());
        $overallPercentage = $totalWords > 0 
            ? round(($totalFillerWords / $totalWords) * 100, 2) 
            : 0;

        // Aggregate filler words across all responses
        $fillerWordCounts = [];
        
        foreach ($responses as $response) {
            $fillers = $response->filler_words ?? [];
            
            foreach ($fillers as $word => $count) {
                if (!isset($fillerWordCounts[$word])) {
                    $fillerWordCounts[$word] = 0;
                }
                $fillerWordCounts[$word] += $count;
            }
        }

        // Sort by frequency
        arsort($fillerWordCounts);
        $topFillers = array_slice($fillerWordCounts, 0, 5, true);

        return [
            'total_filler_words' => $totalFillerWords,
            'total_words' => $totalWords,
            'overall_percentage' => $overallPercentage,
            'top_filler_words' => $topFillers,
            'assessment' => $this->getFillerWordAssessment($overallPercentage),
        ];
    }

    protected function getFillerWordAssessment(float $percentage): string
    {
        if ($percentage < 2) {
            return 'Excellent - very professional delivery';
        } elseif ($percentage < 5) {
            return 'Good - acceptable level of filler words';
        } elseif ($percentage < 8) {
            return 'Fair - noticeable filler words, room for improvement';
        } else {
            return 'Needs improvement - excessive filler words affecting professionalism';
        }
    }

    /**
     * Evaluate STAR methodology usage
     */
    protected function evaluateSTARMethodology(InterviewSession $session): array
    {
        $behavioralResponses = $session->questions()
            ->where('question_type', 'behavioral')
            ->with('response')
            ->get()
            ->pluck('response')
            ->filter();

        if ($behavioralResponses->isEmpty()) {
            return ['overall_compliance' => null, 'message' => 'No behavioral questions'];
        }

        $totalComponents = 0;
        $presentComponents = 0;
        $componentBreakdown = [
            'situation' => 0,
            'task' => 0,
            'action' => 0,
            'result' => 0,
        ];

        foreach ($behavioralResponses as $response) {
            $starAnalysis = $response->star_analysis ?? [];
            
            foreach (['situation', 'task', 'action', 'result'] as $component) {
                $totalComponents++;
                
                if (!empty($starAnalysis[$component])) {
                    $presentComponents++;
                    $componentBreakdown[$component]++;
                }
            }
        }

        $overallCompliance = $totalComponents > 0 
            ? round(($presentComponents / $totalComponents) * 100, 2) 
            : 0;

        return [
            'overall_compliance' => $overallCompliance,
            'total_behavioral_questions' => $behavioralResponses->count(),
            'component_breakdown' => array_map(
                fn($count) => round(($count / $behavioralResponses->count()) * 100, 2),
                $componentBreakdown
            ),
            'assessment' => $this->getSTARAssessment($overallCompliance),
        ];
    }

    protected function getSTARAssessment(float $compliance): string
    {
        if ($compliance >= 90) {
            return 'Excellent STAR methodology usage';
        } elseif ($compliance >= 75) {
            return 'Good STAR framework application';
        } elseif ($compliance >= 60) {
            return 'Partial STAR usage - some components missing';
        } else {
            return 'Needs improvement - focus on complete STAR structure';
        }
    }

    /**
     * Assess company fit
     */
    protected function assessCompanyFit(InterviewSession $session): array
    {
        $companyData = CompanyInterviewData::forCompany($session->company_name)
            ->forRole($session->role_title)
            ->first();

        if (!$companyData || !$companyData->hasSubstantialData()) {
            return ['score' => null, 'message' => 'Insufficient company data for comparison'];
        }

        $responses = $session->questions()->with('response')->get()->pluck('response')->filter();
        
        if ($responses->isEmpty()) {
            return ['score' => null, 'message' => 'No responses to analyze'];
        }

        $culturalKeywords = $companyData->getCulturalKeywords();
        $technicalTopics = $companyData->getTechnicalTopics();
        
        $culturalMatches = 0;
        $technicalMatches = 0;
        $totalKeywords = count($culturalKeywords) + count($technicalTopics);

        foreach ($responses as $response) {
            $text = strtolower($response->response_text);
            
            foreach ($culturalKeywords as $keyword) {
                if (str_contains($text, strtolower($keyword))) {
                    $culturalMatches++;
                }
            }
            
            foreach ($technicalTopics as $topic) {
                if (str_contains($text, strtolower($topic))) {
                    $technicalMatches++;
                }
            }
        }

        $fitScore = $totalKeywords > 0 
            ? round((($culturalMatches + $technicalMatches) / $totalKeywords) * 100, 2) 
            : 75;

        return [
            'score' => min(100, $fitScore),
            'cultural_alignment' => $culturalMatches,
            'technical_alignment' => $technicalMatches,
            'assessment' => $this->getCompanyFitAssessment($fitScore),
        ];
    }

    protected function getCompanyFitAssessment(float $score): string
    {
        if ($score >= 80) {
            return 'Excellent alignment with company culture and technical expectations';
        } elseif ($score >= 60) {
            return 'Good fit - demonstrated understanding of company values';
        } elseif ($score >= 40) {
            return 'Moderate fit - could emphasize company-specific points more';
        } else {
            return 'Limited alignment shown - research company culture and values more deeply';
        }
    }

    /**
     * Generate actionable improvements
     */
    protected function generateActionableImprovements(InterviewSession $session): array
    {
        $improvements = [];
        $weaknesses = $this->identifyWeaknesses($session);

        foreach ($weaknesses as $weakness) {
            $improvement = [
                'area' => $weakness['area'],
                'current_score' => $weakness['score'],
                'target_score' => min(100, $weakness['score'] + 20),
                'priority' => $weakness['impact'],
                'action_items' => $this->getActionItemsForArea($weakness['area']),
            ];

            $improvements[] = $improvement;
        }

        return $improvements;
    }

    protected function getActionItemsForArea(string $area): array
    {
        $actionItems = [
            'Confidence' => [
                'Practice power posing before interviews',
                'Use definitive language instead of hedging phrases',
                'Prepare thoroughly to boost natural confidence',
            ],
            'Communication Clarity' => [
                'Record and review your practice answers',
                'Focus on one idea per sentence',
                'Use transition words to connect thoughts',
            ],
            'Answer Structure' => [
                'Always use STAR format for behavioral questions',
                'Plan your answer structure before speaking',
                'Include clear introduction and conclusion',
            ],
            'Content Depth' => [
                'Prepare specific examples with measurable outcomes',
                'Research company and role thoroughly',
                'Practice quantifying your achievements',
            ],
            'STAR Methodology' => [
                'Write out 5-7 STAR stories from your experience',
                'Practice identifying all four components',
                'Review STAR examples from successful candidates',
            ],
            'Filler Words' => [
                'Record yourself and count filler words',
                'Pause instead of saying "um" or "like"',
                'Practice speaking slowly and deliberately',
            ],
        ];

        return $actionItems[$area] ?? [
            'Practice this area specifically',
            'Seek feedback from others',
            'Review best practices and examples',
        ];
    }

    /**
     * Identify recommended practice areas
     */
    protected function identifyPracticeAreas(InterviewSession $session): array
    {
        $practiceAreas = [];
        $categoryScores = $this->calculateCategoryScores($session);

        // Recommend practice based on category performance
        foreach (['behavioral', 'technical', 'situational'] as $category) {
            if (isset($categoryScores[$category])) {
                $score = $categoryScores[$category]['average_score'];
                
                if ($score < 80) {
                    $practiceAreas[] = ucfirst($category) . ' Questions';
                }
            }
        }

        // Check STAR
        $starScore = $this->evaluateSTARMethodology($session);
        if (($starScore['overall_compliance'] ?? 100) < 75) {
            $practiceAreas[] = 'STAR Methodology';
        }

        // Check filler words
        $fillerAnalysis = $this->analyzeFillerWords($session);
        if (($fillerAnalysis['overall_percentage'] ?? 0) > 5) {
            $practiceAreas[] = 'Professional Delivery (Reduce Filler Words)';
        }

        // Company-specific practice
        $companyFit = $this->assessCompanyFit($session);
        if (($companyFit['score'] ?? 100) < 70) {
            $practiceAreas[] = "Company Culture Alignment ({$session->company_name})";
        }

        return array_unique($practiceAreas);
    }

    /**
     * Generate comparison metrics
     */
    protected function generateComparisonMetrics(InterviewSession $session): array
    {
        // Compare to average performance for this company/role
        $avgSessionScore = DB::table('interview_sessions')
            ->where('company_name', $session->company_name)
            ->where('role_title', $session->role_title)
            ->where('status', 'completed')
            ->where('id', '!=', $session->id)
            ->avg('overall_score');

        $comparison = null;
        
        if ($avgSessionScore !== null) {
            $diff = $session->overall_score - $avgSessionScore;
            $comparison = [
                'your_score' => $session->overall_score,
                'average_score' => round($avgSessionScore, 2),
                'difference' => round($diff, 2),
                'percentile' => $this->calculatePercentile($session),
            ];
        }

        return [
            'company_role_comparison' => $comparison,
            'sessions_completed' => $session->user->interviewSessions()->where('status', 'completed')->count(),
            'improvement_trend' => $this->calculateImprovementTrend($session),
        ];
    }

    protected function calculatePercentile(InterviewSession $session): ?int
    {
        $totalSessions = DB::table('interview_sessions')
            ->where('company_name', $session->company_name)
            ->where('role_title', $session->role_title)
            ->where('status', 'completed')
            ->count();

        if ($totalSessions < 10) {
            return null; // Not enough data
        }

        $lowerScores = DB::table('interview_sessions')
            ->where('company_name', $session->company_name)
            ->where('role_title', $session->role_title)
            ->where('status', 'completed')
            ->where('overall_score', '<', $session->overall_score)
            ->count();

        return round(($lowerScores / $totalSessions) * 100);
    }

    protected function calculateImprovementTrend(InterviewSession $session): ?array
    {
        $recentSessions = $session->user
            ->interviewSessions()
            ->where('status', 'completed')
            ->orderBy('completed_at', 'desc')
            ->limit(5)
            ->get();

        if ($recentSessions->count() < 2) {
            return null;
        }

        $scores = $recentSessions->pluck('overall_score')->toArray();
        $firstScore = end($scores);
        $lastScore = reset($scores);
        
        $improvement = $lastScore - $firstScore;
        $trend = $improvement > 5 ? 'improving' : ($improvement < -5 ? 'declining' : 'stable');

        return [
            'trend' => $trend,
            'change' => round($improvement, 2),
            'sessions_analyzed' => count($scores),
        ];
    }

    /**
     * Generate AI-powered insights
     */
    protected function generateAIInsights(InterviewPerformanceReport $report, InterviewSession $session): void
    {
        try {
            $prompt = $this->buildAIInsightsPrompt($report, $session);
            
            $aiResponse = $this->openAIService->generateCompletion($prompt, [
                'max_tokens' => 500,
                'temperature' => 0.7,
            ]);

            $insights = json_decode($aiResponse, true);

            if ($insights) {
                // Store AI insights in the session
                $session->updateAIInsights($insights);
            }
        } catch (\Exception $e) {
            Log::error('AI insights generation failed', [
                'error' => $e->getMessage(),
                'report_id' => $report->id,
            ]);
        }
    }

    protected function buildAIInsightsPrompt(InterviewPerformanceReport $report, InterviewSession $session): string
    {
        $prompt = "Generate personalized interview coaching insights based on this performance report.\n\n";
        $prompt .= "Company: {$session->company_name}\n";
        $prompt .= "Role: {$session->role_title}\n";
        $prompt .= "Overall Score: {$report->overall_score}\n\n";
        
        $prompt .= "Top Strengths:\n";
        foreach ($report->getTopStrengths() as $strength) {
            $prompt .= "- {$strength['area']}: {$strength['description']}\n";
        }
        
        $prompt .= "\nTop Weaknesses:\n";
        foreach ($report->getTopWeaknesses() as $weakness) {
            $prompt .= "- {$weakness['area']}: {$weakness['description']}\n";
        }

        $prompt .= "\nProvide a JSON object with:\n";
        $prompt .= "- key_insight: One sentence summarizing overall performance\n";
        $prompt .= "- next_steps: Array of 3 specific next actions\n";
        $prompt .= "- motivation_message: Encouraging message based on performance\n";

        return $prompt;
    }
}
