<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\AgentConfiguration;
use App\Models\JobSource;
use App\Models\DiscoveredJob;
use App\Models\JobMatch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AgentConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first job seeker user
        $user = User::where('account_type', 'job_seeker')->first();
        
        if (!$user) {
            $this->command->warn('No job seeker users found. Creating one...');
            $user = User::create([
                'name' => 'Demo Job Seeker',
                'email' => 'jobseeker@example.com',
                'password' => bcrypt('password'),
                'account_type' => 'job_seeker',
                'is_active' => true,
            ]);
        }

        // Create agent configuration for the user (or skip if exists)
        $config = AgentConfiguration::where('user_id', $user->id)->first();
        
        if (!$config) {
            $config = AgentConfiguration::create([
            'user_id' => $user->id,
            'is_active' => true,
            'daily_application_limit' => 5,
            'target_roles' => json_encode(['Laravel Developer', 'Backend Developer', 'PHP Developer']),
            'preferred_locations' => json_encode(['Bangalore', 'Remote', 'Pune']),
            'required_skills' => json_encode(['Laravel', 'PHP', 'MySQL', 'REST API']),
            'nice_to_have_skills' => json_encode(['Docker', 'Redis', 'Vue.js']),
            'min_salary' => 800000, // 8 LPA
            'max_salary' => 1500000, // 15 LPA
            'salary_period' => 'yearly',
            'work_arrangements' => json_encode(['remote', 'hybrid']),
            'employment_types' => json_encode(['full-time', 'contract']),
            'min_experience_years' => 2,
            'max_experience_years' => 5,
            'excluded_keywords' => json_encode(['sales', 'cold calling']),
            'only_verified_companies' => false,
            'require_visa_sponsorship' => false,
            'application_aggressiveness' => 'moderate',
            'match_threshold_percentage' => 70,
            'auto_follow_up' => true,
            'follow_up_days' => 7,
            'enable_learning' => true,
            'active_hours' => json_encode(['start' => '09:00', 'end' => '18:00']),
            'active_days' => json_encode(['monday', 'tuesday', 'wednesday', 'thursday', 'friday']),
            'next_run_at' => now()->addHours(4),
        ]);

            $this->command->info("Created agent configuration for user: {$user->name}");
        } else {
            $this->command->info("Agent configuration already exists for user: {$user->name}");
        }

        // Create job sources
        $sources = [
            [
                'name' => 'LinkedIn',
                'type' => 'scraper',
                'url' => 'https://www.linkedin.com/jobs',
                'is_active' => true,
                'priority' => 1,
                'scraping_config' => json_encode([
                    'selectors' => [
                        'job_card' => '.job-search-card',
                        'title' => '.job-search-card__title',
                        'company' => '.job-search-card__company-name',
                    ],
                ]),
            ],
            [
                'name' => 'Naukri',
                'type' => 'scraper',
                'url' => 'https://www.naukri.com',
                'is_active' => true,
                'priority' => 2,
                'scraping_config' => json_encode([]),
            ],
            [
                'name' => 'Indeed',
                'type' => 'scraper',
                'url' => 'https://www.indeed.co.in',
                'is_active' => true,
                'priority' => 3,
                'scraping_config' => json_encode([]),
            ],
        ];

        foreach ($sources as $sourceData) {
            JobSource::create($sourceData);
        }

        $this->command->info('Created ' . count($sources) . ' job sources');

        $this->command->info('Agent configuration seeding completed successfully!');
        $this->command->info('');
        $this->command->info('You can now test the autonomous agent by running:');
        $this->command->info('  php artisan tinker --execute="App\Jobs\ProcessAutoApplications::dispatch()"');
        $this->command->info('  php artisan queue:work --once');
        $this->command->info('');
        $this->command->info('Note: No discovered jobs were created. The agent will need job sources to scrape or');
        $this->command->info('you can manually create DiscoveredJob records for testing.');
    }
}
