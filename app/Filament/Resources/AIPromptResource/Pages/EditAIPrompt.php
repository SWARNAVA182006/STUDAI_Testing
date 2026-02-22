<?php

declare(strict_types=1);

namespace App\Filament\Resources\AIPromptResource\Pages;

use App\Filament\Resources\AIPromptResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAIPrompt extends EditRecord
{
    protected static string $resource = AIPromptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();

        return $data;
    }
}
