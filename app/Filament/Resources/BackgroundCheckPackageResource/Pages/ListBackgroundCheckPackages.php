<?php

declare(strict_types=1);

namespace App\Filament\Resources\BackgroundCheckPackageResource\Pages;

use App\Filament\Resources\BackgroundCheckPackageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBackgroundCheckPackages extends ListRecords
{
    protected static string $resource = BackgroundCheckPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
