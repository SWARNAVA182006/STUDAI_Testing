<?php

declare(strict_types=1);

namespace App\Filament\Resources\AIPromptResource\Pages;

use App\Filament\Resources\AIPromptResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAIPrompt extends CreateRecord
{
    protected static string $resource = AIPromptResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        // Set version to 1 if not provided
        if (empty($data['version'])) {
            $data['version'] = 1;
        }

        return $data;
    }
}
