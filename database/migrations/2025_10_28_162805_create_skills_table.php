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
        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('category'); // technical, soft, language, etc.
            $table->text('description')->nullable();
            $table->integer('demand_index')->default(0); // Market demand
            $table->json('related_skills')->nullable();
            $table->json('learning_resources')->nullable();
            $table->boolean('is_trending')->default(false);
            $table->timestamps();
            
            $table->index(['category', 'demand_index']);
            $table->index('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('skills');
    }
};
