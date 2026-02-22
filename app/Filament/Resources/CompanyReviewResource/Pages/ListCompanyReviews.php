<?php

declare(strict_types=1);

namespace App\Filament\Resources\CompanyReviewResource\Pages;

use App\Filament\Resources\CompanyReviewResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCompanyReviews extends ListRecords
{
    protected static string $resource = CompanyReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('pending_queue')
                ->label('View Pending Queue')
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->url(fn () => static::getResource()::getUrl('index', ['tableFilters' => ['status' => ['value' => 'pending']]])),
        ];
    }
}
