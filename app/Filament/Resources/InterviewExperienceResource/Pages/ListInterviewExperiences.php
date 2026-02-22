<?php

declare(strict_types=1);

namespace App\Filament\Resources\InterviewExperienceResource\Pages;

use App\Filament\Resources\InterviewExperienceResource;
use Filament\Resources\Pages\ListRecords;

class ListInterviewExperiences extends ListRecords
{
    protected static string $resource = InterviewExperienceResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
