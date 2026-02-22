<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompanyReview;
use App\Models\InterviewExperience;
use App\Models\SalaryReport;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompanyReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding Company Reviews, Salaries, and Interview Experiences...');

        // Get existing companies and users
        $companies = Company::all();
        $users = User::all();

        if ($companies->isEmpty()) {
            $this->command->warn('No companies found. Please seed companies first.');
            return;
        }

        if ($users->isEmpty()) {
            $this->command->warn('No users found. Please seed users first.');
            return;
        }

        // Employment types for company_reviews table
        $employmentTypesReviews = ['full_time', 'part_time', 'contract', 'internship'];
        // Employment types for salary_reports table (uses 'intern' not 'internship')
        $employmentTypesSalaries = ['full_time', 'part_time', 'contract', 'intern', 'freelance'];
        $experienceLevels = ['entry', 'mid', 'senior', 'lead', 'executive'];
        $interviewExperiences = ['positive', 'neutral', 'negative'];
        $interviewDifficulties = ['easy', 'average', 'difficult', 'very_difficult'];
        $applicationMethods = ['online', 'recruiter', 'referral', 'career_fair', 'campus', 'other'];
        $outcomes = ['got_offer', 'declined_offer', 'no_offer', 'pending', 'withdrew'];

        $jobTitles = [
            'Software Engineer', 'Senior Software Engineer', 'Product Manager',
            'Data Scientist', 'UX Designer', 'DevOps Engineer', 'Frontend Developer',
            'Backend Developer', 'Full Stack Developer', 'Engineering Manager',
            'Technical Lead', 'QA Engineer', 'Mobile Developer', 'Data Engineer',
            'Machine Learning Engineer', 'Security Engineer', 'Cloud Architect',
        ];

        $departments = [
            'Engineering', 'Product', 'Design', 'Marketing', 'Sales',
            'Human Resources', 'Finance', 'Operations', 'Customer Success',
        ];

        $locations = [
            'New York, NY', 'San Francisco, CA', 'Seattle, WA', 'Austin, TX',
            'Chicago, IL', 'Boston, MA', 'Denver, CO', 'Los Angeles, CA',
            'Remote', 'Hybrid - New York', 'Hybrid - San Francisco',
        ];

        $prosOptions = [
            'Great work-life balance',
            'Excellent benefits and compensation',
            'Strong engineering culture',
            'Good career growth opportunities',
            'Flexible remote work policy',
            'Collaborative team environment',
            'Cutting-edge technology stack',
            'Supportive management',
            'Learning opportunities',
            'Free meals and snacks',
        ];

        $consOptions = [
            'Long hours during crunch time',
            'Could improve internal communication',
            'Limited career growth in some areas',
            'High expectations and pressure',
            'Office politics',
            'Work-life balance could be better',
            'Bureaucracy in larger teams',
            'Salary could be more competitive',
            'Limited remote work options',
            'Fast-paced environment can be stressful',
        ];

        $adviceOptions = [
            'Focus more on employee well-being',
            'Increase transparency in decision making',
            'Invest more in training and development',
            'Improve promotion processes',
            'Better work-life balance initiatives',
            'More flexible working arrangements',
            'Keep up the great culture!',
        ];

        $interviewQuestions = [
            'Tell me about yourself',
            'Why do you want to work here?',
            'Describe a challenging project you worked on',
            'How do you handle conflicts with teammates?',
            'Where do you see yourself in 5 years?',
            'Design a URL shortener system',
            'Implement a LRU cache',
            'Find the median of two sorted arrays',
            'How would you improve our product?',
            'Tell me about a time you failed and what you learned',
        ];

        $benefitsOptions = [
            'health_insurance' => true,
            'dental_insurance' => true,
            'vision_insurance' => true,
            '401k_match' => true,
            'stock_options' => true,
            'remote_work' => true,
            'unlimited_pto' => false,
            'parental_leave' => true,
            'gym_membership' => true,
            'learning_budget' => true,
        ];

        // Seed reviews for each company
        foreach ($companies as $company) {
            $reviewCount = rand(3, 10);
            $usedUserIds = [];

            // Create reviews
            for ($i = 0; $i < $reviewCount; $i++) {
                $user = $users->whereNotIn('id', $usedUserIds)->random();
                $usedUserIds[] = $user->id;

                if (count($usedUserIds) >= $users->count()) {
                    break;
                }

                $rating = rand(25, 50) / 10; // 2.5 to 5.0

                CompanyReview::create([
                    'user_id' => $user->id,
                    'company_id' => $company->id,
                    'rating' => $rating,
                    'overall_rating' => $rating,
                    'headline' => $this->generateReviewTitle($rating),
                    'review_text' => $this->generateReviewText(),
                    'position' => $jobTitles[array_rand($jobTitles)],
                    'job_title' => $jobTitles[array_rand($jobTitles)],
                    'department' => $departments[array_rand($departments)],
                    'employment_type' => $employmentTypesReviews[array_rand($employmentTypesReviews)],
                    'is_current_employee' => rand(0, 1) === 1,
                    'pros' => implode("\n", array_map(fn() => $prosOptions[array_rand($prosOptions)], range(1, rand(2, 4)))),
                    'cons' => implode("\n", array_map(fn() => $consOptions[array_rand($consOptions)], range(1, rand(1, 3)))),
                    'advice_to_management' => $adviceOptions[array_rand($adviceOptions)],
                    'work_life_balance_rating' => rand(30, 50) / 10,
                    'compensation_rating' => rand(30, 50) / 10,
                    'career_growth_rating' => rand(25, 50) / 10,
                    'culture_rating' => rand(30, 50) / 10,
                    'management_rating' => rand(25, 50) / 10,
                    'recommend_to_friend' => rand(0, 10) > 3,
                    'ceo_approval' => rand(0, 10) > 2 ? true : (rand(0, 1) === 1 ? false : null),
                    'status' => 'approved',
                    'is_verified' => rand(0, 1) === 1,
                    'helpful_count' => rand(0, 50),
                ]);
            }

            // Create salary reports
            $salaryCount = rand(2, 6);
            $usedUserIds = [];

            for ($i = 0; $i < $salaryCount; $i++) {
                $user = $users->whereNotIn('id', $usedUserIds)->random();
                $usedUserIds[] = $user->id;

                if (count($usedUserIds) >= $users->count()) {
                    break;
                }

                $experienceLevel = $experienceLevels[array_rand($experienceLevels)];
                $baseSalary = $this->getSalaryForLevel($experienceLevel);

                SalaryReport::create([
                    'user_id' => $user->id,
                    'company_id' => $company->id,
                    'job_title' => $jobTitles[array_rand($jobTitles)],
                    'department' => $departments[array_rand($departments)],
                    'location' => $locations[array_rand($locations)],
                    'years_of_experience' => $this->getYearsForLevel($experienceLevel),
                    'years_at_company' => rand(1, 5),
                    'experience_level' => $experienceLevel,
                    'base_salary' => $baseSalary,
                    'bonus' => rand(0, 1) === 1 ? $baseSalary * (rand(5, 20) / 100) : null,
                    'stock_options' => rand(0, 1) === 1 ? rand(10000, 100000) : null,
                    'signing_bonus' => rand(0, 3) === 0 ? rand(5000, 30000) : null,
                    'pay_period' => 'yearly',
                    'currency' => 'USD',
                    'is_current_employee' => rand(0, 1) === 1,
                    'employment_type' => $employmentTypesSalaries[array_rand($employmentTypesSalaries)],
                    'benefits' => json_encode(array_filter($benefitsOptions, fn() => rand(0, 1) === 1)),
                    'is_verified' => rand(0, 1) === 1,
                    'status' => 'approved',
                    'is_anonymous' => true,
                ]);
            }

            // Create interview experiences
            $interviewCount = rand(2, 5);
            $usedUserIds = [];

            for ($i = 0; $i < $interviewCount; $i++) {
                $user = $users->whereNotIn('id', $usedUserIds)->random();
                $usedUserIds[] = $user->id;

                if (count($usedUserIds) >= $users->count()) {
                    break;
                }

                InterviewExperience::create([
                    'user_id' => $user->id,
                    'company_id' => $company->id,
                    'job_title' => $jobTitles[array_rand($jobTitles)],
                    'department' => $departments[array_rand($departments)],
                    'location' => $locations[array_rand($locations)],
                    'application_method' => $applicationMethods[array_rand($applicationMethods)],
                    'interview_date' => now()->subDays(rand(30, 365)),
                    'interview_duration' => ['less_than_1_hour', '1_2_hours', '2_4_hours', 'half_day'][array_rand(['less_than_1_hour', '1_2_hours', '2_4_hours', 'half_day'])],
                    'num_interviews' => rand(2, 6),
                    'experience' => $interviewExperiences[array_rand($interviewExperiences)],
                    'difficulty' => $interviewDifficulties[array_rand($interviewDifficulties)],
                    'outcome' => $outcomes[array_rand($outcomes)],
                    'interview_process' => $this->generateInterviewProcess(),
                    'interview_questions' => json_encode(array_map(fn() => $interviewQuestions[array_rand($interviewQuestions)], range(1, rand(3, 5)))),
                    'preparation_tips' => 'Study system design, practice coding problems, research the company culture.',
                    'advice_for_candidates' => 'Be yourself, ask thoughtful questions, and show enthusiasm for the role.',
                    'is_verified' => rand(0, 1) === 1,
                    'status' => 'approved',
                    'is_anonymous' => true,
                    'helpful_count' => rand(0, 30),
                    'view_count' => rand(10, 500),
                ]);
            }

            // Update company stats
            $this->updateCompanyStats($company);
        }

        $this->command->info('Company Reviews seeding completed!');
        $this->command->info('Created ' . CompanyReview::count() . ' reviews');
        $this->command->info('Created ' . SalaryReport::count() . ' salary reports');
        $this->command->info('Created ' . InterviewExperience::count() . ' interview experiences');
    }

    private function generateReviewTitle(float $rating): string
    {
        if ($rating >= 4.5) {
            return ['Amazing place to work!', 'Best job I ever had', 'Highly recommended company', 'Excellent workplace'][array_rand(['Amazing place to work!', 'Best job I ever had', 'Highly recommended company', 'Excellent workplace'])];
        } elseif ($rating >= 3.5) {
            return ['Good company overall', 'Solid place to work', 'Decent experience', 'Good but room for improvement'][array_rand(['Good company overall', 'Solid place to work', 'Decent experience', 'Good but room for improvement'])];
        } elseif ($rating >= 2.5) {
            return ['Mixed experience', 'It was okay', 'Some good, some bad', 'Average workplace'][array_rand(['Mixed experience', 'It was okay', 'Some good, some bad', 'Average workplace'])];
        } else {
            return ['Could be better', 'Not the best experience', 'Needs improvement', 'Disappointing'][array_rand(['Could be better', 'Not the best experience', 'Needs improvement', 'Disappointing'])];
        }
    }

    private function generateReviewText(): string
    {
        $texts = [
            'I have been working here for several years and overall it has been a positive experience. The team is collaborative and the work is interesting. There are always new challenges to tackle.',
            'Great company with strong values. Management genuinely cares about employees and their growth. The benefits are competitive and the culture is inclusive.',
            'Worked here as a software engineer and learned a lot. The tech stack is modern and there are opportunities to work on impactful projects. Would recommend to others.',
            'The company has its pros and cons. Good compensation but work-life balance could be better during peak periods. Overall a decent place to grow your career.',
            'Joined as a junior and was promoted within 2 years. The mentorship program is excellent and senior engineers are always willing to help. Strong engineering culture.',
        ];
        return $texts[array_rand($texts)];
    }

    private function generateInterviewProcess(): string
    {
        $processes = [
            'Started with a phone screen with HR, followed by a technical phone interview. Then had an on-site with 4 rounds including coding, system design, and behavioral interviews.',
            'Applied online and got a response within a week. Had 3 rounds of interviews - initial screen, technical assessment, and final round with the hiring manager.',
            'Recruiter reached out on LinkedIn. Process included take-home assignment followed by virtual interviews with the team. Very smooth and well-organized.',
            'Campus recruiting event led to first interview. Then had a super day with multiple interviews back-to-back. Got offer within a week.',
            'Referral from a friend who works there. Had coffee chat first, then formal interviews. Process took about 3 weeks from start to offer.',
        ];
        return $processes[array_rand($processes)];
    }

    private function getSalaryForLevel(string $level): float
    {
        return match ($level) {
            'entry' => rand(60000, 90000),
            'mid' => rand(90000, 130000),
            'senior' => rand(130000, 180000),
            'lead' => rand(160000, 220000),
            'executive' => rand(200000, 350000),
            default => rand(80000, 120000),
        };
    }

    private function getYearsForLevel(string $level): int
    {
        return match ($level) {
            'entry' => rand(0, 2),
            'mid' => rand(2, 5),
            'senior' => rand(5, 10),
            'lead' => rand(8, 15),
            'executive' => rand(12, 25),
            default => rand(2, 8),
        };
    }

    private function updateCompanyStats(Company $company): void
    {
        $reviews = $company->reviews()->where('status', 'approved')->get();
        $salaries = SalaryReport::where('company_id', $company->id)->where('status', 'approved')->get();
        $interviews = InterviewExperience::where('company_id', $company->id)->where('status', 'approved')->get();

        $company->update([
            'avg_rating' => $reviews->avg('rating') ?? $reviews->avg('overall_rating'),
            'total_reviews' => $reviews->count(),
            'total_salaries' => $salaries->count(),
            'total_interviews' => $interviews->count(),
            'recommend_percent' => $reviews->count() > 0
                ? (int) ($reviews->where('recommend_to_friend', true)->count() / $reviews->count() * 100)
                : null,
            'ceo_approval_percent' => $reviews->whereNotNull('ceo_approval')->count() > 0
                ? (int) ($reviews->where('ceo_approval', true)->count() / $reviews->whereNotNull('ceo_approval')->count() * 100)
                : null,
            'avg_salary' => $salaries->avg('base_salary'),
        ]);
    }
}
