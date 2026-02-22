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
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('headline')->nullable();
            $table->text('summary')->nullable();
            $table->json('experience')->nullable(); // Array of experience objects
            $table->json('education')->nullable(); // Array of education objects
            $table->json('skills')->nullable(); // Array of skills with proficiency
            $table->json('languages')->nullable(); // Language proficiencies
            $table->string('current_location')->nullable();
            $table->json('preferred_locations')->nullable();
            $table->decimal('expected_salary_min', 10, 2)->nullable();
            $table->decimal('expected_salary_max', 10, 2)->nullable();
            $table->string('notice_period')->nullable();
            $table->enum('work_preference', ['remote', 'hybrid', 'onsite'])->nullable();
            $table->json('social_links')->nullable();
            $table->integer('profile_completeness')->default(0);
            $table->boolean('is_public')->default(true);
            $table->boolean('open_to_opportunities')->default(true);
            $table->timestamps();
            
            $table->index(['user_id', 'is_public']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
