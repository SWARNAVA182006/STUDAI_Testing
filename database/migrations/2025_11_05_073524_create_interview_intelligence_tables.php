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
        // Company interview data - aggregated from thousands of real interview experiences
        Schema::create('company_interview_data', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->index();
            $table->string('role_title')->nullable();
            $table->string('department')->nullable();
            $table->string('interview_type')->nullable(); // technical, behavioral, case, system_design
            $table->json('common_questions'); // Array of frequently asked questions
            $table->json('interviewer_profiles')->nullable(); // Typical interviewer backgrounds
            $table->json('interview_structure')->nullable(); // Rounds, format, duration
            $table->json('difficulty_ratings')->nullable(); // Difficulty by topic
            $table->json('success_patterns')->nullable(); // What works well
            $table->json('cultural_values')->nullable(); // Company values emphasized
            $table->json('technical_focus_areas')->nullable(); // For tech roles
            $table->text('notes')->nullable();
            $table->integer('data_points_count')->default(0); // How many interviews analyzed
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamps();
            
            $table->index(['company_name', 'role_title']);
        });

        // Interview practice sessions
        Schema::create('interview_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('discovered_job_id')->nullable()->constrained()->onDelete('set null');
            $table->string('company_name');
            $table->string('role_title');
            $table->string('interview_type'); // technical, behavioral, case, mixed
            $table->enum('status', ['in_progress', 'completed', 'abandoned'])->default('in_progress');
            $table->integer('total_questions')->default(0);
            $table->integer('questions_answered')->default(0);
            $table->integer('duration_minutes')->nullable();
            $table->decimal('overall_score', 5, 2)->nullable(); // 0-100
            $table->json('performance_metrics')->nullable(); // Detailed breakdown
            $table->json('ai_insights')->nullable(); // AI-generated insights
            $table->json('interviewer_style')->nullable(); // Predicted interviewer characteristics
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['user_id', 'status']);
            $table->index(['company_name', 'role_title']);
        });

        // Individual interview questions
        Schema::create('interview_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interview_session_id')->constrained()->onDelete('cascade');
            $table->integer('question_order')->default(1);
            $table->string('question_type'); // technical, behavioral, situational, case_study
            $table->text('question_text');
            $table->json('question_context')->nullable(); // Background info about question
            $table->string('difficulty_level')->nullable(); // easy, medium, hard
            $table->json('expected_elements')->nullable(); // Key points to cover
            $table->json('star_components')->nullable(); // For behavioral questions
            $table->text('ideal_answer_outline')->nullable();
            $table->json('follow_up_questions')->nullable(); // Predicted follow-ups
            $table->json('interviewer_notes')->nullable(); // What interviewer looks for
            $table->boolean('is_company_specific')->default(false);
            $table->text('company_context')->nullable(); // Why this question for this company
            $table->timestamps();
            
            $table->index(['interview_session_id', 'question_order']);
        });

        // User responses to interview questions
        Schema::create('interview_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interview_question_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('response_type', ['text', 'voice', 'video'])->default('text');
            $table->text('response_text')->nullable();
            $table->string('audio_file_path')->nullable();
            $table->string('video_file_path')->nullable();
            $table->text('transcription')->nullable(); // For voice/video responses
            $table->integer('response_time_seconds')->nullable();
            $table->integer('word_count')->nullable();
            $table->decimal('confidence_score', 5, 2)->nullable(); // AI-detected confidence
            $table->decimal('clarity_score', 5, 2)->nullable();
            $table->decimal('structure_score', 5, 2)->nullable();
            $table->decimal('content_score', 5, 2)->nullable();
            $table->decimal('overall_score', 5, 2)->nullable();
            $table->json('filler_words')->nullable(); // Detected filler words with counts
            $table->json('star_analysis')->nullable(); // STAR methodology breakdown
            $table->json('keywords_used')->nullable(); // Relevant keywords mentioned
            $table->json('missing_elements')->nullable(); // What was not covered
            $table->timestamp('answered_at');
            $table->timestamps();
            
            $table->index(['interview_question_id']);
            $table->index(['user_id', 'answered_at']);
        });

        // Real-time and post-session feedback
        Schema::create('interview_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interview_response_id')->constrained()->onDelete('cascade');
            $table->enum('feedback_type', ['real_time', 'post_response', 'session_summary']);
            $table->text('feedback_text');
            $table->json('strengths')->nullable(); // What went well
            $table->json('improvements')->nullable(); // What to improve
            $table->json('suggestions')->nullable(); // Specific actionable suggestions
            $table->json('example_answers')->nullable(); // Better answer examples
            $table->boolean('is_positive')->default(true);
            $table->string('focus_area')->nullable(); // content, delivery, structure, etc.
            $table->integer('priority')->default(5); // 1-10, higher = more important
            $table->timestamps();
            
            $table->index(['interview_response_id', 'feedback_type']);
        });

        // Performance reports
        Schema::create('interview_performance_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interview_session_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('overall_score', 5, 2);
            $table->json('category_scores'); // Scores by category (technical, behavioral, etc.)
            $table->json('strengths'); // Top strengths identified
            $table->json('weaknesses'); // Areas needing improvement
            $table->json('filler_word_analysis'); // Aggregated filler word stats
            $table->json('star_methodology_score')->nullable(); // STAR usage effectiveness
            $table->json('company_fit_analysis')->nullable(); // How well aligned with company
            $table->json('actionable_improvements'); // Prioritized improvement list
            $table->json('recommended_practice_areas'); // What to practice next
            $table->json('comparison_metrics')->nullable(); // Compare to other candidates
            $table->text('executive_summary');
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
        });

        // Interview coaching tips and talking points
        Schema::create('interview_coaching_tips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interview_session_id')->constrained()->onDelete('cascade');
            $table->string('company_name');
            $table->string('role_title');
            $table->json('company_talking_points'); // Company-specific points to mention
            $table->json('role_specific_tips'); // Role-specific advice
            $table->json('interviewer_insights')->nullable(); // Likely interviewer background/style
            $table->json('cultural_alignment_points'); // How to show cultural fit
            $table->json('technical_prep_areas')->nullable(); // Technical topics to review
            $table->json('common_mistakes')->nullable(); // What to avoid
            $table->json('success_strategies'); // Proven strategies for this company
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interview_coaching_tips');
        Schema::dropIfExists('interview_performance_reports');
        Schema::dropIfExists('interview_feedback');
        Schema::dropIfExists('interview_responses');
        Schema::dropIfExists('interview_questions');
        Schema::dropIfExists('interview_sessions');
        Schema::dropIfExists('company_interview_data');
    }
};
