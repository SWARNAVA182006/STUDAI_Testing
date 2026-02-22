<?php

declare(strict_types=1);

namespace App\Filament\Resources\BackgroundCheckResource\Pages;

use App\Filament\Resources\BackgroundCheckResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateBackgroundCheck extends CreateRecord
{
    protected static string $resource = BackgroundCheckResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();
        
        $data['company_id'] = $user->company_id ?? $user->id;
        $data['initiator_id'] = $user->id;
        $data['status'] = 'pending';
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
