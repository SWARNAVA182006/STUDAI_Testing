<?php

declare(strict_types=1);

namespace App\Filament\Resources\OfferLetterResource\Pages;

use App\Filament\Resources\OfferLetterResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOfferLetter extends EditRecord
{
    protected static string $resource = OfferLetterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->visible(fn () => $this->record->isDraft()),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
