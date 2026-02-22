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
        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('feature', [
                'resume_analysis', 'job_matching', 'cover_letter', 
                'interview_prep', 'career_advice', 'skills_extraction'
            ]);
            $table->string('model'); // gpt-4o, text-embedding-3-large, etc.
            $table->integer('input_tokens');
            $table->integer('output_tokens');
            $table->integer('total_tokens');
            $table->decimal('cost_usd', 10, 6);
            $table->integer('response_time_ms')->nullable(); // Response time in milliseconds
            $table->json('metadata')->nullable(); // Additional context
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
            $table->index(['feature', 'created_at']);
            $table->index('model');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_usage_logs');
    }
};
