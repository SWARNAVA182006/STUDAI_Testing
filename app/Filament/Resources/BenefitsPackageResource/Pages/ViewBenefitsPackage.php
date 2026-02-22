<?php

declare(strict_types=1);

namespace App\Filament\Resources\BenefitsPackageResource\Pages;

use App\Filament\Resources\BenefitsPackageResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewBenefitsPackage extends ViewRecord
{
    protected static string $resource = BenefitsPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
