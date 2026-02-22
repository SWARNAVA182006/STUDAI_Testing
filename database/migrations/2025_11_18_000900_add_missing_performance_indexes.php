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
        // Applications indexes
        if (Schema::hasTable('applications')) {
            if (!$this->indexExists('applications', 'applications_created_at_index')) {
                Schema::table('applications', function (Blueprint $table) {
                    $table->index('created_at', 'applications_created_at_index');
                });
            }

            if (!$this->indexExists('applications', 'applications_status_created_at_index')) {
                Schema::table('applications', function (Blueprint $table) {
                    $table->index(['status', 'created_at'], 'applications_status_created_at_index');
                });
            }
        }

        // Job listings indexes
        if (Schema::hasTable('job_listings')) {
            // Only add status+expires_at index if both columns exist
            if (Schema::hasColumn('job_listings', 'status') && Schema::hasColumn('job_listings', 'expires_at') && !$this->indexExists('job_listings', 'job_listings_status_expires_at_index')) {
                Schema::table('job_listings', function (Blueprint $table) {
                    $table->index(['status', 'expires_at'], 'job_listings_status_expires_at_index');
                });
            }

            // Only add company_id+status+expires_at index if all columns exist
            if (Schema::hasColumn('job_listings', 'company_id') && Schema::hasColumn('job_listings', 'status') && Schema::hasColumn('job_listings', 'expires_at') && !$this->indexExists('job_listings', 'job_listings_company_id_status_expires_index')) {
                Schema::table('job_listings', function (Blueprint $table) {
                    $table->index(['company_id', 'status', 'expires_at'], 'job_listings_company_id_status_expires_index');
                });
            }

            if (Schema::hasColumn('job_listings', 'location') && !$this->indexExists('job_listings', 'job_listings_location_index')) {
                Schema::table('job_listings', function (Blueprint $table) {
                    $table->index('location', 'job_listings_location_index');
                });
            }
        }

        // Users indexes
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'company_id')) {
            if (!$this->indexExists('users', 'users_company_id_index')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->index('company_id', 'users_company_id_index');
                });
            }

            if (!$this->indexExists('users', 'users_company_id_account_type_index') && Schema::hasColumn('users', 'account_type')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->index(['company_id', 'account_type'], 'users_company_id_account_type_index');
                });
            }
        }

        // Talent pool indexes
        if (Schema::hasTable('talent_pool')) {
            if (!$this->indexExists('talent_pool', 'talent_pool_company_id_index')) {
                Schema::table('talent_pool', function (Blueprint $table) {
                    $table->index('company_id', 'talent_pool_company_id_index');
                });
            }

            if (!$this->indexExists('talent_pool', 'talent_pool_company_id_is_active_index')) {
                Schema::table('talent_pool', function (Blueprint $table) {
                    $table->index(['company_id', 'is_active'], 'talent_pool_company_id_is_active_index');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('applications')) {
            if ($this->indexExists('applications', 'applications_created_at_index')) {
                Schema::table('applications', function (Blueprint $table) {
                    $table->dropIndex('applications_created_at_index');
                });
            }
            if ($this->indexExists('applications', 'applications_status_created_at_index')) {
                Schema::table('applications', function (Blueprint $table) {
                    $table->dropIndex('applications_status_created_at_index');
                });
            }
        }

        if (Schema::hasTable('job_listings')) {
            if ($this->indexExists('job_listings', 'job_listings_status_expires_at_index')) {
                Schema::table('job_listings', function (Blueprint $table) {
                    $table->dropIndex('job_listings_status_expires_at_index');
                });
            }
            if ($this->indexExists('job_listings', 'job_listings_company_id_status_expires_index')) {
                Schema::table('job_listings', function (Blueprint $table) {
                    $table->dropIndex('job_listings_company_id_status_expires_index');
                });
            }
            if ($this->indexExists('job_listings', 'job_listings_location_index')) {
                Schema::table('job_listings', function (Blueprint $table) {
                    $table->dropIndex('job_listings_location_index');
                });
            }
        }

        if (Schema::hasTable('users')) {
            if ($this->indexExists('users', 'users_company_id_index')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->dropIndex('users_company_id_index');
                });
            }
            if ($this->indexExists('users', 'users_company_id_account_type_index')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->dropIndex('users_company_id_account_type_index');
                });
            }
        }

        if (Schema::hasTable('talent_pool')) {
            if ($this->indexExists('talent_pool', 'talent_pool_company_id_index')) {
                Schema::table('talent_pool', function (Blueprint $table) {
                    $table->dropIndex('talent_pool_company_id_index');
                });
            }
            if ($this->indexExists('talent_pool', 'talent_pool_company_id_is_active_index')) {
                Schema::table('talent_pool', function (Blueprint $table) {
                    $table->dropIndex('talent_pool_company_id_is_active_index');
                });
            }
        }
    }

    protected function indexExists(string $table, string $index): bool
    {
        try {
            $result = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]);
            return count($result) > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }
};
