<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Services\OfferLetterService;
use Illuminate\Database\Seeder;

class OfferLetterTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $service = app(OfferLetterService::class);
        $service->createDefaultTemplates();
        
        $this->command->info('Default offer letter templates created successfully.');
    }
}
