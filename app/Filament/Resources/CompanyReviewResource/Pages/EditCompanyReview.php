<?php

declare(strict_types=1);

namespace App\Filament\Resources\CompanyReviewResource\Pages;

use App\Filament\Resources\CompanyReviewResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCompanyReview extends EditRecord
{
    protected static string $resource = CompanyReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $this->record->company->recalculateAllRatings();
    }
}
