<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\VideoInterviewSession;
use App\Models\VideoInterviewRecording;
use App\Models\VideoInterviewInvitation;
use App\Services\VideoInterviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VideoInterviewController extends Controller
{
    public function __construct(
        protected VideoInterviewService $videoService
    ) {}

    /**
     * Get upload URL for direct client upload
     */
    public function getUploadUrl(Request $request, VideoInterviewSession $session): JsonResponse
    {
        $this->authorize('update', $session);

        $uploadData = $this->videoService->getUploadUrl(
            session: $session,
            questionId: $request->input('question_id')
        );

        return response()->json($uploadData);
    }

    /**
     * Handle direct video upload (for local storage)
     */
    public function uploadVideo(Request $request, VideoInterviewSession $session): JsonResponse
    {
        $this->authorize('update', $session);

        $request->validate([
            'video' => 'required|file|mimes:webm,mp4,mov|max:512000', // 500MB max
            'question_id' => 'nullable|exists:video_interview_questions,id',
            'attempt' => 'nullable|integer|min:1',
        ]);

        $question = null;
        if ($request->input('question_id')) {
            $question = $session->questions()->find($request->input('question_id'));
        }

        $recording = $this->videoService->uploadRecording(
            session: $session,
            file: $request->file('video'),
            question: $question,
            attemptNumber: $request->input('attempt', 1)
        );

        return response()->json([
            'success' => true,
            'recording_id' => $recording->id,
            'file_path' => $recording->file_path,
        ]);
    }

    /**
     * Get session analysis summary
     */
    public function getAnalysis(VideoInterviewSession $session): JsonResponse
    {
        $this->authorize('view', $session);

        $session->load(['recordings.analysis']);

        $analyses = $session->recordings
            ->pluck('analysis')
            ->filter();

        return response()->json([
            'session_id' => $session->id,
            'status' => $session->status,
            'summary' => $session->ai_analysis_summary,
            'overall_score' => $session->overall_score,
            'recordings' => $session->recordings->map(fn ($r) => [
                'id' => $r->id,
                'question_id' => $r->video_interview_question_id,
                'status' => $r->status,
                'has_analysis' => $r->analysis !== null,
                'analysis' => $r->analysis ? [
                    'overall_score' => $r->analysis->overall_score,
                    'content_score' => $r->analysis->content_score,
                    'confidence_score' => $r->analysis->confidence_score,
                    'feedback' => $r->analysis->ai_feedback,
                ] : null,
            ]),
        ]);
    }

    /**
     * Accept invitation
     */
    public function acceptInvitation(VideoInterviewInvitation $invitation): JsonResponse
    {
        $session = $this->videoService->acceptInvitation($invitation, Auth::user());

        return response()->json([
            'success' => true,
            'session_id' => $session->id,
            'redirect_url' => route('video-interview.record', $session),
        ]);
    }

    /**
     * Decline invitation
     */
    public function declineInvitation(Request $request, VideoInterviewInvitation $invitation): JsonResponse
    {
        $this->videoService->declineInvitation(
            invitation: $invitation,
            user: Auth::user(),
            reason: $request->input('reason')
        );

        return response()->json([
            'success' => true,
            'message' => 'Invitation declined.',
        ]);
    }

    /**
     * Receive client-side video analysis data
     */
    public function submitClientAnalysis(Request $request, VideoInterviewRecording $recording): JsonResponse
    {
        $this->authorize('update', $recording->session);

        $request->validate([
            'eye_contact_score' => 'nullable|numeric|min:0|max:100',
            'posture_score' => 'nullable|numeric|min:0|max:100',
            'gesture_score' => 'nullable|numeric|min:0|max:100',
            'expression_data' => 'nullable|array',
            'head_movement' => 'nullable|array',
        ]);

        $recording->update([
            'client_side_analysis' => $request->only([
                'eye_contact_score',
                'posture_score',
                'gesture_score',
                'expression_data',
                'head_movement',
            ]),
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Get recording playback URL
     */
    public function getPlaybackUrl(VideoInterviewRecording $recording): JsonResponse
    {
        $this->authorize('view', $recording->session);

        $url = $recording->getTemporaryUrl(now()->addHour());

        return response()->json([
            'url' => $url,
            'expires_at' => now()->addHour()->toIso8601String(),
        ]);
    }
}
