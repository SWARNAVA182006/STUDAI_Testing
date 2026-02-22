<?php

declare(strict_types=1);

namespace App\Filament\Resources\SocialProviderResource\Pages;

use App\Filament\Resources\SocialProviderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSocialProviders extends ListRecords
{
    protected static string $resource = SocialProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
