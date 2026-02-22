<?php

declare(strict_types=1);

namespace App\Filament\Resources\SalaryReportResource\Pages;

use App\Filament\Resources\SalaryReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSalaryReports extends ListRecords
{
    protected static string $resource = SalaryReportResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
