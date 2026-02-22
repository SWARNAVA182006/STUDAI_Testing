<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Company;
use App\Models\Job;
use App\Models\Application;
use App\Models\Profile;
use App\Models\UserSubscription;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SampleDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding sample data...');

        // Create subscription plans first if they don't exist
        $this->command->info('Creating subscription plans...');
        $this->seedSubscriptionPlans();
        $this->command->info('✓ Subscription plans created');

        // Create sample companies
        $this->command->info('Creating companies...');
        $companies = $this->seedCompanies();
        $this->command->info('✓ ' . count($companies) . ' companies created');

        // Create sample employers (company users)
        $this->command->info('Creating employers...');
        $employers = $this->seedEmployers($companies);
        $this->command->info('✓ ' . count($employers) . ' employers created');

        // Create sample job seekers
        $this->command->info('Creating job seekers...');
        $jobSeekers = $this->seedJobSeekers();
        $this->command->info('✓ ' . count($jobSeekers) . ' job seekers created');

        // Create sample jobs
        $this->command->info('Creating jobs...');
        $jobs = $this->seedJobs($companies);
        $this->command->info('✓ ' . count($jobs) . ' jobs created');

        // Create sample applications
        $this->command->info('Creating applications...');
        $this->seedApplications($jobSeekers, $jobs);
        $this->command->info('✓ Applications created');

        $this->command->info('Sample data seeded successfully!');
    }

    private function seedSubscriptionPlans(): void
    {
        $plans = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'price' => 0,
                'billing_period' => 'monthly',
                'applications_limit' => 10,
                'ai_credits' => 5,
                'features' => json_encode(['Basic job search', '10 applications/month', '5 AI credits']),
                'is_active' => true,
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'price' => 999,
                'billing_period' => 'monthly',
                'applications_limit' => 100,
                'ai_credits' => 100,
                'features' => json_encode(['Unlimited job search', '100 applications/month', '100 AI credits', 'Priority support']),
                'is_active' => true,
            ],
            [
                'name' => 'Premium',
                'slug' => 'premium',
                'price' => 2499,
                'billing_period' => 'monthly',
                'applications_limit' => 9999,
                'ai_credits' => 9999,
                'features' => json_encode(['Unlimited everything', 'Autonomous agent', 'Resume builder', 'Interview coaching']),
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::firstOrCreate(['slug' => $plan['slug']], $plan);
        }
    }

    private function seedCompanies(): array
    {
        $companies = [
            [
                'name' => 'TechFlow Solutions',
                'slug' => 'techflow-solutions',
                'industry' => 'Information Technology',
                'company_size' => '201-500',
                'founded_year' => 2015,
                'headquarters' => 'Bangalore, India',
                'website' => 'https://techflow.example.com',
                'description' => 'TechFlow Solutions is a leading software development company specializing in enterprise solutions, cloud computing, and digital transformation. We help businesses modernize their technology stack and achieve digital excellence.',
                'is_verified' => true,
                'is_featured' => true,
                'avg_rating' => 4.5,
                'total_reviews' => 128,
                'culture_rating' => 4.6,
                'recommend_percent' => 85,
                'benefits' => json_encode(['Health Insurance', 'Remote Work', 'Stock Options', 'Learning Budget', 'Gym Membership']),
                'tech_stack' => json_encode(['Laravel', 'React', 'AWS', 'Docker', 'MySQL']),
            ],
            [
                'name' => 'InnovateLabs India',
                'slug' => 'innovatelabs-india',
                'industry' => 'Software Development',
                'company_size' => '51-200',
                'founded_year' => 2018,
                'headquarters' => 'Hyderabad, India',
                'website' => 'https://innovatelabs.example.com',
                'description' => 'InnovateLabs is a product-focused startup building cutting-edge AI and machine learning solutions. We are passionate about using technology to solve real-world problems.',
                'is_verified' => true,
                'is_featured' => true,
                'avg_rating' => 4.7,
                'total_reviews' => 67,
                'culture_rating' => 4.8,
                'recommend_percent' => 92,
                'benefits' => json_encode(['Unlimited PTO', 'Remote First', 'ESOP', 'Conference Budget', 'Mental Health Support']),
                'tech_stack' => json_encode(['Python', 'TensorFlow', 'PyTorch', 'Kubernetes', 'GCP']),
            ],
            [
                'name' => 'Global Finance Corp',
                'slug' => 'global-finance-corp',
                'industry' => 'Financial Services',
                'company_size' => '1001-5000',
                'founded_year' => 1998,
                'headquarters' => 'Mumbai, India',
                'website' => 'https://globalfinance.example.com',
                'description' => 'Global Finance Corp is a multinational financial services company offering banking, investment, and insurance products. We serve millions of customers across 20+ countries.',
                'is_verified' => true,
                'is_featured' => false,
                'avg_rating' => 4.1,
                'total_reviews' => 342,
                'culture_rating' => 3.9,
                'recommend_percent' => 78,
                'benefits' => json_encode(['Health Insurance', 'Life Insurance', 'Pension', 'Performance Bonus', 'Car Allowance']),
                'tech_stack' => json_encode(['Java', 'Spring Boot', 'Oracle', 'Angular', 'Azure']),
            ],
            [
                'name' => 'HealthTech Pro',
                'slug' => 'healthtech-pro',
                'industry' => 'Healthcare Technology',
                'company_size' => '51-200',
                'founded_year' => 2020,
                'headquarters' => 'Pune, India',
                'website' => 'https://healthtechpro.example.com',
                'description' => 'HealthTech Pro is revolutionizing healthcare delivery through innovative technology solutions. Our platform connects patients, doctors, and healthcare providers seamlessly.',
                'is_verified' => true,
                'is_featured' => true,
                'avg_rating' => 4.6,
                'total_reviews' => 45,
                'culture_rating' => 4.7,
                'recommend_percent' => 88,
                'benefits' => json_encode(['Health Insurance', 'Flexible Hours', 'Remote Work', 'Learning Budget', 'Team Outings']),
                'tech_stack' => json_encode(['Node.js', 'React Native', 'MongoDB', 'AWS', 'GraphQL']),
            ],
            [
                'name' => 'CloudNine Technologies',
                'slug' => 'cloudnine-technologies',
                'industry' => 'Cloud Computing',
                'company_size' => '201-500',
                'founded_year' => 2016,
                'headquarters' => 'Gurgaon, India',
                'website' => 'https://cloudnine.example.com',
                'description' => 'CloudNine Technologies provides enterprise cloud infrastructure and DevOps solutions. We help companies migrate to the cloud and optimize their infrastructure costs.',
                'is_verified' => true,
                'is_featured' => false,
                'avg_rating' => 4.3,
                'total_reviews' => 89,
                'culture_rating' => 4.4,
                'recommend_percent' => 82,
                'benefits' => json_encode(['Health Insurance', 'Work From Home', 'Certification Sponsorship', 'Stock Options']),
                'tech_stack' => json_encode(['Kubernetes', 'Terraform', 'AWS', 'Azure', 'Docker']),
            ],
            [
                'name' => 'EduLearn Academy',
                'slug' => 'edulearn-academy',
                'industry' => 'EdTech',
                'company_size' => '11-50',
                'founded_year' => 2021,
                'headquarters' => 'Chennai, India',
                'website' => 'https://edulearn.example.com',
                'description' => 'EduLearn Academy is an ed-tech startup making quality education accessible to everyone. Our platform offers courses in technology, business, and creative fields.',
                'is_verified' => true,
                'is_featured' => false,
                'avg_rating' => 4.4,
                'total_reviews' => 23,
                'culture_rating' => 4.6,
                'recommend_percent' => 90,
                'benefits' => json_encode(['Free Courses', 'Flexible Hours', 'Remote Work', 'Learning Stipend']),
                'tech_stack' => json_encode(['Vue.js', 'Laravel', 'PostgreSQL', 'Redis', 'S3']),
            ],
        ];

        $createdCompanies = [];
        foreach ($companies as $company) {
            $createdCompanies[] = Company::firstOrCreate(
                ['slug' => $company['slug']],
                $company
            );
        }

        return $createdCompanies;
    }

    private function seedEmployers(array $companies): array
    {
        $employers = [];
        
        foreach ($companies as $index => $company) {
            $employer = User::firstOrCreate(
                ['email' => 'employer' . ($index + 1) . '@example.com'],
                [
                    'name' => 'HR Manager - ' . $company->name,
                    'email' => 'employer' . ($index + 1) . '@example.com',
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'account_type' => 'employer',
                    'is_active' => true,
                ]
            );
            
            // Associate with company
            $employer->company_id = $company->id;
            $employer->save();
            
            $employers[] = $employer;
        }

        return $employers;
    }

    private function seedJobSeekers(): array
    {
        $jobSeekers = [
            [
                'name' => 'Rahul Sharma',
                'email' => 'rahul.sharma@example.com',
                'profile' => [
                    'headline' => 'Full Stack Developer | Laravel Expert',
                    'current_location' => 'Bangalore, India',
                    'summary' => 'Experienced Full Stack Developer with 5+ years of expertise in Laravel, React, and cloud technologies. Passionate about building scalable applications and mentoring junior developers.',
                    'skills' => ['Laravel', 'PHP', 'React', 'JavaScript', 'MySQL', 'AWS', 'Docker', 'Git'],
                    'expected_salary_min' => 2000000,
                    'expected_salary_max' => 2500000,
                    'notice_period' => '30 days',
                    'work_preference' => 'remote',
                ],
            ],
            [
                'name' => 'Priya Patel',
                'email' => 'priya.patel@example.com',
                'profile' => [
                    'headline' => 'Data Scientist | Machine Learning Expert',
                    'current_location' => 'Mumbai, India',
                    'summary' => 'Data Scientist with 3 years of experience in machine learning, deep learning, and statistical analysis. Strong background in Python and TensorFlow.',
                    'skills' => ['Python', 'TensorFlow', 'PyTorch', 'Machine Learning', 'Data Analysis', 'SQL', 'Pandas', 'Scikit-learn'],
                    'expected_salary_min' => 1800000,
                    'expected_salary_max' => 2200000,
                    'notice_period' => '60 days',
                    'work_preference' => 'hybrid',
                ],
            ],
            [
                'name' => 'Amit Kumar',
                'email' => 'amit.kumar@example.com',
                'profile' => [
                    'headline' => 'DevOps Engineer | Kubernetes Specialist',
                    'current_location' => 'Hyderabad, India',
                    'summary' => 'DevOps Engineer with 4 years of experience in CI/CD, Kubernetes, and cloud infrastructure. Expert in automating deployment pipelines and infrastructure as code.',
                    'skills' => ['Kubernetes', 'Docker', 'AWS', 'Terraform', 'Jenkins', 'Linux', 'Python', 'Ansible'],
                    'expected_salary_min' => 2000000,
                    'expected_salary_max' => 2300000,
                    'notice_period' => '45 days',
                    'work_preference' => 'remote',
                ],
            ],
            [
                'name' => 'Sneha Reddy',
                'email' => 'sneha.reddy@example.com',
                'profile' => [
                    'headline' => 'UI/UX Designer | Design Systems Expert',
                    'current_location' => 'Chennai, India',
                    'summary' => 'UI/UX Designer with 6 years of experience creating user-centered designs for web and mobile applications. Proficient in Figma, Adobe XD, and design systems.',
                    'skills' => ['Figma', 'Adobe XD', 'UI Design', 'UX Research', 'Prototyping', 'Design Systems', 'HTML', 'CSS'],
                    'expected_salary_min' => 1600000,
                    'expected_salary_max' => 2000000,
                    'notice_period' => '30 days',
                    'work_preference' => 'hybrid',
                ],
            ],
            [
                'name' => 'Vikram Singh',
                'email' => 'vikram.singh@example.com',
                'profile' => [
                    'headline' => 'Product Manager | Agile Expert',
                    'current_location' => 'Delhi, India',
                    'summary' => 'Product Manager with 7 years of experience driving product strategy and execution. Strong background in agile methodologies and cross-functional team leadership.',
                    'skills' => ['Product Management', 'Agile', 'Scrum', 'User Research', 'Roadmapping', 'Analytics', 'SQL', 'Jira'],
                    'expected_salary_min' => 3000000,
                    'expected_salary_max' => 3500000,
                    'notice_period' => '60 days',
                    'work_preference' => 'onsite',
                ],
            ],
            [
                'name' => 'Ananya Gupta',
                'email' => 'ananya.gupta@example.com',
                'profile' => [
                    'headline' => 'Fresh Graduate | Web Developer',
                    'current_location' => 'Pune, India',
                    'summary' => 'Fresh graduate with strong foundation in computer science and internship experience in web development. Eager to learn and grow in a dynamic environment.',
                    'skills' => ['JavaScript', 'React', 'Node.js', 'HTML', 'CSS', 'Git', 'MongoDB'],
                    'expected_salary_min' => 500000,
                    'expected_salary_max' => 700000,
                    'notice_period' => 'Immediate',
                    'work_preference' => 'hybrid',
                ],
            ],
        ];

        $freePlan = SubscriptionPlan::where('slug', 'free')->first();
        $proPlan = SubscriptionPlan::where('slug', 'pro')->first();
        
        $createdJobSeekers = [];
        
        foreach ($jobSeekers as $index => $seeker) {
            $user = User::firstOrCreate(
                ['email' => $seeker['email']],
                [
                    'name' => $seeker['name'],
                    'email' => $seeker['email'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'account_type' => 'job_seeker',
                    'is_active' => true,
                ]
            );

            // Create profile using correct model
            Profile::firstOrCreate(
                ['user_id' => $user->id],
                array_merge($seeker['profile'], [
                    'user_id' => $user->id,
                    'is_public' => true,
                    'open_to_opportunities' => true,
                    'profile_completeness' => 75,
                ])
            );

            // Create subscription using correct model (alternate between free and pro)
            $plan = $index % 2 === 0 ? $freePlan : $proPlan;
            if ($plan) {
                UserSubscription::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'user_id' => $user->id,
                        'subscription_plan_id' => $plan->id,
                        'status' => 'active',
                        'starts_at' => now(),
                        'ends_at' => now()->addMonth(),
                        'current_period_start' => now(),
                        'current_period_end' => now()->addMonth(),
                        'applications_used_this_month' => rand(0, 5),
                        'ai_credits_used_this_month' => rand(0, 3),
                    ]
                );
            }

            $createdJobSeekers[] = $user;
        }

        return $createdJobSeekers;
    }

    private function seedJobs(array $companies): array
    {
        $jobs = [
            // TechFlow Solutions jobs
            [
                'company_index' => 0,
                'title' => 'Senior Laravel Developer',
                'description' => "We are looking for an experienced Senior Laravel Developer to join our growing team. You will be responsible for developing and maintaining web applications using Laravel framework.\n\nKey Responsibilities:\n- Design and implement robust, scalable web applications\n- Write clean, maintainable, and well-documented code\n- Collaborate with cross-functional teams to define and implement new features\n- Mentor junior developers and conduct code reviews\n- Participate in architecture and design discussions",
                'responsibilities' => "- Lead development of complex features and modules\n- Ensure code quality through reviews and testing\n- Optimize application performance\n- Document technical specifications\n- Collaborate with frontend developers",
                'location' => 'Bangalore, India',
                'work_mode' => 'hybrid',
                'employment_type' => 'full-time',
                'experience_level' => 'senior',
                'salary_min' => 2000000,
                'salary_max' => 3500000,
                'required_skills' => json_encode(['Laravel', 'PHP', 'MySQL', 'Redis', 'REST APIs', 'Git']),
                'benefits' => json_encode(['Health Insurance', 'Stock Options', 'Remote Work', 'Learning Budget']),
            ],
            [
                'company_index' => 0,
                'title' => 'React Frontend Developer',
                'description' => "Join our frontend team to build beautiful, responsive user interfaces. You'll work with modern React ecosystem and collaborate closely with designers and backend developers.\n\nWhat you'll do:\n- Build reusable components and libraries\n- Implement responsive designs from Figma mockups\n- Write unit and integration tests\n- Optimize application performance",
                'responsibilities' => "- Develop new user-facing features using React.js\n- Build reusable components and front-end libraries\n- Translate designs and wireframes into high-quality code\n- Optimize components for maximum performance",
                'location' => 'Bangalore, India',
                'work_mode' => 'remote',
                'employment_type' => 'full-time',
                'experience_level' => 'mid',
                'salary_min' => 1200000,
                'salary_max' => 2000000,
                'required_skills' => json_encode(['React', 'JavaScript', 'TypeScript', 'CSS', 'Redux', 'Git']),
                'benefits' => json_encode(['Health Insurance', 'Remote Work', 'Flexible Hours']),
            ],
            // InnovateLabs jobs
            [
                'company_index' => 1,
                'title' => 'Machine Learning Engineer',
                'description' => "We're looking for an ML Engineer to help build and deploy machine learning models at scale. You'll work on cutting-edge AI projects that impact millions of users.\n\nYou will:\n- Design and implement ML pipelines\n- Train and optimize deep learning models\n- Deploy models to production\n- Collaborate with data scientists and engineers",
                'responsibilities' => "- Build end-to-end ML pipelines\n- Optimize model performance and inference speed\n- Implement A/B testing frameworks\n- Monitor model performance in production",
                'location' => 'Hyderabad, India',
                'work_mode' => 'hybrid',
                'employment_type' => 'full-time',
                'experience_level' => 'mid',
                'salary_min' => 1800000,
                'salary_max' => 2800000,
                'required_skills' => json_encode(['Python', 'TensorFlow', 'PyTorch', 'MLOps', 'Docker', 'AWS']),
                'benefits' => json_encode(['ESOP', 'Unlimited PTO', 'Remote Work', 'Conference Budget']),
            ],
            [
                'company_index' => 1,
                'title' => 'AI Research Scientist',
                'description' => "Join our research team to push the boundaries of AI. You'll work on novel algorithms and publish research papers at top conferences.\n\nIdeal candidate has:\n- PhD or Masters in CS/ML\n- Publication track record\n- Strong mathematical foundation\n- Passion for research",
                'responsibilities' => "- Conduct original research in AI/ML\n- Publish papers at top venues\n- Collaborate with engineering teams\n- Mentor junior researchers",
                'location' => 'Remote',
                'work_mode' => 'remote',
                'employment_type' => 'full-time',
                'experience_level' => 'senior',
                'salary_min' => 3000000,
                'salary_max' => 5000000,
                'required_skills' => json_encode(['Deep Learning', 'NLP', 'Computer Vision', 'Python', 'Research']),
                'benefits' => json_encode(['Research Budget', 'Conference Travel', 'Flexible Hours', 'Publication Bonus']),
            ],
            // Global Finance Corp jobs
            [
                'company_index' => 2,
                'title' => 'Full Stack Developer (Fintech)',
                'description' => "Build secure, scalable financial applications. You'll work on payment systems, trading platforms, and banking solutions.\n\nRequirements:\n- Strong background in secure coding\n- Experience with financial systems\n- Knowledge of compliance requirements",
                'responsibilities' => "- Develop secure financial applications\n- Implement payment integrations\n- Ensure compliance with regulations\n- Optimize transaction processing",
                'location' => 'Mumbai, India',
                'work_mode' => 'on-site',
                'employment_type' => 'full-time',
                'experience_level' => 'mid',
                'salary_min' => 1500000,
                'salary_max' => 2500000,
                'required_skills' => json_encode(['Java', 'Spring Boot', 'React', 'PostgreSQL', 'Security']),
                'benefits' => json_encode(['Performance Bonus', 'Health Insurance', 'Pension']),
            ],
            [
                'company_index' => 2,
                'title' => 'Data Analyst',
                'description' => "Analyze financial data to drive business decisions. You'll work with large datasets to uncover insights and create reports for stakeholders.",
                'responsibilities' => "- Analyze financial data and trends\n- Create dashboards and reports\n- Present findings to stakeholders\n- Collaborate with business teams",
                'location' => 'Mumbai, India',
                'work_mode' => 'hybrid',
                'employment_type' => 'full-time',
                'experience_level' => 'entry',
                'salary_min' => 800000,
                'salary_max' => 1200000,
                'required_skills' => json_encode(['SQL', 'Python', 'Excel', 'Tableau', 'Statistics']),
                'benefits' => json_encode(['Learning Budget', 'Health Insurance', 'Flexible Hours']),
            ],
            // HealthTech Pro jobs
            [
                'company_index' => 3,
                'title' => 'Backend Developer (Node.js)',
                'description' => "Build APIs and services for our healthcare platform. You'll work on patient data management, appointment scheduling, and telemedicine features.",
                'responsibilities' => "- Design and implement RESTful APIs\n- Ensure HIPAA compliance\n- Optimize database performance\n- Write comprehensive tests",
                'location' => 'Pune, India',
                'work_mode' => 'hybrid',
                'employment_type' => 'full-time',
                'experience_level' => 'mid',
                'salary_min' => 1400000,
                'salary_max' => 2200000,
                'required_skills' => json_encode(['Node.js', 'TypeScript', 'MongoDB', 'REST APIs', 'AWS']),
                'benefits' => json_encode(['Health Insurance', 'Remote Work', 'Learning Budget']),
            ],
            [
                'company_index' => 3,
                'title' => 'Mobile Developer (React Native)',
                'description' => "Build our patient-facing mobile app used by thousands of users daily. Focus on performance, accessibility, and great user experience.",
                'responsibilities' => "- Develop cross-platform mobile features\n- Implement offline-first architecture\n- Ensure accessibility compliance\n- Collaborate with design team",
                'location' => 'Pune, India',
                'work_mode' => 'remote',
                'employment_type' => 'full-time',
                'experience_level' => 'mid',
                'salary_min' => 1500000,
                'salary_max' => 2400000,
                'required_skills' => json_encode(['React Native', 'JavaScript', 'TypeScript', 'iOS', 'Android']),
                'benefits' => json_encode(['Health Insurance', 'Remote Work', 'Device Allowance']),
            ],
            // CloudNine Technologies jobs
            [
                'company_index' => 4,
                'title' => 'DevOps Engineer',
                'description' => "Design and maintain cloud infrastructure for our enterprise clients. You'll work with cutting-edge tools and technologies.",
                'responsibilities' => "- Design cloud architecture\n- Implement CI/CD pipelines\n- Monitor and optimize infrastructure\n- Ensure security best practices",
                'location' => 'Gurgaon, India',
                'work_mode' => 'hybrid',
                'employment_type' => 'full-time',
                'experience_level' => 'senior',
                'salary_min' => 2200000,
                'salary_max' => 3500000,
                'required_skills' => json_encode(['Kubernetes', 'Docker', 'AWS', 'Terraform', 'CI/CD', 'Linux']),
                'benefits' => json_encode(['Certification Sponsorship', 'Stock Options', 'Remote Work']),
            ],
            [
                'company_index' => 4,
                'title' => 'Cloud Solutions Architect',
                'description' => "Design and implement cloud solutions for enterprise clients. You'll work directly with customers to understand their needs and architect solutions.",
                'responsibilities' => "- Design cloud architecture\n- Lead technical discussions with clients\n- Create technical documentation\n- Mentor engineering teams",
                'location' => 'Remote',
                'work_mode' => 'remote',
                'employment_type' => 'full-time',
                'experience_level' => 'lead',
                'salary_min' => 3500000,
                'salary_max' => 5500000,
                'required_skills' => json_encode(['AWS', 'Azure', 'Architecture', 'Solution Design', 'Leadership']),
                'benefits' => json_encode(['Travel Allowance', 'Stock Options', 'Conference Budget']),
            ],
            // EduLearn Academy jobs
            [
                'company_index' => 5,
                'title' => 'Junior Web Developer',
                'description' => "Perfect for fresh graduates! Join our team and learn from experienced developers while building real products.\n\nWhat you'll learn:\n- Modern web development practices\n- Agile methodologies\n- Code review processes\n- Testing best practices",
                'responsibilities' => "- Develop features under guidance\n- Write tests for your code\n- Participate in code reviews\n- Learn and grow continuously",
                'location' => 'Chennai, India',
                'work_mode' => 'hybrid',
                'employment_type' => 'full-time',
                'experience_level' => 'entry',
                'salary_min' => 400000,
                'salary_max' => 700000,
                'required_skills' => json_encode(['JavaScript', 'HTML', 'CSS', 'React', 'Git']),
                'benefits' => json_encode(['Free Courses', 'Mentorship', 'Flexible Hours']),
            ],
            [
                'company_index' => 5,
                'title' => 'Content Developer',
                'description' => "Create engaging educational content for our platform. You'll work with subject matter experts to develop courses that help students learn effectively.",
                'responsibilities' => "- Develop course content\n- Create assessments and quizzes\n- Work with video production team\n- Gather and incorporate feedback",
                'location' => 'Chennai, India',
                'work_mode' => 'remote',
                'employment_type' => 'part-time',
                'experience_level' => 'entry',
                'salary_min' => 300000,
                'salary_max' => 500000,
                'required_skills' => json_encode(['Content Writing', 'Research', 'Education', 'Communication']),
                'benefits' => json_encode(['Flexible Hours', 'Free Courses', 'Work From Home']),
            ],
        ];

        $createdJobs = [];
        
        foreach ($jobs as $job) {
            $company = $companies[$job['company_index']];
            unset($job['company_index']);
            
            $createdJob = Job::firstOrCreate(
                ['title' => $job['title'], 'company_id' => $company->id],
                array_merge($job, [
                    'company_id' => $company->id,
                    'slug' => Str::slug($job['title']) . '-' . Str::random(8),
                    'status' => 'published',
                    'published_at' => now()->subDays(rand(1, 30)),
                    'expires_at' => now()->addDays(rand(15, 60)),
                ])
            );
            
            $createdJobs[] = $createdJob;
        }

        return $createdJobs;
    }

    private function seedApplications(array $jobSeekers, array $jobs): void
    {
        $statuses = ['submitted', 'viewed', 'shortlisted', 'interview_scheduled', 'rejected'];
        
        foreach ($jobSeekers as $seeker) {
            // Each job seeker applies to 2-4 random jobs
            $numApplications = rand(2, 4);
            $appliedJobs = collect($jobs)->random($numApplications);
            
            foreach ($appliedJobs as $job) {
                $status = $statuses[array_rand($statuses)];
                $submittedAt = now()->subDays(rand(1, 20));
                
                Application::firstOrCreate(
                    ['user_id' => $seeker->id, 'job_id' => $job->id],
                    [
                        'user_id' => $seeker->id,
                        'job_id' => $job->id,
                        'application_number' => 'APP-' . strtoupper(Str::random(8)),
                        'status' => $status,
                        'cover_letter' => $this->generateSampleCoverLetter($seeker, $job),
                        'match_score' => rand(60, 95),
                        'submitted_at' => $submittedAt,
                        'viewed_at' => $status !== 'submitted' ? $submittedAt->addDays(rand(1, 3)) : null,
                    ]
                );
            }
        }
    }

    private function generateSampleCoverLetter($user, $job): string
    {
        // Reload user with profile relationship
        $user->load('profile');
        $profile = $user->profile;
        $skills = $profile?->skills ? implode(', ', array_slice($profile->skills, 0, 3)) : 'relevant skills';
        $companyName = $job->company?->name ?? 'your company';
        
        return "Dear Hiring Manager,

I am writing to express my strong interest in the {$job->title} position at {$companyName}. With my experience in {$skills}, I am confident that I would be a valuable addition to your team.

I am particularly drawn to this opportunity because of {$companyName}'s reputation in the industry and the exciting challenges this role presents. I believe my skills and experience align well with your requirements.

Throughout my career, I have developed strong problem-solving abilities and a commitment to delivering high-quality work. I am a quick learner who thrives in collaborative environments.

I would welcome the opportunity to discuss how my background can benefit {$companyName}. Thank you for considering my application.

Best regards,
{$user->name}";
    }
}
