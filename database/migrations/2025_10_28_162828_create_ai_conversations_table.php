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
        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('context', [
                'resume_review', 'interview_prep', 'career_advice', 
                'cover_letter', 'job_match', 'skills_gap'
            ]);
            $table->json('messages'); // Conversation history
            $table->integer('tokens_used')->default(0);
            $table->decimal('cost', 8, 4)->default(0);
            $table->string('session_id')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'context']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_conversations');
    }
};
