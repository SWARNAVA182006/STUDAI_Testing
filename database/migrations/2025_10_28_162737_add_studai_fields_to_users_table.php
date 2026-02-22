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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->enum('account_type', ['job_seeker', 'employer', 'admin'])->default('job_seeker')->after('password');
            $table->string('avatar')->nullable()->after('account_type');
            $table->boolean('is_active')->default(true)->after('avatar');
            $table->json('preferences')->nullable()->after('is_active');
            $table->timestamp('last_login_at')->nullable()->after('preferences');
            $table->string('timezone')->default('UTC')->after('last_login_at');
            $table->softDeletes()->after('updated_at');
            
            $table->index(['email', 'account_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['email', 'account_type']);
            $table->dropColumn([
                'phone', 'account_type', 'avatar', 'is_active', 
                'preferences', 'last_login_at', 'timezone', 'deleted_at'
            ]);
        });
    }
};
