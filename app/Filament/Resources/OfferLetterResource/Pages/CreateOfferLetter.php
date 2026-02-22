<?php

declare(strict_types=1);

namespace App\Filament\Resources\OfferLetterResource\Pages;

use App\Filament\Resources\OfferLetterResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;

class CreateOfferLetter extends CreateRecord
{
    protected static string $resource = OfferLetterResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        
        $data['company_id'] = $user->company_id;
        $data['created_by'] = $user->id;
        $data['status'] = 'draft';
        
        // Get candidate name for any template rendering
        if (!empty($data['candidate_id'])) {
            $candidate = User::find($data['candidate_id']);
            $data['candidate_name'] = $candidate?->name ?? '';
        }
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
