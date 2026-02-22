<?php

declare(strict_types=1);

namespace App\Filament\Resources\VideoInterviewSessionResource\Pages;

use App\Filament\Resources\VideoInterviewSessionResource;
use Filament\Resources\Pages\ViewRecord;

class ViewVideoInterviewSession extends ViewRecord
{
    protected static string $resource = VideoInterviewSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\EditAction::make(),
        ];
    }
}
