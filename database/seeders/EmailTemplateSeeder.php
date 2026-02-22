<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\EmailTemplate;
use App\Models\EmailTemplateCategory;
use App\Services\EmailTemplateService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EmailTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create categories
        $categories = [
            [
                'name' => 'Interview',
                'slug' => 'interview',
                'description' => 'Templates for scheduling and managing interviews',
                'icon' => '🎤',
                'sort_order' => 1,
            ],
            [
                'name' => 'Rejection',
                'slug' => 'rejection',
                'description' => 'Professional and kind rejection letters',
                'icon' => '💔',
                'sort_order' => 2,
            ],
            [
                'name' => 'Offer',
                'slug' => 'offer',
                'description' => 'Job offer and acceptance templates',
                'icon' => '🎉',
                'sort_order' => 3,
            ],
            [
                'name' => 'Follow-up',
                'slug' => 'follow-up',
                'description' => 'Application status and follow-up emails',
                'icon' => '📧',
                'sort_order' => 4,
            ],
            [
                'name' => 'Onboarding',
                'slug' => 'onboarding',
                'description' => 'Welcome and first-day information',
                'icon' => '🚀',
                'sort_order' => 5,
            ],
            [
                'name' => 'Reference',
                'slug' => 'reference',
                'description' => 'Reference request templates',
                'icon' => '📋',
                'sort_order' => 6,
            ],
        ];

        foreach ($categories as $categoryData) {
            EmailTemplateCategory::updateOrCreate(
                ['slug' => $categoryData['slug']],
                $categoryData
            );
        }

        // Get the service to access default templates
        $service = app(EmailTemplateService::class);
        $defaultTemplates = $service->getDefaultTemplates();

        // Create default templates
        foreach ($defaultTemplates as $categorySlug => $templates) {
            $category = EmailTemplateCategory::where('slug', $categorySlug)->first();

            if (!$category) {
                continue;
            }

            foreach ($templates as $templateData) {
                $slug = Str::slug($templateData['name']);

                // Extract variables from the template body
                preg_match_all('/\{\{(\w+)\}\}/', $templateData['body_html'], $matches);
                $variables = [];
                $availableVariables = $service->getAvailableVariables();

                foreach (array_unique($matches[1] ?? []) as $var) {
                    $variables[$var] = $availableVariables[$var] ?? 'Custom variable';
                }

                EmailTemplate::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'category_id' => $category->id,
                        'user_id' => null,
                        'company_id' => null,
                        'name' => $templateData['name'],
                        'subject' => $templateData['subject'],
                        'body_html' => $templateData['body_html'],
                        'body_text' => strip_tags($templateData['body_html']),
                        'variables' => $variables,
                        'default_values' => [],
                        'type' => 'system',
                        'tone' => $templateData['tone'] ?? 'professional',
                        'is_default' => true,
                        'is_public' => true,
                        'is_active' => true,
                    ]
                );
            }
        }

        $this->command->info('Email template categories and default templates seeded successfully!');
    }
}
