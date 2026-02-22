<?php

declare(strict_types=1);

namespace App\Filament\Resources\OfferLetterTemplateResource\Pages;

use App\Filament\Resources\OfferLetterTemplateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOfferLetterTemplate extends CreateRecord
{
    protected static string $resource = OfferLetterTemplateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        
        if (!$user->hasRole('super-admin')) {
            $data['company_id'] = $user->company_id;
            $data['type'] = 'custom';
        }
        
        return $data;
    }
}
