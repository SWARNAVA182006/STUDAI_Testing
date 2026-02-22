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
        Schema::table('job_listings', function (Blueprint $table) {
            $table->string('experience_level')->nullable()->after('employment_type');
            $table->decimal('salary_min', 15, 2)->nullable()->after('salary_range');
            $table->decimal('salary_max', 15, 2)->nullable()->after('salary_min');
            $table->json('required_skills')->nullable()->after('salary_max');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_listings', function (Blueprint $table) {
            $table->dropColumn(['experience_level', 'salary_min', 'salary_max', 'required_skills']);
        });
    }
};
