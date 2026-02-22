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
        Schema::create('scout_decision_traces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->onDelete('cascade');
            $table->string('prediction_type', 50);
            $table->json('explanation_json');
            $table->string('model_version', 50)->nullable();
            $table->decimal('final_score', 8, 4)->nullable();
            $table->string('confidence_level', 20)->nullable();
            $table->timestamp('traced_at');
            $table->timestamps();

            $table->index(['application_id', 'prediction_type'], 'scout_dt_app_type_idx');
            $table->index('prediction_type');
            $table->index('traced_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scout_decision_traces');
    }
};
