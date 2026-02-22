<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Models\Profile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Creates a super admin user with access to ALL features:
     * - Admin panel access
     * - Job seeker features (AI agent, negotiation, skills)
     * - Employer features (job posting, applications)
     * - Marketplace features
     */
    public function run(): void
    {
        // Create super admin user
        $user = User::firstOrCreate(
            ['email' => 'admin@studaipath.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('StudAI@2024!'),
                'account_type' => 'admin',
                'email_verified_at' => now(),
                'is_active' => true,
                'phone' => '+1234567890',
                'timezone' => 'America/New_York',
                'preferences' => [
                    'notifications' => true,
                    'email_updates' => true,
                    'theme' => 'light',
                ],
            ]
        );

        // Assign super admin role (with all permissions)
        $user->assignRole('super_admin');
        $user->assignRole('admin');
        $user->assignRole('employer');
        $user->assignRole('job_seeker');
        
        // Create profile for job seeker features
        Profile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'headline' => 'Platform Administrator & Developer',
                'summary' => 'Super admin account with full access to all platform features for testing and administration.',
                'current_location' => 'San Francisco, CA',
                'skills' => ['Laravel', 'PHP', 'AI/ML', 'Leadership', 'Product Management'],
                'languages' => ['English', 'Spanish'],
                'education' => [
                    [
                        'degree' => 'Master of Computer Science',
                        'institution' => 'Stanford University',
                        'year' => 2018,
                    ],
                ],
                'experience' => [
                    [
                        'title' => 'Chief Technology Officer',
                        'company' => 'StudAI Path',
                        'start_date' => '2020-01-01',
                        'current' => true,
                        'description' => 'Leading technical vision and development of the AI-powered career platform.',
                    ],
                ],
                'expected_salary_min' => 200000,
                'expected_salary_max' => 350000,
                'work_preference' => 'hybrid',
                'notice_period' => 'immediately',
                'is_public' => false,
                'open_to_opportunities' => true,
                'profile_completeness' => 85,
            ]
        );

        $this->command->info('Super Admin created successfully!');
        $this->command->info('Email: admin@studaipath.com');
        $this->command->info('Password: StudAI@2024!');
        $this->command->newLine();
        $this->command->warn('⚠️  Please change the password after first login!');
    }
}
