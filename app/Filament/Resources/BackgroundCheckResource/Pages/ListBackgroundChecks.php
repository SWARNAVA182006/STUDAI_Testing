<?php

declare(strict_types=1);

namespace App\Filament\Resources\BackgroundCheckResource\Pages;

use App\Filament\Resources\BackgroundCheckResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBackgroundChecks extends ListRecords
{
    protected static string $resource = BackgroundCheckResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
