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
        // Resume templates
        Schema::create('resume_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('preview_image')->nullable();
            $table->enum('category', ['professional', 'creative', 'modern', 'minimalist', 'academic', 'executive']);
            $table->enum('industry', ['technology', 'healthcare', 'finance', 'education', 'creative', 'general']);
            $table->json('color_scheme'); // primary, secondary, accent colors
            $table->json('layout_config'); // columns, sections, spacing
            $table->boolean('is_ats_friendly')->default(true);
            $table->boolean('is_premium')->default(false);
            $table->integer('popularity_score')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['category', 'is_active']);
            $table->index(['industry', 'is_active']);
        });

        // User resumes
        Schema::create('resumes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('resume_templates')->nullOnDelete();
            $table->string('title'); // e.g., "Software Engineer Resume"
            $table->string('slug')->unique();
            $table->boolean('is_default')->default(false);
            
            // Personal Information
            $table->string('full_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('location')->nullable(); // City, Country
            $table->string('linkedin_url')->nullable();
            $table->string('github_url')->nullable();
            $table->string('portfolio_url')->nullable();
            $table->string('profile_photo')->nullable();
            
            // Professional Summary (AI-generated or custom)
            $table->text('professional_summary')->nullable();
            $table->boolean('summary_is_ai_generated')->default(false);
            
            // Resume Data (JSON for flexibility)
            $table->json('experience')->nullable(); // Array of work experiences
            $table->json('education')->nullable(); // Array of education entries
            $table->json('skills')->nullable(); // Categorized skills
            $table->json('certifications')->nullable(); // Certifications and licenses
            $table->json('projects')->nullable(); // Personal/professional projects
            $table->json('achievements')->nullable(); // Awards and achievements
            $table->json('languages')->nullable(); // Languages and proficiency
            $table->json('volunteer_work')->nullable(); // Volunteer experiences
            $table->json('publications')->nullable(); // Publications and papers
            $table->json('custom_sections')->nullable(); // User-defined sections
            
            // AI Customization
            $table->foreignId('target_job_id')->nullable()->constrained('jobs')->nullOnDelete();
            $table->text('target_role_description')->nullable(); // For custom role targeting
            $table->json('ai_optimization_data')->nullable(); // ATS keywords, suggestions
            $table->timestamp('last_ai_optimized_at')->nullable();
            
            // Template Customization
            $table->json('color_overrides')->nullable();
            $table->json('section_order')->nullable();
            $table->json('visibility_settings')->nullable(); // Which sections to show/hide
            
            // Export & Sharing
            $table->string('pdf_path')->nullable();
            $table->string('docx_path')->nullable();
            $table->string('share_token')->nullable()->unique();
            $table->boolean('is_public')->default(false);
            $table->integer('view_count')->default(0);
            $table->integer('download_count')->default(0);
            
            // Metadata
            $table->enum('ats_score', ['poor', 'fair', 'good', 'excellent'])->nullable();
            $table->json('ats_analysis')->nullable(); // Detailed ATS feedback
            $table->timestamp('last_exported_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['user_id', 'is_default']);
            $table->index(['user_id', 'created_at']);
            $table->index('share_token');
        });

        // AI Resume Suggestions
        Schema::create('resume_ai_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resume_id')->constrained()->cascadeOnDelete();
            $table->enum('section', ['summary', 'experience', 'skills', 'achievements', 'projects']);
            $table->enum('suggestion_type', ['improvement', 'keyword', 'quantification', 'action_verb', 'ats_optimization']);
            $table->text('original_content');
            $table->text('suggested_content');
            $table->text('reasoning')->nullable();
            $table->integer('confidence_score')->default(0); // 0-100
            $table->enum('status', ['pending', 'accepted', 'rejected', 'modified'])->default('pending');
            $table->json('metadata')->nullable(); // Keywords matched, impact score, etc.
            $table->timestamps();
            
            $table->index(['resume_id', 'status']);
            $table->index(['resume_id', 'section']);
        });

        // Resume versions (for tracking changes)
        Schema::create('resume_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resume_id')->constrained()->cascadeOnDelete();
            $table->integer('version_number');
            $table->json('resume_data'); // Full snapshot of resume data
            $table->string('change_description')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            
            $table->index(['resume_id', 'version_number']);
        });

        // Resume analytics
        Schema::create('resume_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resume_id')->constrained()->cascadeOnDelete();
            $table->enum('event_type', ['created', 'viewed', 'exported', 'shared', 'customized', 'ai_optimized']);
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('referrer')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at');
            
            $table->index(['resume_id', 'event_type', 'created_at']);
        });

        // AI generation history (for caching and learning)
        Schema::create('ai_resume_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resume_id')->nullable()->constrained()->cascadeOnDelete();
            $table->enum('generation_type', ['summary', 'experience_bullet', 'skills_extraction', 'achievement_quantification', 'full_resume']);
            $table->text('input_data'); // User's raw data
            $table->text('prompt_used');
            $table->text('ai_response');
            $table->integer('tokens_used')->default(0);
            $table->float('cost', 8, 4)->default(0);
            $table->float('generation_time')->default(0); // seconds
            $table->string('model_used')->default('gpt-4');
            $table->boolean('was_accepted')->default(false);
            $table->json('feedback')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'generation_type', 'created_at']);
            $table->index('created_at');
        });

        // Resume keywords (for ATS optimization)
        Schema::create('resume_keywords', function (Blueprint $table) {
            $table->id();
            $table->string('keyword');
            $table->string('category'); // technical, soft_skill, industry, certification, etc.
            $table->string('industry')->nullable();
            $table->string('job_role')->nullable();
            $table->integer('importance_score')->default(0); // 0-100
            $table->json('synonyms')->nullable();
            $table->integer('usage_count')->default(0);
            $table->timestamps();
            
            $table->unique(['keyword', 'category']);
            $table->index(['category', 'industry']);
            $table->index('importance_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_resume_generations');
        Schema::dropIfExists('resume_analytics');
        Schema::dropIfExists('resume_versions');
        Schema::dropIfExists('resume_ai_suggestions');
        Schema::dropIfExists('resume_keywords');
        Schema::dropIfExists('resumes');
        Schema::dropIfExists('resume_templates');
    }
};
