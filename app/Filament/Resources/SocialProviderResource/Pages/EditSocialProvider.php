<?php

declare(strict_types=1);

namespace App\Filament\Resources\SocialProviderResource\Pages;

use App\Filament\Resources\SocialProviderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSocialProvider extends EditRecord
{
    protected static string $resource = SocialProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Set default redirect URL if not provided
        if (empty($data['redirect_url'])) {
            $data['redirect_url'] = url('/auth/' . $data['slug'] . '/callback');
        }

        return $data;
    }
}
