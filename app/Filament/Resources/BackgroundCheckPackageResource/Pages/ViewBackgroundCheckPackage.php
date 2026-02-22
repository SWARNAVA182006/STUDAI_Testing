<?php

declare(strict_types=1);

namespace App\Filament\Resources\BackgroundCheckPackageResource\Pages;

use App\Filament\Resources\BackgroundCheckPackageResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewBackgroundCheckPackage extends ViewRecord
{
    protected static string $resource = BackgroundCheckPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
