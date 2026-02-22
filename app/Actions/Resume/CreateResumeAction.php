<?php

declare(strict_types=1);

namespace App\Actions\Resume;

use App\Models\Job;
use App\Models\Resume;
use App\Models\User;
use App\Services\AI\ResumeAIService;
use Illuminate\Support\Facades\DB;

class CreateResumeAction
{
    public function __construct(
        private ResumeAIService $aiService
    ) {}

    public function execute(User $user, array $data): Resume
    {
        return DB::transaction(function () use ($user, $data) {
            $resume = $user->resumes()->create($data);

            // Generate AI summary if not provided
            if (empty($data['professional_summary'])) {
                $targetJob = isset($data['target_job_id']) ? Job::find($data['target_job_id']) : null;
                $summary = $this->aiService->generateProfessionalSummary($resume, $targetJob);
                
                $resume->update([
                    'professional_summary' => $summary,
                    'summary_is_ai_generated' => true,
                ]);
            }

            // Track creation
            $resume->analytics()->create([
                'event_type' => 'created',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return $resume;
        });
    }
}
