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
        Schema::table('agent_configurations', function (Blueprint $table) {
            // Human-in-the-loop approval settings
            $table->boolean('require_approval')->default(false)->after('auto_follow_up');
            $table->integer('approval_threshold')->default(80)->after('require_approval');
            $table->integer('applications_today')->default(0)->after('applications_this_month');
            $table->date('applications_today_date')->nullable()->after('applications_today');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agent_configurations', function (Blueprint $table) {
            $table->dropColumn([
                'require_approval',
                'approval_threshold',
                'applications_today',
                'applications_today_date',
            ]);
        });
    }
};
