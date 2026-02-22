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
        if (!Schema::hasTable('salary_reports')) {
            Schema::create('salary_reports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();

                // Job Information
                $table->string('job_title');
                $table->string('department')->nullable();
                $table->string('location')->nullable();
                $table->unsignedTinyInteger('years_of_experience')->nullable();
                $table->unsignedTinyInteger('years_at_company')->nullable();
                $table->enum('experience_level', ['entry', 'mid', 'senior', 'lead', 'executive'])->nullable();

                // Compensation Details
                $table->decimal('base_salary', 12, 2);
                $table->decimal('bonus', 12, 2)->nullable();
                $table->decimal('stock_options', 12, 2)->nullable();
                $table->decimal('signing_bonus', 12, 2)->nullable();
                $table->decimal('profit_sharing', 12, 2)->nullable();
                $table->decimal('commission', 12, 2)->nullable();
                $table->decimal('total_compensation', 12, 2)->virtualAs('base_salary + COALESCE(bonus, 0) + COALESCE(stock_options, 0) + COALESCE(signing_bonus, 0) + COALESCE(profit_sharing, 0) + COALESCE(commission, 0)');
                $table->enum('pay_period', ['hourly', 'weekly', 'biweekly', 'monthly', 'yearly'])->default('yearly');
                $table->string('currency', 3)->default('USD');

                // Employment Details
                $table->boolean('is_current_employee')->default(true);
                $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'intern', 'freelance'])->default('full_time');
                $table->date('employment_start_date')->nullable();
                $table->date('employment_end_date')->nullable();

                // Benefits (JSON)
                $table->json('benefits')->nullable();

                // Additional Compensation Info
                $table->text('additional_notes')->nullable();

                // Verification & Status
                $table->boolean('is_verified')->default(false);
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->boolean('is_anonymous')->default(true);

                $table->timestamps();
                $table->softDeletes();

                // Indexes
                $table->index(['company_id', 'status']);
                $table->index(['company_id', 'job_title']);
                $table->index(['location']);
                $table->index(['experience_level']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_reports');
    }
};
