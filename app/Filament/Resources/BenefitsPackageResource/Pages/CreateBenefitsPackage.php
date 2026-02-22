<?php

declare(strict_types=1);

namespace App\Filament\Resources\BenefitsPackageResource\Pages;

use App\Filament\Resources\BenefitsPackageResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBenefitsPackage extends CreateRecord
{
    protected static string $resource = BenefitsPackageResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = auth()->user()->company_id;
        
        return $data;
    }
}
