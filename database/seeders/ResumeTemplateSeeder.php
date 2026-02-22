<?php

namespace Database\Seeders;

use App\Models\ResumeTemplate;
use Illuminate\Database\Seeder;

class ResumeTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Professional Classic',
                'slug' => 'professional-classic',
                'description' => 'Clean and professional design perfect for corporate roles',
                'category' => 'professional',
                'industry' => 'general',
                'color_scheme' => [
                    'primary' => '#1a202c',
                    'secondary' => '#2d3748',
                    'accent' => '#4a5568',
                ],
                'layout_config' => [
                    'columns' => 1,
                    'sections' => ['summary', 'experience', 'education', 'skills'],
                    'spacing' => 'comfortable',
                ],
                'is_ats_friendly' => true,
                'is_premium' => false,
                'popularity_score' => 100,
            ],
            [
                'name' => 'Modern Tech',
                'slug' => 'modern-tech',
                'description' => 'Contemporary design tailored for technology professionals',
                'category' => 'modern',
                'industry' => 'technology',
                'color_scheme' => [
                    'primary' => '#3b82f6',
                    'secondary' => '#1e40af',
                    'accent' => '#60a5fa',
                ],
                'layout_config' => [
                    'columns' => 2,
                    'sections' => ['summary', 'skills', 'experience', 'projects', 'education'],
                    'spacing' => 'compact',
                ],
                'is_ats_friendly' => true,
                'is_premium' => false,
                'popularity_score' => 95,
            ],
            [
                'name' => 'Executive',
                'slug' => 'executive',
                'description' => 'Sophisticated layout for senior leadership positions',
                'category' => 'executive',
                'industry' => 'general',
                'color_scheme' => [
                    'primary' => '#1f2937',
                    'secondary' => '#374151',
                    'accent' => '#6b7280',
                ],
                'layout_config' => [
                    'columns' => 1,
                    'sections' => ['summary', 'experience', 'achievements', 'education'],
                    'spacing' => 'spacious',
                ],
                'is_ats_friendly' => true,
                'is_premium' => true,
                'popularity_score' => 85,
            ],
            [
                'name' => 'Creative Portfolio',
                'slug' => 'creative-portfolio',
                'description' => 'Eye-catching design for creative professionals',
                'category' => 'creative',
                'industry' => 'creative',
                'color_scheme' => [
                    'primary' => '#ec4899',
                    'secondary' => '#be185d',
                    'accent' => '#f472b6',
                ],
                'layout_config' => [
                    'columns' => 2,
                    'sections' => ['summary', 'projects', 'skills', 'experience', 'education'],
                    'spacing' => 'creative',
                ],
                'is_ats_friendly' => false,
                'is_premium' => true,
                'popularity_score' => 80,
            ],
            [
                'name' => 'Minimalist',
                'slug' => 'minimalist',
                'description' => 'Clean, simple design with maximum readability',
                'category' => 'minimalist',
                'industry' => 'general',
                'color_scheme' => [
                    'primary' => '#000000',
                    'secondary' => '#333333',
                    'accent' => '#666666',
                ],
                'layout_config' => [
                    'columns' => 1,
                    'sections' => ['summary', 'experience', 'education', 'skills'],
                    'spacing' => 'minimal',
                ],
                'is_ats_friendly' => true,
                'is_premium' => false,
                'popularity_score' => 90,
            ],
            [
                'name' => 'Academic Scholar',
                'slug' => 'academic-scholar',
                'description' => 'Traditional format for academic and research positions',
                'category' => 'academic',
                'industry' => 'education',
                'color_scheme' => [
                    'primary' => '#1e3a8a',
                    'secondary' => '#1e40af',
                    'accent' => '#3b82f6',
                ],
                'layout_config' => [
                    'columns' => 1,
                    'sections' => ['summary', 'education', 'publications', 'experience', 'achievements'],
                    'spacing' => 'academic',
                ],
                'is_ats_friendly' => true,
                'is_premium' => false,
                'popularity_score' => 70,
            ],
            [
                'name' => 'Healthcare Professional',
                'slug' => 'healthcare-professional',
                'description' => 'Structured layout for healthcare and medical roles',
                'category' => 'professional',
                'industry' => 'healthcare',
                'color_scheme' => [
                    'primary' => '#059669',
                    'secondary' => '#047857',
                    'accent' => '#10b981',
                ],
                'layout_config' => [
                    'columns' => 1,
                    'sections' => ['summary', 'certifications', 'experience', 'education', 'skills'],
                    'spacing' => 'professional',
                ],
                'is_ats_friendly' => true,
                'is_premium' => true,
                'popularity_score' => 75,
            ],
            [
                'name' => 'Finance & Banking',
                'slug' => 'finance-banking',
                'description' => 'Conservative design for finance sector professionals',
                'category' => 'professional',
                'industry' => 'finance',
                'color_scheme' => [
                    'primary' => '#0f172a',
                    'secondary' => '#1e293b',
                    'accent' => '#334155',
                ],
                'layout_config' => [
                    'columns' => 1,
                    'sections' => ['summary', 'experience', 'certifications', 'education', 'skills'],
                    'spacing' => 'conservative',
                ],
                'is_ats_friendly' => true,
                'is_premium' => true,
                'popularity_score' => 78,
            ],
        ];

        foreach ($templates as $template) {
            ResumeTemplate::create($template);
        }

        $this->command->info('Resume templates seeded successfully!');
    }
}
