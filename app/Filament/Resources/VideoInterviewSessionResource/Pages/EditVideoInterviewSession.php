<?php

declare(strict_types=1);

namespace App\Filament\Resources\VideoInterviewSessionResource\Pages;

use App\Filament\Resources\VideoInterviewSessionResource;
use Filament\Resources\Pages\EditRecord;

class EditVideoInterviewSession extends EditRecord
{
    protected static string $resource = VideoInterviewSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}
