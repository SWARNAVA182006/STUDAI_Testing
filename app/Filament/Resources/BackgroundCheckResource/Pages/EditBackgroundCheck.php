<?php

declare(strict_types=1);

namespace App\Filament\Resources\BackgroundCheckResource\Pages;

use App\Filament\Resources\BackgroundCheckResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBackgroundCheck extends EditRecord
{
    protected static string $resource = BackgroundCheckResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->visible(fn () => !in_array($this->record->status, ['completed', 'in_progress'])),
            Actions\Action::make('cancel')
                ->label('Cancel Check')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => !in_array($this->record->status, ['completed', 'cancelled', 'failed']))
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update([
                        'status' => 'cancelled',
                        'cancelled_at' => now(),
                    ]);
                    
                    $this->redirect($this->getResource()::getUrl('index'));
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
