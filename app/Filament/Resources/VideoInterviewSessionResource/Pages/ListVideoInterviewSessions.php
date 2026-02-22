<?php

declare(strict_types=1);

namespace App\Filament\Resources\VideoInterviewSessionResource\Pages;

use App\Filament\Resources\VideoInterviewSessionResource;
use Filament\Resources\Pages\ListRecords;

class ListVideoInterviewSessions extends ListRecords
{
    protected static string $resource = VideoInterviewSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
