<?php

declare(strict_types=1);

namespace App\Filament\Resources\OfferLetterTemplateResource\Pages;

use App\Filament\Resources\OfferLetterTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOfferLetterTemplates extends ListRecords
{
    protected static string $resource = OfferLetterTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
