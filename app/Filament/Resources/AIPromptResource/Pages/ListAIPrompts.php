<?php

declare(strict_types=1);

namespace App\Filament\Resources\AIPromptResource\Pages;

use App\Filament\Resources\AIPromptResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAIPrompts extends ListRecords
{
    protected static string $resource = AIPromptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
