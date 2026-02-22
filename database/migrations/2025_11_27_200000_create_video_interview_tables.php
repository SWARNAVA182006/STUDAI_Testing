<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Video interview sessions - main container for video interviews
        Schema::create('video_interview_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_id')->nullable()->constrained('job_listings')->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('interview_session_id')->nullable()->constrained()->nullOnDelete();
            
            // Session details
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['async', 'live', 'mock'])->default('async');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'expired', 'cancelled'])->default('pending');
            
            // Scheduling
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->integer('max_duration_minutes')->default(60);
            $table->integer('actual_duration_seconds')->nullable();
            
            // Live session specifics
            $table->string('room_id')->nullable()->unique();
            $table->string('room_token')->nullable();
            $table->json('participants')->nullable();
            $table->boolean('has_screen_share')->default(false);
            $table->boolean('is_recording_enabled')->default(true);
            
            // AI Analysis aggregate
            $table->json('ai_analysis_summary')->nullable();
            $table->decimal('overall_score', 5, 2)->nullable();
            $table->json('performance_breakdown')->nullable();
            
            // Settings
            $table->json('settings')->nullable();
            $table->boolean('allow_retakes')->default(false);
            $table->integer('max_retakes')->default(1);
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['user_id', 'status']);
            $table->index(['type', 'status']);
            $table->index(['scheduled_at']);
        });

        // Video interview questions - questions for async video interviews
        Schema::create('video_interview_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_interview_session_id')->constrained()->cascadeOnDelete();
            
            $table->integer('order')->default(1);
            $table->text('question_text');
            $table->text('question_context')->nullable();
            $table->enum('question_type', ['behavioral', 'technical', 'situational', 'general'])->default('general');
            
            // Time limits
            $table->integer('prep_time_seconds')->default(30);
            $table->integer('max_response_time_seconds')->default(180);
            $table->integer('min_response_time_seconds')->default(30);
            
            // Retakes
            $table->integer('max_retakes')->default(2);
            $table->boolean('allow_skip')->default(false);
            
            // Expected elements for AI analysis
            $table->json('expected_elements')->nullable();
            $table->json('keywords_to_look_for')->nullable();
            $table->text('ideal_answer_notes')->nullable();
            
            $table->timestamps();
            
            $table->index(['video_interview_session_id', 'order'], 'vi_questions_session_order_idx');
        });

        // Video recordings - individual video responses
        Schema::create('video_interview_recordings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_interview_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('video_interview_question_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            // Recording details
            $table->enum('recording_type', ['response', 'full_session', 'screen_share'])->default('response');
            $table->integer('attempt_number')->default(1);
            $table->enum('status', ['uploading', 'processing', 'ready', 'failed', 'deleted'])->default('uploading');
            
            // File storage
            $table->string('storage_disk')->default('s3');
            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime_type')->default('video/webm');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->integer('duration_seconds')->nullable();
            
            // Thumbnails
            $table->string('thumbnail_path')->nullable();
            $table->json('thumbnail_sprites')->nullable();
            
            // Playback
            $table->string('playback_url')->nullable();
            $table->string('download_url')->nullable();
            $table->timestamp('url_expires_at')->nullable();
            
            // Transcription
            $table->longText('transcription')->nullable();
            $table->json('transcription_segments')->nullable();
            $table->string('transcription_language')->default('en');
            $table->enum('transcription_status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            
            // Processing metadata
            $table->json('processing_metadata')->nullable();
            $table->timestamp('processed_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['video_interview_session_id', 'recording_type'], 'vi_recordings_session_type_idx');
            $table->index(['user_id', 'status'], 'vi_recordings_user_status_idx');
        });

        // AI Video Analysis - detailed AI analysis of video recordings
        Schema::create('video_interview_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_interview_recording_id')->constrained()->cascadeOnDelete();
            $table->foreignId('video_interview_question_id')->nullable()->constrained()->cascadeOnDelete();
            
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            
            // Content Analysis (from transcription)
            $table->decimal('content_score', 5, 2)->nullable();
            $table->decimal('clarity_score', 5, 2)->nullable();
            $table->decimal('structure_score', 5, 2)->nullable();
            $table->decimal('relevance_score', 5, 2)->nullable();
            $table->json('key_points_mentioned')->nullable();
            $table->json('missing_elements')->nullable();
            $table->json('star_analysis')->nullable();
            
            // Speech Analysis
            $table->decimal('speech_pace_wpm', 6, 2)->nullable();
            $table->enum('speech_pace_rating', ['too_slow', 'slow', 'optimal', 'fast', 'too_fast'])->nullable();
            $table->json('filler_words')->nullable();
            $table->integer('filler_word_count')->default(0);
            $table->decimal('filler_word_percentage', 5, 2)->nullable();
            $table->json('pause_analysis')->nullable();
            $table->decimal('articulation_score', 5, 2)->nullable();
            
            // Body Language Analysis (TensorFlow.js)
            $table->decimal('eye_contact_score', 5, 2)->nullable();
            $table->decimal('posture_score', 5, 2)->nullable();
            $table->decimal('gesture_score', 5, 2)->nullable();
            $table->decimal('facial_expression_score', 5, 2)->nullable();
            $table->json('body_language_breakdown')->nullable();
            $table->json('eye_contact_timeline')->nullable();
            
            // Confidence & Emotion Analysis
            $table->decimal('confidence_score', 5, 2)->nullable();
            $table->decimal('enthusiasm_score', 5, 2)->nullable();
            $table->decimal('nervousness_indicator', 5, 2)->nullable();
            $table->json('emotion_timeline')->nullable();
            $table->json('sentiment_analysis')->nullable();
            
            // Overall Assessment
            $table->decimal('overall_score', 5, 2)->nullable();
            $table->string('performance_grade')->nullable();
            $table->json('strengths')->nullable();
            $table->json('areas_for_improvement')->nullable();
            $table->json('actionable_feedback')->nullable();
            $table->text('ai_summary')->nullable();
            
            // Timestamps for specific moments
            $table->json('notable_moments')->nullable();
            $table->json('improvement_timestamps')->nullable();
            
            $table->timestamp('analyzed_at')->nullable();
            $table->timestamps();
            
            $table->index(['video_interview_recording_id'], 'vi_analyses_recording_idx');
            $table->index(['status'], 'vi_analyses_status_idx');
        });

        // Live interview rooms - WebRTC room management
        Schema::create('video_interview_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_interview_session_id')->constrained()->cascadeOnDelete();
            
            $table->string('room_id')->unique();
            $table->string('room_name');
            $table->enum('status', ['created', 'waiting', 'active', 'ended'])->default('created');
            
            // WebRTC signaling
            $table->json('ice_servers')->nullable();
            $table->json('room_config')->nullable();
            
            // Participants
            $table->integer('max_participants')->default(2);
            $table->integer('current_participants')->default(0);
            $table->json('participant_list')->nullable();
            
            // Features
            $table->boolean('chat_enabled')->default(true);
            $table->boolean('screen_share_enabled')->default(true);
            $table->boolean('recording_enabled')->default(true);
            $table->boolean('whiteboard_enabled')->default(false);
            
            // Recording
            $table->boolean('is_recording')->default(false);
            $table->timestamp('recording_started_at')->nullable();
            
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            
            $table->index(['room_id', 'status']);
        });

        // Room participants - tracking who joined live rooms
        Schema::create('video_interview_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_interview_room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            $table->enum('role', ['interviewer', 'candidate', 'observer'])->default('candidate');
            $table->enum('status', ['invited', 'joined', 'left', 'disconnected'])->default('invited');
            
            $table->string('display_name')->nullable();
            $table->boolean('audio_enabled')->default(true);
            $table->boolean('video_enabled')->default(true);
            $table->boolean('screen_sharing')->default(false);
            
            $table->string('connection_id')->nullable();
            $table->json('device_info')->nullable();
            $table->string('ip_address')->nullable();
            
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->integer('total_duration_seconds')->nullable();
            
            $table->timestamps();
            
            $table->unique(['video_interview_room_id', 'user_id'], 'vi_participants_room_user_unique');
            $table->index(['user_id', 'status'], 'vi_participants_user_status_idx');
        });

        // Video interview templates - reusable question sets
        Schema::create('video_interview_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['behavioral', 'technical', 'mixed', 'custom'])->default('mixed');
            $table->string('role_category')->nullable();
            $table->string('experience_level')->nullable();
            
            $table->json('questions');
            $table->json('settings')->nullable();
            
            $table->boolean('is_public')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('usage_count')->default(0);
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['company_id', 'is_active']);
            $table->index(['type', 'is_public']);
        });

        // Invitations - for employer-initiated video interviews
        Schema::create('video_interview_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_interview_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invited_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('job_id')->nullable()->constrained('job_listings')->nullOnDelete();
            
            $table->string('invitation_token')->unique();
            $table->enum('status', ['pending', 'accepted', 'declined', 'expired'])->default('pending');
            
            $table->text('message')->nullable();
            $table->timestamp('deadline')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->text('decline_reason')->nullable();
            
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->integer('reminder_count')->default(0);
            $table->timestamp('last_reminder_at')->nullable();
            
            $table->timestamps();
            
            $table->index(['candidate_id', 'status']);
            $table->index(['invitation_token']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_interview_invitations');
        Schema::dropIfExists('video_interview_templates');
        Schema::dropIfExists('video_interview_participants');
        Schema::dropIfExists('video_interview_rooms');
        Schema::dropIfExists('video_interview_analyses');
        Schema::dropIfExists('video_interview_recordings');
        Schema::dropIfExists('video_interview_questions');
        Schema::dropIfExists('video_interview_sessions');
    }
};
