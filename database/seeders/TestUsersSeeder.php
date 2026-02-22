<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TestUsersSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Student (Job Seeker)
        $student = User::firstOrCreate(
            ['email' => 'user@user.com'],
            [
                'name' => 'Test Student',
                'password' => Hash::make('12345678'),
                'account_type' => 'job_seeker',
                'email_verified_at' => now(),
            ]
        );
        $this->command->info('Student user created: user@user.com');

        // 2. Employee (Employer)
        $employer = User::firstOrCreate(
            ['email' => 'company@user.com'],
            [
                'name' => 'Test Employer',
                'password' => Hash::make('12345678'),
                'account_type' => 'employer',
                'email_verified_at' => now(),
            ]
        );
        $this->command->info('Employer user created: company@user.com');

        // Create a company for the employer if not exists
        // Check if user has company_id column or relationship logic
        // Based on User model belongsTo Company, users table should have company_id
        
        if (!$employer->company_id) {
            $company = Company::firstOrCreate(
                ['name' => 'Test Company'],
                [
                    'slug' => 'test-company-' . Str::random(5),
                    'industry' => 'Technology',
                    'description' => 'A test company for development purposes.',
                    'is_verified' => true,
                ]
            );
            
            $employer->company_id = $company->id;
            $employer->save();
            $this->command->info('Company created and assigned to employer.');
        }

        // 3. Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@user.com'],
            [
                'name' => 'Test Admin',
                'password' => Hash::make('12345678'),
                'account_type' => 'admin',
                'email_verified_at' => now(),
            ]
        );
        $this->command->info('Admin user created: admin@user.com');
    }
}
