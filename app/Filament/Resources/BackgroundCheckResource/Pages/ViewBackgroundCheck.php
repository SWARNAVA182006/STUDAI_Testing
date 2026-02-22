<?php

declare(strict_types=1);

namespace App\Filament\Resources\BackgroundCheckResource\Pages;

use App\Filament\Resources\BackgroundCheckResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewBackgroundCheck extends ViewRecord
{
    protected static string $resource = BackgroundCheckResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('sendConsent')
                ->label('Send Consent Request')
                ->icon('heroicon-o-envelope')
                ->color('warning')
                ->visible(fn () => $this->record->status === 'pending')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update([
                        'status' => 'awaiting_consent',
                        'consent_sent_at' => now(),
                    ]);
                    
                    // TODO: Dispatch job to send consent email
                    
                    $this->refreshFormData(['status', 'consent_sent_at']);
                }),
            Actions\Action::make('downloadReport')
                ->label('Download Report')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->visible(fn () => $this->record->status === 'completed' && $this->record->report_pdf_path)
                ->url(fn () => route('background-checks.download', $this->record))
                ->openUrlInNewTab(),
        ];
    }
}
