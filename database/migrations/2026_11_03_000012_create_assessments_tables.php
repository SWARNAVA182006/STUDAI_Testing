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
        // Assessments table
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->foreignId('skill_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('difficulty', ['beginner', 'intermediate', 'advanced', 'expert'])->default('intermediate');
            $table->json('questions'); // Array of question objects
            $table->integer('duration_minutes')->default(30); // Time limit
            $table->integer('passing_score')->default(70); // Percentage required to pass
            $table->integer('total_points')->default(100);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('attempts_count')->default(0);
            $table->integer('pass_count')->default(0);
            $table->decimal('average_score', 5, 2)->nullable();
            $table->timestamps();
            
            $table->index(['skill_id', 'is_active']);
            $table->index('difficulty');
        });
        
        // Assessment attempts table
        Schema::create('assessment_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->json('answers'); // User's submitted answers
            $table->integer('score')->nullable(); // Final score (0-100)
            $table->integer('correct_answers')->default(0);
            $table->integer('total_questions')->default(0);
            $table->boolean('passed')->default(false);
            $table->enum('status', ['in_progress', 'completed', 'expired'])->default('in_progress');
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at'); // Auto-submit if expired
            $table->integer('time_spent_seconds')->nullable(); // Actual time taken
            $table->timestamps();
            
            $table->index(['user_id', 'assessment_id']);
            $table->index('status');
        });
        
        // Certificates table
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attempt_id')->constrained('assessment_attempts')->cascadeOnDelete();
            $table->string('certificate_number')->unique(); // CERT-YYYYMMDD-XXXX
            $table->string('verification_code', 16)->unique(); // Random verification code
            $table->integer('score');
            $table->timestamp('issued_at');
            $table->timestamp('expires_at')->nullable(); // Optional expiry
            $table->boolean('is_verified')->default(true);
            $table->timestamps();
            
            $table->index('verification_code');
            $table->index(['user_id', 'assessment_id']);
        });
        
        // Badges table
        Schema::create('badges', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable(); // Icon/image path
            $table->string('color', 7)->default('#3b82f6'); // Hex color
            $table->enum('category', ['skill', 'achievement', 'milestone', 'special'])->default('skill');
            $table->enum('tier', ['bronze', 'silver', 'gold', 'platinum'])->default('bronze');
            $table->json('criteria')->nullable(); // Requirements to earn badge
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        
        // User badges (pivot table)
        Schema::create('badge_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('badge_id')->constrained()->cascadeOnDelete();
            $table->foreignId('certificate_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('earned_at');
            $table->boolean('is_visible')->default(true); // User can hide badges
            $table->integer('display_order')->default(0);
            
            $table->unique(['user_id', 'badge_id']);
            $table->index('earned_at');
        });
    }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('badge_user');
        Schema::dropIfExists('badges');
        Schema::dropIfExists('certificates');
        Schema::dropIfExists('assessment_attempts');
        Schema::dropIfExists('assessments');
    }
};
