<?php

namespace Database\Seeders;

use App\Models\Testimonial;
use Illuminate\Database\Seeder;

class TestimonialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $testimonials = [
            [
                'name' => 'Priya Sharma',
                'position' => 'Software Engineer',
                'company' => 'Tech Corp',
                'content' => 'StudAI Career completely transformed my job search. I found my dream job in just 3 weeks! The AI matching feature discovered opportunities I would have never found on my own.',
                'rating' => 5,
                'verified' => true,
                'is_active' => true,
                'display_order' => 1,
            ],
            [
                'name' => 'Rahul Patel',
                'position' => 'Product Manager',
                'company' => 'Startup Inc',
                'content' => 'The resume optimization and interview prep tools are absolute game-changers. My interview success rate went from 20% to 80% after using StudAI Career!',
                'rating' => 5,
                'verified' => true,
                'is_active' => true,
                'display_order' => 2,
            ],
            [
                'name' => 'Anjali Mehta',
                'position' => 'HR Manager',
                'company' => 'Global Solutions',
                'content' => 'As a recruiter, I can tell you that candidates using StudAI Career always stand out. Their resumes are perfectly optimized and they come incredibly well-prepared.',
                'rating' => 5,
                'verified' => true,
                'is_active' => true,
                'display_order' => 3,
            ],
            [
                'name' => 'Karthik Reddy',
                'position' => 'Data Scientist',
                'company' => 'Analytics Hub',
                'content' => 'The job matching algorithm is brilliant. It suggested roles I hadn\'t considered that turned out to be perfect fits for my skills and career goals.',
                'rating' => 5,
                'verified' => true,
                'is_active' => true,
                'display_order' => 4,
            ],
            [
                'name' => 'Sneha Desai',
                'position' => 'UX Designer',
                'company' => 'Design Studio',
                'content' => 'I was struggling with cover letters until I found StudAI Career. The AI-generated cover letters are personalized and effective. Got 3 offers in one month!',
                'rating' => 5,
                'verified' => true,
                'is_active' => true,
                'display_order' => 5,
            ],
            [
                'name' => 'Amit Kumar',
                'position' => 'DevOps Engineer',
                'company' => 'Cloud Systems',
                'content' => 'The interview preparation module helped me ace technical interviews. The practice questions were spot-on and the feedback was incredibly helpful.',
                'rating' => 5,
                'verified' => true,
                'is_active' => true,
                'display_order' => 6,
            ],
        ];

        foreach ($testimonials as $testimonial) {
            Testimonial::create($testimonial);
        }
    }
}
