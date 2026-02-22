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
        // User Skills - Skills the user currently has or is learning
        Schema::create('user_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('skill_name');
            $table->string('category')->nullable(); // technical, soft, domain, language, tool
            $table->enum('proficiency_level', ['beginner', 'intermediate', 'advanced', 'expert'])->default('beginner');
            $table->integer('proficiency_score')->default(0); // 0-100
            $table->enum('source', ['self_reported', 'validated', 'ai_detected', 'assessment'])->default('self_reported');
            $table->json('evidence')->nullable(); // work history, projects, certifications
            $table->date('acquired_date')->nullable();
            $table->date('last_used_date')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->integer('market_demand_score')->nullable(); // 0-100, updated daily
            $table->decimal('average_salary_impact', 10, 2)->nullable(); // salary premium for this skill
            $table->json('related_skills')->nullable(); // skills that complement this one
            $table->json('metadata')->nullable(); // certifications, projects, endorsements
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['user_id', 'skill_name']);
            $table->index(['user_id', 'category']);
            $table->index(['proficiency_level']);
            $table->index(['is_verified']);
            $table->index(['market_demand_score']);
            $table->unique(['user_id', 'skill_name']);
        });

        // Skill Gaps - Identified gaps between current skills and market/goal requirements
        Schema::create('skill_gaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('skill_name');
            $table->string('category')->nullable();
            $table->enum('gap_severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->integer('impact_score')->default(0); // 0-100, impact on career goals
            $table->integer('market_demand_score')->default(0); // 0-100, current market demand
            $table->decimal('salary_impact', 10, 2)->nullable(); // potential salary increase
            $table->json('required_for_roles')->nullable(); // job titles that need this skill
            $table->integer('learning_time_weeks')->nullable(); // estimated time to proficiency
            $table->enum('difficulty_level', ['easy', 'moderate', 'challenging', 'advanced'])->default('moderate');
            $table->json('prerequisite_skills')->nullable(); // skills needed before learning this
            $table->json('ai_reasoning')->nullable(); // why this gap was identified
            $table->boolean('is_emerging_skill')->default(false); // trending/future skill
            $table->integer('trend_score')->nullable(); // 0-100, growth trajectory
            $table->string('trend_direction')->nullable(); // rising, stable, declining
            $table->date('identified_date');
            $table->date('target_completion_date')->nullable();
            $table->enum('status', ['identified', 'learning', 'completed', 'deferred'])->default('identified');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['user_id', 'gap_severity']);
            $table->index(['user_id', 'status']);
            $table->index(['impact_score']);
            $table->index(['market_demand_score']);
            $table->index(['is_emerging_skill']);
        });

        // Learning Paths - Curated step-by-step learning journeys
        Schema::create('learning_paths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('skill_gap_id')->nullable()->constrained()->onDelete('set null');
            $table->string('path_name');
            $table->text('description')->nullable();
            $table->string('target_skill');
            $table->enum('target_proficiency', ['beginner', 'intermediate', 'advanced', 'expert'])->default('intermediate');
            $table->integer('total_duration_hours')->default(0);
            $table->integer('total_resources')->default(0);
            $table->integer('completed_resources')->default(0);
            $table->decimal('completion_percentage', 5, 2)->default(0);
            $table->json('learning_style_preferences')->nullable(); // video, reading, interactive, project-based
            $table->json('schedule_preferences')->nullable(); // daily time commitment, preferred days
            $table->json('steps')->nullable(); // ordered array of learning milestones
            $table->json('prerequisites_completed')->nullable(); // skills completed before starting
            $table->enum('difficulty_progression', ['gradual', 'moderate', 'steep'])->default('gradual');
            $table->integer('estimated_completion_weeks')->nullable();
            $table->date('started_date')->nullable();
            $table->date('target_completion_date')->nullable();
            $table->date('completed_date')->nullable();
            $table->enum('status', ['draft', 'active', 'paused', 'completed', 'abandoned'])->default('draft');
            $table->boolean('is_ai_generated')->default(true);
            $table->json('ai_customizations')->nullable(); // personalization details
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['user_id', 'status']);
            $table->index(['target_skill']);
            $table->index(['completion_percentage']);
        });

        // Learning Resources - Individual learning materials (courses, articles, videos, etc.)
        Schema::create('learning_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_path_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('url')->nullable();
            $table->enum('resource_type', ['course', 'video', 'article', 'book', 'tutorial', 'project', 'documentation', 'podcast', 'interactive'])->default('article');
            $table->enum('provider', ['coursera', 'udemy', 'pluralsight', 'youtube', 'medium', 'github', 'official_docs', 'free_code_camp', 'khan_academy', 'other'])->default('other');
            $table->string('provider_name')->nullable();
            $table->decimal('cost', 8, 2)->default(0); // 0 for free
            $table->string('currency', 3)->default('USD');
            $table->boolean('is_free')->default(true);
            $table->integer('duration_hours')->nullable();
            $table->enum('difficulty_level', ['beginner', 'intermediate', 'advanced', 'all_levels'])->default('beginner');
            $table->decimal('rating', 3, 2)->nullable(); // 0-5 stars
            $table->integer('reviews_count')->nullable();
            $table->json('skills_covered')->nullable(); // array of skills taught
            $table->string('language', 10)->default('en');
            $table->boolean('has_certificate')->default(false);
            $table->boolean('is_hands_on')->default(false); // practical/project-based
            $table->json('prerequisites')->nullable(); // required knowledge
            $table->integer('step_order')->default(0); // order within learning path
            $table->integer('ai_relevance_score')->nullable(); // 0-100, how relevant to user
            $table->json('tags')->nullable(); // searchable tags
            $table->date('last_updated')->nullable(); // content freshness
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['learning_path_id', 'step_order']);
            $table->index(['resource_type']);
            $table->index(['is_free']);
            $table->index(['difficulty_level']);
            $table->index(['ai_relevance_score']);
        });

        // Skill Assessments - AI-generated tests to validate skill proficiency
        Schema::create('skill_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_skill_id')->nullable()->constrained()->onDelete('set null');
            $table->string('skill_name');
            $table->string('assessment_title');
            $table->text('description')->nullable();
            $table->enum('assessment_type', ['multiple_choice', 'coding', 'scenario_based', 'project', 'mixed'])->default('multiple_choice');
            $table->enum('difficulty_level', ['beginner', 'intermediate', 'advanced', 'expert'])->default('intermediate');
            $table->json('questions')->nullable(); // array of question objects
            $table->integer('total_questions')->default(0);
            $table->integer('passing_score')->default(70); // percentage
            $table->integer('time_limit_minutes')->nullable();
            $table->json('answers')->nullable(); // user's answers
            $table->integer('score')->nullable(); // 0-100
            $table->boolean('passed')->nullable();
            $table->enum('proficiency_awarded', ['beginner', 'intermediate', 'advanced', 'expert'])->nullable();
            $table->json('detailed_results')->nullable(); // per-question breakdown
            $table->json('strengths')->nullable(); // areas of strength
            $table->json('weaknesses')->nullable(); // areas to improve
            $table->json('recommendations')->nullable(); // learning suggestions
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // certificate expiration
            $table->boolean('is_shareable')->default(false);
            $table->string('certificate_url')->nullable(); // public certificate link
            $table->string('certificate_hash')->unique()->nullable(); // verification hash
            $table->enum('status', ['draft', 'in_progress', 'submitted', 'graded', 'expired'])->default('draft');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['user_id', 'skill_name']);
            $table->index(['user_id', 'status']);
            $table->index(['passed']);
            $table->index(['certificate_hash']);
        });

        // Learning Progress - Track daily/weekly progress on learning activities
        Schema::create('learning_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('learning_path_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('learning_resource_id')->nullable()->constrained()->onDelete('cascade');
            $table->date('progress_date');
            $table->integer('time_spent_minutes')->default(0);
            $table->decimal('completion_percentage', 5, 2)->default(0);
            $table->enum('activity_type', ['watching', 'reading', 'coding', 'quiz', 'project', 'practice'])->default('reading');
            $table->text('notes')->nullable(); // user's learning notes
            $table->json('achievements')->nullable(); // milestones reached
            $table->integer('streak_days')->default(0); // consecutive learning days
            $table->boolean('daily_goal_met')->default(false);
            $table->json('metadata')->nullable(); // chapters completed, exercises done, etc.
            $table->timestamps();
            
            $table->index(['user_id', 'progress_date']);
            $table->index(['learning_path_id']);
            $table->index(['learning_resource_id']);
            $table->index(['daily_goal_met']);
        });

        // Skill Validations - AI analysis of work history to validate claimed skills
        Schema::create('skill_validations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_skill_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('skill_name');
            $table->enum('validation_source', ['work_history', 'project', 'education', 'certification', 'endorsement', 'assessment'])->default('work_history');
            $table->text('evidence_description')->nullable();
            $table->json('evidence_data')->nullable(); // job title, company, duration, achievements
            $table->integer('confidence_score')->default(0); // 0-100, AI confidence in validation
            $table->enum('proficiency_detected', ['beginner', 'intermediate', 'advanced', 'expert'])->nullable();
            $table->integer('years_of_experience')->nullable();
            $table->json('key_achievements')->nullable(); // specific accomplishments using this skill
            $table->json('projects')->nullable(); // projects demonstrating this skill
            $table->json('ai_analysis')->nullable(); // detailed AI reasoning
            $table->json('demonstration_suggestions')->nullable(); // how to better showcase this skill
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['user_id', 'skill_name']);
            $table->index(['user_skill_id']);
            $table->index(['validation_source']);
            $table->index(['confidence_score']);
            $table->index(['is_verified']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('skill_validations');
        Schema::dropIfExists('learning_progress');
        Schema::dropIfExists('skill_assessments');
        Schema::dropIfExists('learning_resources');
        Schema::dropIfExists('learning_paths');
        Schema::dropIfExists('skill_gaps');
        Schema::dropIfExists('user_skills');
    }
};
