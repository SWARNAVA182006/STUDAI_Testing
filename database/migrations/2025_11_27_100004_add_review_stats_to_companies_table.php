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
        Schema::table('companies', function (Blueprint $table) {
            // Add review statistics columns if they don't exist
            if (!Schema::hasColumn('companies', 'avg_rating')) {
                $table->decimal('avg_rating', 3, 2)->nullable()->after('description');
            }
            if (!Schema::hasColumn('companies', 'total_reviews')) {
                $table->unsignedInteger('total_reviews')->default(0)->after('avg_rating');
            }
            if (!Schema::hasColumn('companies', 'total_salaries')) {
                $table->unsignedInteger('total_salaries')->default(0)->after('total_reviews');
            }
            if (!Schema::hasColumn('companies', 'total_interviews')) {
                $table->unsignedInteger('total_interviews')->default(0)->after('total_salaries');
            }
            if (!Schema::hasColumn('companies', 'recommend_percent')) {
                $table->unsignedTinyInteger('recommend_percent')->nullable()->after('total_interviews');
            }
            if (!Schema::hasColumn('companies', 'ceo_approval_percent')) {
                $table->unsignedTinyInteger('ceo_approval_percent')->nullable()->after('recommend_percent');
            }
            if (!Schema::hasColumn('companies', 'avg_salary')) {
                $table->decimal('avg_salary', 12, 2)->nullable()->after('ceo_approval_percent');
            }
            if (!Schema::hasColumn('companies', 'interview_difficulty_avg')) {
                $table->decimal('interview_difficulty_avg', 3, 2)->nullable()->after('avg_salary');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $columns = [
                'avg_rating',
                'total_reviews',
                'total_salaries',
                'total_interviews',
                'recommend_percent',
                'ceo_approval_percent',
                'avg_salary',
                'interview_difficulty_avg',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('companies', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
