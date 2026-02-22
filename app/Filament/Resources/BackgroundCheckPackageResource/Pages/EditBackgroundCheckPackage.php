<?php

declare(strict_types=1);

namespace App\Filament\Resources\BackgroundCheckPackageResource\Pages;

use App\Filament\Resources\BackgroundCheckPackageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBackgroundCheckPackage extends EditRecord
{
    protected static string $resource = BackgroundCheckPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->visible(fn () => $this->record->background_checks_count === 0),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
