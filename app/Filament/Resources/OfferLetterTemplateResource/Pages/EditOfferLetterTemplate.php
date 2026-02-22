<?php

declare(strict_types=1);

namespace App\Filament\Resources\OfferLetterTemplateResource\Pages;

use App\Filament\Resources\OfferLetterTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOfferLetterTemplate extends EditRecord
{
    protected static string $resource = OfferLetterTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->visible(fn () => $this->record->type !== 'system'),
        ];
    }
}
