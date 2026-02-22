<?php

declare(strict_types=1);

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
        Schema::table('agent_configurations', function (Blueprint $table) {
            $table->timestamp('emergency_stopped_at')->nullable()->after('last_run_at');
            $table->foreignId('emergency_stopped_by')->nullable()->after('emergency_stopped_at')
                ->constrained('users')->nullOnDelete();
            $table->text('emergency_stop_reason')->nullable()->after('emergency_stopped_by');
            $table->boolean('is_globally_stopped')->default(false)->after('emergency_stop_reason');

            $table->index('emergency_stopped_at');
            $table->index('is_globally_stopped');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agent_configurations', function (Blueprint $table) {
            $table->dropIndex(['emergency_stopped_at']);
            $table->dropIndex(['is_globally_stopped']);
            $table->dropForeign(['emergency_stopped_by']);
            $table->dropColumn([
                'emergency_stopped_at',
                'emergency_stopped_by',
                'emergency_stop_reason',
                'is_globally_stopped',
            ]);
        });
    }
};
