<?php

namespace App\Services\AI;

use App\Models\DiscoveredJob;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ApplicationPreparationService
{
    public function __construct(
        protected ResumeCustomizationService $resumeService,
        protected CoverLetterGeneratorService $coverLetterService,
        protected ATSOptimizerService $atsService,
        protected ScreeningQuestionService $screeningService,
        protected ApplicationQualityScorerService $qualityService
    ) {
    }

    /**
     * Prepare a full AI-enhanced application package for a given job.
     */
    public function prepare(
        User $user,
        DiscoveredJob $job,
        string $baseResume,
        array $screeningQuestions = [],
        array $options = []
    ): array {
        $options = array_merge([
            'resume_tone' => 'professional',
            'focus_skills' => [],
            'cover_letter_tone' => 'confident',
            'cover_letter_length' => 'medium',
            'screening_tone' => 'professional',
            'target_score' => 82,
            'force_refresh' => false,
        ], $options);

        $resumeResult = $this->runResumeCustomization($user, $job, $baseResume, $options);
        $coverLetterResult = $this->runCoverLetterGeneration($user, $job, $options);
        $atsResult = $this->runAtsAnalysis($resumeResult['rendered_resume'] ?? $baseResume, $job, $options);
        $screeningAnswers = $this->runScreeningAnswers($user, $job, $screeningQuestions, $options);

        $qualityArtifacts = [
            'resume' => $resumeResult,
            'cover_letter' => $coverLetterResult,
            'ats' => $atsResult,
            'screening_answers' => $screeningAnswers,
        ];

        $qualityAssessment = $this->runQualityAssessment($user, $job, $qualityArtifacts, $options);

        return [
            'resume' => $resumeResult,
            'cover_letter' => $coverLetterResult,
            'ats' => $atsResult,
            'screening_answers' => $screeningAnswers,
            'quality' => $qualityAssessment,
            'auto_application_payload' => $this->buildAutoApplicationPayload(
                $resumeResult,
                $coverLetterResult,
                $atsResult,
                $screeningAnswers,
                $qualityAssessment,
                $job
            ),
            'metadata' => [
                'job_id' => $job->id,
                'ready_to_submit' => ($qualityAssessment['decision'] ?? null) === 'ready',
                'generated_at' => now()->toIso8601String(),
            ],
        ];
    }

    protected function runResumeCustomization(
        User $user,
        DiscoveredJob $job,
        string $baseResume,
        array $options
    ): array {
        try {
            return $this->resumeService->customize($user, $job, $baseResume, [
                'tone' => $options['resume_tone'],
                'focus_skills' => $options['focus_skills'],
                'force_refresh' => $options['force_refresh'],
            ]);
        } catch (\Throwable $exception) {
            Log::error('Application preparation: resume customization failed', [
                'user_id' => $user->id,
                'job_id' => $job->id,
                'error' => $exception->getMessage(),
            ]);

            return [
                'structured_resume' => [],
                'rendered_resume' => $baseResume,
                'ats_score' => 60,
                'resume_changes' => [],
                'optimized_keywords' => [],
                'warnings' => ['Resume customization failed. Using provided resume.'],
                'metadata' => ['fallback' => true],
            ];
        }
    }

    protected function runCoverLetterGeneration(
        User $user,
        DiscoveredJob $job,
        array $options
    ): array {
        try {
            return $this->coverLetterService->generate($user, $job, [
                'tone' => $options['cover_letter_tone'],
                'length' => $options['cover_letter_length'],
                'force_refresh' => $options['force_refresh'],
            ]);
        } catch (\Throwable $exception) {
            Log::error('Application preparation: cover letter generation failed', [
                'user_id' => $user->id,
                'job_id' => $job->id,
                'error' => $exception->getMessage(),
            ]);

            return [
                'structured_letter' => [],
                'cover_letter' => '',
                'keywords_used' => [],
                'confidence' => 0.0,
                'subject_line' => '',
                'metadata' => ['fallback' => true],
            ];
        }
    }

    protected function runAtsAnalysis(
        string $resumeContent,
        DiscoveredJob $job,
        array $options
    ): array {
        try {
            return $this->atsService->analyze($resumeContent, $job, [
                'force_refresh' => $options['force_refresh'],
            ]);
        } catch (\Throwable $exception) {
            Log::error('Application preparation: ATS analysis failed', [
                'job_id' => $job->id,
                'error' => $exception->getMessage(),
            ]);

            return [
                'ats_score' => 60,
                'section_scores' => [],
                'keyword_match' => ['matched' => [], 'missing' => [], 'coverage_percent' => 0.0],
                'formatting_issues' => ['ATS analysis failed. Review resume manually.'],
                'recommendations' => [],
                'optimized_resume' => null,
                'warnings' => ['ATS analysis unavailable.'],
                'summary' => 'ATS analysis unavailable due to fallback.',
            ];
        }
    }

    protected function runScreeningAnswers(
        User $user,
        DiscoveredJob $job,
        array $questions,
        array $options
    ): array {
        if (empty($questions)) {
            return [];
        }

        try {
            return $this->screeningService->answerQuestions($user, $job, $questions, [
                'tone' => $options['screening_tone'],
                'force_refresh' => $options['force_refresh'],
            ]);
        } catch (\Throwable $exception) {
            Log::error('Application preparation: screening answers failed', [
                'user_id' => $user->id,
                'job_id' => $job->id,
                'error' => $exception->getMessage(),
            ]);

            return [];
        }
    }

    protected function runQualityAssessment(
        User $user,
        DiscoveredJob $job,
        array $artifacts,
        array $options
    ): array {
        try {
            return $this->qualityService->evaluate($user, $job, $artifacts, [
                'target_score' => $options['target_score'],
                'force_refresh' => $options['force_refresh'],
            ]);
        } catch (\Throwable $exception) {
            Log::error('Application preparation: quality scoring failed', [
                'user_id' => $user->id,
                'job_id' => $job->id,
                'error' => $exception->getMessage(),
            ]);

            return [
                'readiness_score' => 60.0,
                'confidence' => 0.4,
                'component_scores' => [
                    'resume' => 60.0,
                    'cover_letter' => 55.0,
                    'ats_alignment' => 60.0,
                    'screening' => empty($artifacts['screening_answers']) ? 0.0 : 55.0,
                ],
                'decision' => 'needs_improvement',
                'risk_flags' => ['Unable to compute AI quality score.'],
                'recommended_actions' => [],
                'summary' => 'Fallback quality assessment applied.',
                'next_steps' => ['Review application elements manually.'],
            ];
        }
    }

    protected function buildAutoApplicationPayload(
        array $resume,
        array $coverLetter,
        array $ats,
        array $screening,
        array $quality,
        DiscoveredJob $job
    ): array {
        return [
            'customized_resume_content' => $resume['rendered_resume'] ?? null,
            'resume_changes' => $resume['resume_changes'] ?? [],
            'keywords_optimized' => $resume['optimized_keywords'] ?? [],
            'cover_letter' => $coverLetter['cover_letter'] ?? null,
            'screening_answers' => $screening,
            'ats_optimization_score' => $ats['ats_score'] ?? null,
            'custom_fields' => [
                'quality_decision' => $quality['decision'] ?? null,
                'quality_score' => $quality['readiness_score'] ?? null,
                'quality_confidence' => $quality['confidence'] ?? null,
                'quality_summary' => $quality['summary'] ?? null,
                'quality_next_steps' => $quality['next_steps'] ?? [],
            ],
            'job_id' => $job->id,
        ];
    }
}
