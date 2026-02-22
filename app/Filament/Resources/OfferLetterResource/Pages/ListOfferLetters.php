<?php

declare(strict_types=1);

namespace App\Filament\Resources\OfferLetterResource\Pages;

use App\Filament\Resources\OfferLetterResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOfferLetters extends ListRecords
{
    protected static string $resource = OfferLetterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Add widgets here if needed
        ];
    }
}
