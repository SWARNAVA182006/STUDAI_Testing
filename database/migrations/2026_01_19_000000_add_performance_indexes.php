<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add missing indexes for better query performance
        
        // Users table indexes
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!$this->indexExists('users', 'users_email_verified_at_index')) {
                    $table->index('email_verified_at');
                }
                if (!$this->indexExists('users', 'users_account_type_index')) {
                    $table->index('account_type');
                }
                if (!$this->indexExists('users', 'users_created_at_index')) {
                    $table->index('created_at');
                }
            });
        }
        
        // Job listings table indexes
        if (Schema::hasTable('job_listings')) {
            Schema::table('job_listings', function (Blueprint $table) {
                if (!$this->indexExists('job_listings', 'job_listings_company_id_status_index')) {
                    $table->index(['company_id', 'status']);
                }
                // Skip category, published_at, expires_at as columns don't exist
                if (!$this->indexExists('job_listings', 'job_listings_work_mode_index')) {
                    $table->index('work_mode');
                }
                if (!$this->indexExists('job_listings', 'job_listings_employment_type_index')) {
                    $table->index('employment_type');
                }
            });
        }
        
        // Applications table indexes
        if (Schema::hasTable('applications')) {
            Schema::table('applications', function (Blueprint $table) {
                if (!$this->indexExists('applications', 'applications_job_id_status_index')) {
                    $table->index(['job_id', 'status']);
                }
                if (!$this->indexExists('applications', 'applications_user_id_status_index')) {
                    $table->index(['user_id', 'status']);
                }
                if (!$this->indexExists('applications', 'applications_submitted_at_index')) {
                    $table->index('submitted_at');
                }
                if (!$this->indexExists('applications', 'applications_match_score_index')) {
                    $table->index('match_score');
                }
                if (!$this->indexExists('applications', 'applications_source_index')) {
                    $table->index('source');
                }
            });
        }
        
        // Profiles table indexes
        if (Schema::hasTable('profiles')) {
            Schema::table('profiles', function (Blueprint $table) {
                if (!$this->indexExists('profiles', 'profiles_location_index')) {
                    $table->index('current_location', 'profiles_location_index');
                }
                // if (!$this->indexExists('profiles', 'profiles_total_experience_index')) {
                //     $table->index('total_experience');
                // }
            });
        }
        
        // Companies table indexes
        if (Schema::hasTable('companies')) {
            Schema::table('companies', function (Blueprint $table) {
                if (!$this->indexExists('companies', 'companies_industry_index')) {
                    $table->index('industry');
                }
                if (!$this->indexExists('companies', 'companies_verified_index')) {
                    $table->index('is_verified', 'companies_verified_index');
                }
            });
        }
        
        // User subscriptions indexes
        if (Schema::hasTable('user_subscriptions')) {
            Schema::table('user_subscriptions', function (Blueprint $table) {
                if (!$this->indexExists('user_subscriptions', 'user_subscriptions_status_index')) {
                    $table->index('status');
                }
                if (!$this->indexExists('user_subscriptions', 'user_subscriptions_expires_at_index')) {
                    $table->index('ends_at', 'user_subscriptions_expires_at_index');
                }
                if (!$this->indexExists('user_subscriptions', 'user_subscriptions_user_id_status_index')) {
                    $table->index(['user_id', 'status']);
                }
            });
        }
        
        // Payment transactions indexes
        if (Schema::hasTable('payment_transactions')) {
            Schema::table('payment_transactions', function (Blueprint $table) {
                if (!$this->indexExists('payment_transactions', 'payment_transactions_status_index')) {
                    $table->index('status');
                }
                if (!$this->indexExists('payment_transactions', 'payment_transactions_payment_gateway_index')) {
                    $table->index('payment_gateway');
                }
                if (!$this->indexExists('payment_transactions', 'payment_transactions_created_at_index')) {
                    $table->index('created_at');
                }
            });
        }
        
        // Interviews table indexes
        if (Schema::hasTable('interviews')) {
            Schema::table('interviews', function (Blueprint $table) {
                if (!$this->indexExists('interviews', 'interviews_scheduled_at_index')) {
                    $table->index('scheduled_at');
                }
                if (!$this->indexExists('interviews', 'interviews_status_index')) {
                    $table->index('status');
                }
                if (!$this->indexExists('interviews', 'interviews_application_id_status_index')) {
                    $table->index(['application_id', 'status']);
                }
            });
        }
        
        // Job alerts indexes
        if (Schema::hasTable('job_alerts')) {
            Schema::table('job_alerts', function (Blueprint $table) {
                if (!$this->indexExists('job_alerts', 'job_alerts_is_active_index')) {
                    $table->index('is_active');
                }
                if (!$this->indexExists('job_alerts', 'job_alerts_user_id_is_active_index')) {
                    $table->index(['user_id', 'is_active']);
                }
            });
        }
        
        // Saved jobs indexes
        if (Schema::hasTable('saved_jobs')) {
            Schema::table('saved_jobs', function (Blueprint $table) {
                if (!$this->indexExists('saved_jobs', 'saved_jobs_created_at_index')) {
                    $table->index('created_at');
                }
            });
        }
        
        // Employee referrals indexes
        if (Schema::hasTable('employee_referrals')) {
            Schema::table('employee_referrals', function (Blueprint $table) {
                if (!$this->indexExists('employee_referrals', 'employee_referrals_status_index')) {
                    $table->index('status');
                }
                if (!$this->indexExists('employee_referrals', 'employee_referrals_bonus_status_index')) {
                    $table->index('bonus_status');
                }
                if (!$this->indexExists('employee_referrals', 'employee_referrals_company_id_status_index')) {
                    $table->index(['company_id', 'status']);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes in reverse order
        $tables = [
            'employee_referrals' => ['employee_referrals_status_index', 'employee_referrals_bonus_status_index', 'employee_referrals_company_id_status_index'],
            'saved_jobs' => ['saved_jobs_created_at_index'],
            'job_alerts' => ['job_alerts_is_active_index', 'job_alerts_user_id_is_active_index'],
            'interviews' => ['interviews_scheduled_at_index', 'interviews_status_index', 'interviews_application_id_status_index'],
            'payment_transactions' => ['payment_transactions_status_index', 'payment_transactions_payment_gateway_index', 'payment_transactions_created_at_index'],
            'user_subscriptions' => ['user_subscriptions_status_index', 'user_subscriptions_expires_at_index', 'user_subscriptions_user_id_status_index'],
            'companies' => ['companies_industry_index', 'companies_verified_index'],
            'profiles' => ['profiles_location_index', 'profiles_total_experience_index'],
            'applications' => ['applications_job_id_status_index', 'applications_user_id_status_index', 'applications_submitted_at_index', 'applications_match_score_index', 'applications_source_index'],
            'jobs' => ['jobs_company_id_status_index', 'jobs_category_status_index', 'jobs_published_at_index', 'jobs_expires_at_index', 'jobs_work_mode_index', 'jobs_employment_type_index'],
            'users' => ['users_email_verified_at_index', 'users_account_type_index', 'users_created_at_index'],
        ];
        
        foreach ($tables as $table => $indexes) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) use ($indexes) {
                    foreach ($indexes as $index) {
                        if ($this->indexExists($table->getTable(), $index)) {
                            $table->dropIndex($index);
                        }
                    }
                });
            }
        }
    }
    
    /**
     * Check if an index exists
     */
    protected function indexExists(string $table, string $index): bool
    {
        try {
            $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]);
            return count($indexes) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
};
