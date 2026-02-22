<?php

namespace Database\Seeders;

use App\Models\CompanyInterviewData;
use Illuminate\Database\Seeder;

class CompanyInterviewDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Google - Software Engineer
        CompanyInterviewData::create([
            'company_name' => 'Google',
            'role_title' => 'Software Engineer',
            'interview_type' => 'technical',
            'data_points_count' => 250,
            'common_questions' => [
                'behavioral' => [
                    'Tell me about a time when you had to optimize a slow system',
                    'Describe a situation where you disagreed with a technical decision',
                    'How do you handle code reviews that challenge your approach?',
                ],
                'technical' => [
                    'Design a distributed cache system',
                    'Implement an LRU cache',
                    'How would you design YouTube?',
                    'Reverse a linked list',
                ],
                'situational' => [
                    'How would you handle a production incident affecting millions of users?',
                    'What would you do if you discovered a security vulnerability?',
                ],
            ],
            'interviewer_profiles' => [
                ['title' => 'Senior Software Engineer', 'style' => 'collaborative', 'focus' => 'system design'],
                ['title' => 'Engineering Manager', 'style' => 'detail-oriented', 'focus' => 'problem-solving'],
                ['title' => 'Tech Lead', 'style' => 'conversational', 'focus' => 'coding proficiency'],
            ],
            'interview_structure' => [
                'rounds' => 4,
                'duration_minutes' => 45,
                'types' => ['Coding', 'System Design', 'Behavioral', 'Googleyness & Leadership'],
            ],
            'difficulty_ratings' => [
                'coding' => 'hard',
                'system_design' => 'very_hard',
                'behavioral' => 'medium',
            ],
            'success_patterns' => [
                'Demonstrate scalability thinking',
                'Use concrete metrics and data',
                'Show collaborative problem-solving',
                'Discuss trade-offs explicitly',
            ],
            'cultural_values' => ['Innovation', 'Data-driven decisions', 'User focus', 'Collaboration', 'Technical excellence'],
            'technical_focus_areas' => ['Algorithms', 'Data structures', 'System design', 'Scalability', 'Distributed systems'],
        ]);

        // Microsoft - Senior Software Engineer
        CompanyInterviewData::create([
            'company_name' => 'Microsoft',
            'role_title' => 'Senior Software Engineer',
            'interview_type' => 'mixed',
            'data_points_count' => 180,
            'common_questions' => [
                'behavioral' => [
                    'Tell me about a time you led a technical initiative',
                    'Describe how you mentored junior developers',
                    'How do you handle conflicting priorities?',
                ],
                'technical' => [
                    'Design Azure blob storage',
                    'Implement a B-tree',
                    'Optimize database query performance',
                ],
                'situational' => [
                    'How would you migrate a legacy system to the cloud?',
                    'What if your team disagrees on technology choices?',
                ],
            ],
            'interviewer_profiles' => [
                ['title' => 'Principal Engineer', 'style' => 'strategic', 'focus' => 'architecture'],
                ['title' => 'Engineering Lead', 'style' => 'practical', 'focus' => 'execution'],
            ],
            'interview_structure' => [
                'rounds' => 5,
                'duration_minutes' => 60,
                'types' => ['Coding', 'Design', 'Behavioral', 'Technical Deep Dive', 'As Appropriate'],
            ],
            'difficulty_ratings' => [
                'coding' => 'medium',
                'system_design' => 'hard',
                'behavioral' => 'medium',
            ],
            'success_patterns' => [
                'Show ownership and accountability',
                'Demonstrate customer obsession',
                'Discuss impact and outcomes',
                'Exhibit growth mindset',
            ],
            'cultural_values' => ['Respect', 'Integrity', 'Accountability', 'Growth mindset', 'Customer focus'],
            'technical_focus_areas' => ['Cloud architecture', 'Security', 'Performance optimization', 'DevOps', 'API design'],
        ]);

        // Amazon - Software Development Engineer
        CompanyInterviewData::create([
            'company_name' => 'Amazon',
            'role_title' => 'Software Development Engineer',
            'interview_type' => 'behavioral',
            'data_points_count' => 320,
            'common_questions' => [
                'behavioral' => [
                    'Tell me about a time you took a calculated risk',
                    'Describe a situation where you delivered results under pressure',
                    'Give me an example of when you challenged the status quo',
                    'Tell me about a time you failed',
                ],
                'technical' => [
                    'Design Amazon S3',
                    'Implement an order processing system',
                    'Optimize warehouse routing',
                ],
                'situational' => [
                    'How would you reduce latency for a global service?',
                    'What if a customer complaint reveals a systemic issue?',
                ],
            ],
            'interviewer_profiles' => [
                ['title' => 'SDE II', 'style' => 'direct', 'focus' => 'leadership principles'],
                ['title' => 'Senior SDE', 'style' => 'challenging', 'focus' => 'customer obsession'],
                ['title' => 'Principal Engineer', 'style' => 'deep-dive', 'focus' => 'technical depth'],
            ],
            'interview_structure' => [
                'rounds' => 5,
                'duration_minutes' => 60,
                'types' => ['Coding', 'System Design', 'Behavioral (2 rounds)', 'Bar Raiser'],
            ],
            'difficulty_ratings' => [
                'coding' => 'medium',
                'system_design' => 'hard',
                'behavioral' => 'hard',
            ],
            'success_patterns' => [
                'Use STAR format religiously',
                'Quantify everything',
                'Show ownership and bias for action',
                'Reference Leadership Principles',
            ],
            'cultural_values' => ['Customer obsession', 'Ownership', 'Invent and simplify', 'Bias for action', 'Frugality', 'Dive deep'],
            'technical_focus_areas' => ['Distributed systems', 'High availability', 'Cost optimization', 'Data processing', 'Microservices'],
        ]);

        // Meta - Software Engineer
        CompanyInterviewData::create([
            'company_name' => 'Meta',
            'role_title' => 'Software Engineer',
            'interview_type' => 'technical',
            'data_points_count' => 200,
            'common_questions' => [
                'behavioral' => [
                    'Tell me about a time you shipped a high-impact feature',
                    'Describe how you handle ambiguous requirements',
                    'How do you balance move fast vs. technical excellence?',
                ],
                'technical' => [
                    'Design Facebook News Feed',
                    'Implement a React component library',
                    'Optimize image loading for mobile',
                    'Graph traversal algorithms',
                ],
                'situational' => [
                    'How would you scale a feature to 1 billion users?',
                    'What if user engagement drops after your feature launch?',
                ],
            ],
            'interviewer_profiles' => [
                ['title' => 'E5 Software Engineer', 'style' => 'fast-paced', 'focus' => 'execution speed'],
                ['title' => 'E6 Staff Engineer', 'style' => 'visionary', 'focus' => 'impact'],
            ],
            'interview_structure' => [
                'rounds' => 4,
                'duration_minutes' => 45,
                'types' => ['Coding (2 rounds)', 'System Design', 'Behavioral'],
            ],
            'difficulty_ratings' => [
                'coding' => 'hard',
                'system_design' => 'very_hard',
                'behavioral' => 'medium',
            ],
            'success_patterns' => [
                'Move fast and iterate',
                'Show impact at scale',
                'Demonstrate technical depth',
                'Focus on user experience',
            ],
            'cultural_values' => ['Move fast', 'Be bold', 'Focus on impact', 'Be open', 'Build social value'],
            'technical_focus_areas' => ['Frontend frameworks', 'Backend scalability', 'Mobile optimization', 'Machine learning', 'Real-time systems'],
        ]);

        // Startup - Full Stack Developer
        CompanyInterviewData::create([
            'company_name' => 'Tech Startup',
            'role_title' => 'Full Stack Developer',
            'interview_type' => 'mixed',
            'data_points_count' => 50,
            'common_questions' => [
                'behavioral' => [
                    'Why do you want to work at a startup?',
                    'Tell me about a time you wore multiple hats',
                    'How do you handle fast-changing requirements?',
                ],
                'technical' => [
                    'Build a REST API with authentication',
                    'Design a simple e-commerce platform',
                    'Implement real-time notifications',
                ],
                'situational' => [
                    'How would you prioritize features with limited resources?',
                    'What if you disagree with the founder\'s technical direction?',
                ],
            ],
            'interviewer_profiles' => [
                ['title' => 'CTO', 'style' => 'pragmatic', 'focus' => 'versatility'],
                ['title' => 'Senior Developer', 'style' => 'collaborative', 'focus' => 'full-stack skills'],
            ],
            'interview_structure' => [
                'rounds' => 3,
                'duration_minutes' => 60,
                'types' => ['Technical Screen', 'Coding + System Design', 'Culture Fit'],
            ],
            'difficulty_ratings' => [
                'coding' => 'medium',
                'system_design' => 'medium',
                'behavioral' => 'easy',
            ],
            'success_patterns' => [
                'Show adaptability',
                'Demonstrate ownership',
                'Discuss trade-offs pragmatically',
                'Show passion for the mission',
            ],
            'cultural_values' => ['Ownership', 'Adaptability', 'Speed', 'Learning mindset', 'Customer focus'],
            'technical_focus_areas' => ['Full-stack development', 'MVP building', 'Cloud services', 'Modern frameworks', 'DevOps basics'],
        ]);

        $this->command->info('✓ Seeded company interview data for 5 companies');
    }
}
