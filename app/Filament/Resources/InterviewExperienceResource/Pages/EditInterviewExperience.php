<?php

declare(strict_types=1);

namespace App\Filament\Resources\InterviewExperienceResource\Pages;

use App\Filament\Resources\InterviewExperienceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInterviewExperience extends EditRecord
{
    protected static string $resource = InterviewExperienceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
