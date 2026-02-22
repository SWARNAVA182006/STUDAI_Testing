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
        // Update company_reviews table with enhanced columns
        Schema::table('company_reviews', function (Blueprint $table) {
            // Add new columns if they don't exist
            if (!Schema::hasColumn('company_reviews', 'job_title')) {
                $table->string('job_title')->nullable()->after('company_id');
            }
            if (!Schema::hasColumn('company_reviews', 'department')) {
                $table->string('department')->nullable()->after('job_title');
            }
            if (!Schema::hasColumn('company_reviews', 'employment_status')) {
                $table->enum('employment_status', ['current', 'former'])->default('former')->after('employment_type');
            }
            if (!Schema::hasColumn('company_reviews', 'is_current_employee')) {
                $table->boolean('is_current_employee')->default(false)->after('employment_status');
            }
            if (!Schema::hasColumn('company_reviews', 'overall_rating')) {
                $table->unsignedTinyInteger('overall_rating')->default(3)->after('rating');
            }
            if (!Schema::hasColumn('company_reviews', 'culture_rating')) {
                $table->unsignedTinyInteger('culture_rating')->nullable()->after('overall_rating');
            }
            if (!Schema::hasColumn('company_reviews', 'compensation_rating')) {
                $table->unsignedTinyInteger('compensation_rating')->nullable()->after('culture_rating');
            }
            if (!Schema::hasColumn('company_reviews', 'work_life_balance_rating')) {
                $table->unsignedTinyInteger('work_life_balance_rating')->nullable()->after('compensation_rating');
            }
            if (!Schema::hasColumn('company_reviews', 'career_growth_rating')) {
                $table->unsignedTinyInteger('career_growth_rating')->nullable()->after('work_life_balance_rating');
            }
            if (!Schema::hasColumn('company_reviews', 'management_rating')) {
                $table->unsignedTinyInteger('management_rating')->nullable()->after('career_growth_rating');
            }
            if (!Schema::hasColumn('company_reviews', 'headline')) {
                $table->string('headline')->nullable()->after('management_rating');
            }
            if (!Schema::hasColumn('company_reviews', 'recommend_to_friend')) {
                $table->boolean('recommend_to_friend')->nullable()->after('advice_to_management');
            }
            if (!Schema::hasColumn('company_reviews', 'ceo_approval')) {
                $table->boolean('ceo_approval')->nullable()->after('recommend_to_friend');
            }
            if (!Schema::hasColumn('company_reviews', 'status')) {
                $table->enum('status', ['pending', 'approved', 'rejected', 'flagged'])->default('pending')->after('is_verified');
            }
            if (!Schema::hasColumn('company_reviews', 'not_helpful_count')) {
                $table->unsignedInteger('not_helpful_count')->default(0)->after('helpful_count');
            }
            if (!Schema::hasColumn('company_reviews', 'is_featured')) {
                $table->boolean('is_featured')->default(false)->after('status');
            }
            if (!Schema::hasColumn('company_reviews', 'is_anonymous')) {
                $table->boolean('is_anonymous')->default(true)->after('is_featured');
            }
            if (!Schema::hasColumn('company_reviews', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Create review votes table
        if (!Schema::hasTable('company_review_votes')) {
            Schema::create('company_review_votes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('review_id')->constrained('company_reviews')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->boolean('is_helpful');
                $table->timestamps();

                $table->unique(['review_id', 'user_id']);
            });
        }

        // Create review reports table
        if (!Schema::hasTable('company_review_reports')) {
            Schema::create('company_review_reports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('review_id')->constrained('company_reviews')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->enum('reason', [
                    'inappropriate', 'fake', 'spam', 'offensive',
                    'confidential', 'defamatory', 'other'
                ]);
                $table->text('details')->nullable();
                $table->enum('status', ['pending', 'reviewed', 'resolved'])->default('pending');
                $table->foreignId('reviewed_by')->nullable()->constrained('users');
                $table->text('resolution_notes')->nullable();
                $table->timestamps();

                $table->unique(['review_id', 'user_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_review_reports');
        Schema::dropIfExists('company_review_votes');

        Schema::table('company_reviews', function (Blueprint $table) {
            $columns = [
                'job_title', 'department', 'employment_status', 'is_current_employee',
                'overall_rating', 'culture_rating', 'compensation_rating', 'work_life_balance_rating',
                'career_growth_rating', 'management_rating', 'headline', 'recommend_to_friend',
                'ceo_approval', 'status', 'not_helpful_count', 'is_featured', 'is_anonymous', 'deleted_at'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('company_reviews', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
