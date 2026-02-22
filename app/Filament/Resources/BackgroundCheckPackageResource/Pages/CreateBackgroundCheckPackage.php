<?php

declare(strict_types=1);

namespace App\Filament\Resources\BackgroundCheckPackageResource\Pages;

use App\Filament\Resources\BackgroundCheckPackageResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateBackgroundCheckPackage extends CreateRecord
{
    protected static string $resource = BackgroundCheckPackageResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
