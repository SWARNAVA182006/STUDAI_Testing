<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\ProcessVideoRecording;
use App\Jobs\TranscribeVideoRecording;
use App\Jobs\AnalyzeVideoInterview;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use App\Models\VideoInterviewSession;
use App\Models\VideoInterviewQuestion;
use App\Models\VideoInterviewRecording;
use App\Models\VideoInterviewRoom;
use App\Models\VideoInterviewParticipant;
use App\Models\VideoInterviewTemplate;
use App\Models\VideoInterviewInvitation;
use App\Notifications\VideoInterviewInvitationNotification;
use App\Notifications\VideoInterviewReminderNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VideoInterviewService
{
    protected string $storageDisk;

    public function __construct()
    {
        $this->storageDisk = config('video-interview.storage_disk', 's3');
    }

    /**
     * Create a new async video interview session
     */
    public function createAsyncSession(
        User $user,
        string $title,
        array $questions,
        ?int $jobId = null,
        ?int $companyId = null,
        array $settings = []
    ): VideoInterviewSession {
        return DB::transaction(function () use ($user, $title, $questions, $jobId, $companyId, $settings) {
            $session = VideoInterviewSession::create([
                'user_id' => $user->id,
                'job_id' => $jobId,
                'company_id' => $companyId,
                'title' => $title,
                'type' => VideoInterviewSession::TYPE_ASYNC,
                'status' => VideoInterviewSession::STATUS_PENDING,
                'expires_at' => $settings['expires_at'] ?? now()->addDays(7),
                'max_duration_minutes' => $settings['max_duration_minutes'] ?? 60,
                'allow_retakes' => $settings['allow_retakes'] ?? false,
                'max_retakes' => $settings['max_retakes'] ?? 1,
                'settings' => $settings,
            ]);

            foreach ($questions as $index => $questionData) {
                VideoInterviewQuestion::create([
                    'video_interview_session_id' => $session->id,
                    'order' => $index + 1,
                    'question_text' => $questionData['question_text'],
                    'question_context' => $questionData['question_context'] ?? null,
                    'question_type' => $questionData['question_type'] ?? 'general',
                    'prep_time_seconds' => $questionData['prep_time_seconds'] ?? 30,
                    'max_response_time_seconds' => $questionData['max_response_time_seconds'] ?? 180,
                    'min_response_time_seconds' => $questionData['min_response_time_seconds'] ?? 30,
                    'max_retakes' => $questionData['max_retakes'] ?? 2,
                    'allow_skip' => $questionData['allow_skip'] ?? false,
                    'expected_elements' => $questionData['expected_elements'] ?? null,
                    'keywords_to_look_for' => $questionData['keywords_to_look_for'] ?? null,
                ]);
            }

            Log::info('Async video interview session created', [
                'session_id' => $session->id,
                'user_id' => $user->id,
                'question_count' => count($questions),
            ]);

            return $session->load('questions');
        });
    }

    /**
     * Create a mock practice session
     */
    public function createMockSession(
        User $user,
        string $title,
        ?string $roleType = null,
        ?int $companyId = null
    ): VideoInterviewSession {
        $questions = $this->generateMockQuestions($roleType);

        return $this->createAsyncSession(
            user: $user,
            title: $title,
            questions: $questions,
            companyId: $companyId,
            settings: [
                'allow_retakes' => true,
                'max_retakes' => 3,
                'expires_at' => now()->addDays(30),
            ]
        );
    }

    /**
     * Create a live video interview room
     */
    public function createLiveSession(
        User $interviewer,
        User $candidate,
        string $title,
        \DateTime $scheduledAt,
        ?int $jobId = null,
        ?int $companyId = null,
        array $settings = []
    ): VideoInterviewSession {
        return DB::transaction(function () use ($interviewer, $candidate, $title, $scheduledAt, $jobId, $companyId, $settings) {
            $session = VideoInterviewSession::create([
                'user_id' => $candidate->id,
                'job_id' => $jobId,
                'company_id' => $companyId,
                'title' => $title,
                'type' => VideoInterviewSession::TYPE_LIVE,
                'status' => VideoInterviewSession::STATUS_PENDING,
                'scheduled_at' => $scheduledAt,
                'max_duration_minutes' => $settings['max_duration_minutes'] ?? 60,
                'has_screen_share' => $settings['has_screen_share'] ?? true,
                'is_recording_enabled' => $settings['is_recording_enabled'] ?? true,
                'settings' => $settings,
            ]);

            // Create room
            $room = VideoInterviewRoom::create([
                'video_interview_session_id' => $session->id,
                'room_id' => 'room_' . Str::uuid()->toString(),
                'room_name' => $title,
                'status' => VideoInterviewRoom::STATUS_CREATED,
                'max_participants' => $settings['max_participants'] ?? 5,
                'chat_enabled' => $settings['chat_enabled'] ?? true,
                'screen_share_enabled' => $settings['screen_share_enabled'] ?? true,
                'recording_enabled' => $settings['recording_enabled'] ?? true,
                'ice_servers' => $this->getIceServers(),
            ]);

            // Add participants
            VideoInterviewParticipant::create([
                'video_interview_room_id' => $room->id,
                'user_id' => $interviewer->id,
                'role' => VideoInterviewParticipant::ROLE_INTERVIEWER,
                'status' => VideoInterviewParticipant::STATUS_INVITED,
            ]);

            VideoInterviewParticipant::create([
                'video_interview_room_id' => $room->id,
                'user_id' => $candidate->id,
                'role' => VideoInterviewParticipant::ROLE_CANDIDATE,
                'status' => VideoInterviewParticipant::STATUS_INVITED,
            ]);

            Log::info('Live video interview session created', [
                'session_id' => $session->id,
                'room_id' => $room->room_id,
                'interviewer_id' => $interviewer->id,
                'candidate_id' => $candidate->id,
            ]);

            return $session->load(['room', 'room.participants']);
        });
    }

    /**
     * Start a session
     */
    public function startSession(VideoInterviewSession $session): VideoInterviewSession
    {
        if (!$session->canStart()) {
            throw new \Exception('Session cannot be started');
        }

        $session->start();

        if ($session->type === VideoInterviewSession::TYPE_LIVE && $session->room) {
            $session->room->open();
        }

        return $session->fresh();
    }

    /**
     * Complete a session
     */
    public function completeSession(VideoInterviewSession $session): VideoInterviewSession
    {
        $session->complete();

        if ($session->room) {
            $session->room->close();
        }

        // Calculate aggregate analysis
        $this->calculateSessionAnalysis($session);

        return $session->fresh();
    }

    /**
     * Upload a video recording
     */
    public function uploadRecording(
        VideoInterviewSession $session,
        UploadedFile $file,
        ?VideoInterviewQuestion $question = null,
        int $attemptNumber = 1
    ): VideoInterviewRecording {
        $fileName = Str::uuid()->toString() . '.' . $file->getClientOriginalExtension();
        $path = "video-interviews/{$session->id}/{$fileName}";

        // Upload to storage
        Storage::disk($this->storageDisk)->put($path, file_get_contents($file->getRealPath()));

        $recording = VideoInterviewRecording::create([
            'video_interview_session_id' => $session->id,
            'video_interview_question_id' => $question?->id,
            'user_id' => $session->user_id,
            'recording_type' => $question ? 'response' : 'full_session',
            'attempt_number' => $attemptNumber,
            'status' => VideoInterviewRecording::STATUS_UPLOADING,
            'storage_disk' => $this->storageDisk,
            'file_path' => $path,
            'file_name' => $fileName,
            'mime_type' => $file->getMimeType() ?? 'video/webm',
            'file_size' => $file->getSize(),
        ]);

        // Dispatch processing jobs
        ProcessVideoRecording::dispatch($recording);
        TranscribeVideoRecording::dispatch($recording)->delay(now()->addSeconds(30));

        Log::info('Video recording uploaded', [
            'recording_id' => $recording->id,
            'session_id' => $session->id,
            'question_id' => $question?->id,
            'file_size' => $file->getSize(),
        ]);

        return $recording;
    }

    /**
     * Get signed URL for direct upload
     */
    public function getUploadUrl(VideoInterviewSession $session, ?int $questionId = null): array
    {
        $fileName = Str::uuid()->toString() . '.webm';
        $path = "video-interviews/{$session->id}/{$fileName}";

        $disk = Storage::disk($this->storageDisk);

        // For S3, generate a pre-signed URL
        if ($this->storageDisk === 's3') {
            $url = $disk->temporaryUploadUrl($path, now()->addMinutes(30));
            
            return [
                'upload_url' => $url['url'],
                'headers' => $url['headers'] ?? [],
                'file_path' => $path,
                'file_name' => $fileName,
            ];
        }

        // For local storage, return the API endpoint
        return [
            'upload_url' => route('api.video-interview.upload', [
                'session' => $session->id,
                'question' => $questionId,
            ]),
            'method' => 'POST',
            'file_path' => $path,
            'file_name' => $fileName,
        ];
    }

    /**
     * Join a live interview room
     */
    public function joinRoom(VideoInterviewRoom $room, User $user): VideoInterviewParticipant
    {
        if (!$room->can_join) {
            throw new \Exception('Cannot join this room');
        }

        $participant = $room->participants()
            ->where('user_id', $user->id)
            ->first();

        if (!$participant) {
            $participant = VideoInterviewParticipant::create([
                'video_interview_room_id' => $room->id,
                'user_id' => $user->id,
                'role' => VideoInterviewParticipant::ROLE_OBSERVER,
                'status' => VideoInterviewParticipant::STATUS_INVITED,
            ]);
        }

        $participant->join();

        // Activate room if not already active
        if ($room->status === VideoInterviewRoom::STATUS_WAITING) {
            $room->activate();
        }

        return $participant->fresh();
    }

    /**
     * Leave a room
     */
    public function leaveRoom(VideoInterviewParticipant $participant): void
    {
        $participant->leave();

        // Check if room should be closed
        $room = $participant->room;
        if ($room->current_participants === 0) {
            $room->close();
        }
    }

    /**
     * Send video interview invitation
     */
    public function sendInvitation(
        VideoInterviewSession $session,
        User $inviter,
        User $candidate,
        ?Job $job = null,
        ?string $message = null,
        ?\DateTime $deadline = null
    ): VideoInterviewInvitation {
        $invitation = VideoInterviewInvitation::create([
            'video_interview_session_id' => $session->id,
            'invited_by' => $inviter->id,
            'candidate_id' => $candidate->id,
            'job_id' => $job?->id,
            'message' => $message,
            'deadline' => $deadline ?? now()->addDays(7),
        ]);

        $invitation->markAsSent();

        // Send notification
        $candidate->notify(new VideoInterviewInvitationNotification($invitation));

        Log::info('Video interview invitation sent', [
            'invitation_id' => $invitation->id,
            'session_id' => $session->id,
            'candidate_id' => $candidate->id,
        ]);

        return $invitation;
    }

    /**
     * Accept an invitation
     */
    public function acceptInvitation(VideoInterviewInvitation $invitation, User $user): VideoInterviewSession
    {
        if ($invitation->candidate_id !== $user->id) {
            throw new \Exception('Unauthorized');
        }

        if (!$invitation->is_pending) {
            throw new \Exception('Invitation is no longer valid');
        }

        $invitation->accept();

        return $invitation->session;
    }

    /**
     * Decline an invitation
     */
    public function declineInvitation(VideoInterviewInvitation $invitation, User $user, ?string $reason = null): void
    {
        if ($invitation->candidate_id !== $user->id) {
            throw new \Exception('Unauthorized');
        }

        $invitation->decline($reason);
    }

    /**
     * Send reminder for pending invitation
     */
    public function sendReminder(VideoInterviewInvitation $invitation): bool
    {
        if (!$invitation->canSendReminder()) {
            return false;
        }

        $invitation->candidate->notify(new VideoInterviewReminderNotification($invitation));
        $invitation->recordReminder();

        return true;
    }

    /**
     * Get user's video interview sessions
     */
    public function getUserSessions(User $user, ?string $status = null, int $perPage = 10)
    {
        $query = VideoInterviewSession::forUser($user->id)
            ->with(['job', 'company', 'questions'])
            ->latest();

        if ($status) {
            $query->where('status', $status);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get session with full details
     */
    public function getSessionDetails(VideoInterviewSession $session): VideoInterviewSession
    {
        return $session->load([
            'user',
            'job',
            'company',
            'questions.recordings.analysis',
            'recordings.analysis',
            'room.participants.user',
        ]);
    }

    /**
     * Calculate aggregate analysis for a session
     */
    protected function calculateSessionAnalysis(VideoInterviewSession $session): void
    {
        $analyses = $session->recordings()
            ->with('analysis')
            ->get()
            ->pluck('analysis')
            ->filter();

        if ($analyses->isEmpty()) {
            return;
        }

        $summary = [
            'overall_score' => round($analyses->avg('overall_score') ?? 0, 1),
            'content_score' => round($analyses->avg('content_score') ?? 0, 1),
            'confidence_score' => round($analyses->avg('confidence_score') ?? 0, 1),
            'clarity_score' => round($analyses->avg('clarity_score') ?? 0, 1),
            'body_language_score' => round($analyses->avg('eye_contact_score') ?? 0, 1),
            'speech_pace_wpm' => round($analyses->avg('speech_pace_wpm') ?? 0, 0),
            'total_filler_words' => $analyses->sum('filler_word_count'),
            'questions_analyzed' => $analyses->count(),
            'grade' => $this->calculateOverallGrade($analyses->avg('overall_score') ?? 0),
        ];

        $breakdown = [
            'content' => [
                'score' => $summary['content_score'],
                'clarity' => round($analyses->avg('clarity_score') ?? 0, 1),
                'structure' => round($analyses->avg('structure_score') ?? 0, 1),
                'relevance' => round($analyses->avg('relevance_score') ?? 0, 1),
            ],
            'delivery' => [
                'confidence' => $summary['confidence_score'],
                'enthusiasm' => round($analyses->avg('enthusiasm_score') ?? 0, 1),
                'articulation' => round($analyses->avg('articulation_score') ?? 0, 1),
            ],
            'body_language' => [
                'eye_contact' => round($analyses->avg('eye_contact_score') ?? 0, 1),
                'posture' => round($analyses->avg('posture_score') ?? 0, 1),
                'gestures' => round($analyses->avg('gesture_score') ?? 0, 1),
            ],
            'speech' => [
                'pace_wpm' => $summary['speech_pace_wpm'],
                'filler_words' => $summary['total_filler_words'],
            ],
        ];

        $session->update([
            'ai_analysis_summary' => $summary,
            'overall_score' => $summary['overall_score'],
            'performance_breakdown' => $breakdown,
        ]);
    }

    /**
     * Generate mock interview questions
     */
    protected function generateMockQuestions(?string $roleType = null): array
    {
        $generalQuestions = [
            [
                'question_text' => 'Tell me about yourself and your professional background.',
                'question_type' => 'general',
                'prep_time_seconds' => 30,
                'max_response_time_seconds' => 120,
            ],
            [
                'question_text' => 'What are your greatest strengths?',
                'question_type' => 'behavioral',
                'prep_time_seconds' => 30,
                'max_response_time_seconds' => 90,
            ],
            [
                'question_text' => 'Describe a challenging situation at work and how you overcame it.',
                'question_type' => 'behavioral',
                'prep_time_seconds' => 45,
                'max_response_time_seconds' => 180,
            ],
            [
                'question_text' => 'Why are you interested in this role?',
                'question_type' => 'general',
                'prep_time_seconds' => 30,
                'max_response_time_seconds' => 120,
            ],
            [
                'question_text' => 'Where do you see yourself in 5 years?',
                'question_type' => 'general',
                'prep_time_seconds' => 30,
                'max_response_time_seconds' => 90,
            ],
        ];

        if ($roleType === 'technical') {
            $generalQuestions[] = [
                'question_text' => 'Describe your experience with system design and architecture.',
                'question_type' => 'technical',
                'prep_time_seconds' => 45,
                'max_response_time_seconds' => 180,
            ];
            $generalQuestions[] = [
                'question_text' => 'Tell me about a complex technical problem you solved.',
                'question_type' => 'technical',
                'prep_time_seconds' => 45,
                'max_response_time_seconds' => 180,
            ];
        }

        return $generalQuestions;
    }

    /**
     * Get ICE servers configuration
     */
    protected function getIceServers(): array
    {
        $servers = [
            ['urls' => 'stun:stun.l.google.com:19302'],
            ['urls' => 'stun:stun1.l.google.com:19302'],
        ];

        // Add TURN server if configured
        if (config('video-interview.turn_server')) {
            $servers[] = [
                'urls' => config('video-interview.turn_server'),
                'username' => config('video-interview.turn_username'),
                'credential' => config('video-interview.turn_credential'),
            ];
        }

        return $servers;
    }

    /**
     * Calculate overall grade from score
     */
    protected function calculateOverallGrade(float $score): string
    {
        return match (true) {
            $score >= 95 => 'A+',
            $score >= 90 => 'A',
            $score >= 85 => 'B+',
            $score >= 80 => 'B',
            $score >= 75 => 'C+',
            $score >= 70 => 'C',
            $score >= 60 => 'D',
            default => 'F',
        };
    }

    /**
     * Clear session cache
     */
    public function clearSessionCache(int $sessionId): void
    {
        Cache::forget("video_session_{$sessionId}");
        Cache::forget("video_session_analysis_{$sessionId}");
    }
}
