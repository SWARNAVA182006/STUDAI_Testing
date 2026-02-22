<?php

declare(strict_types=1);

namespace App\Filament\Resources\OfferLetterResource\Pages;

use App\Filament\Resources\OfferLetterResource;
use App\Services\OfferLetterService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewOfferLetter extends ViewRecord
{
    protected static string $resource = OfferLetterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn () => $this->record->isDraft()),
            
            Actions\Action::make('send')
                ->label('Send Offer')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->visible(fn () => $this->record->isDraft())
                ->requiresConfirmation()
                ->modalHeading('Send Offer Letter')
                ->modalDescription('Are you sure you want to send this offer letter to the candidate?')
                ->action(function () {
                    $service = app(OfferLetterService::class);
                    $success = $service->sendOffer($this->record);
                    
                    if ($success) {
                        Notification::make()
                            ->success()
                            ->title('Offer Sent')
                            ->body('The offer letter has been sent to ' . $this->record->candidate->name)
                            ->send();
                    } else {
                        Notification::make()
                            ->danger()
                            ->title('Failed to Send')
                            ->body('There was an error sending the offer letter.')
                            ->send();
                    }
                }),

            Actions\Action::make('download')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(fn () => route('offer-letters.download', $this->record))
                ->openUrlInNewTab(),

            Actions\Action::make('withdraw')
                ->label('Withdraw Offer')
                ->icon('heroicon-o-archive-box-x-mark')
                ->color('danger')
                ->visible(fn () => $this->record->isSent() && !$this->record->isWithdrawn())
                ->requiresConfirmation()
                ->modalHeading('Withdraw Offer')
                ->modalDescription('Are you sure you want to withdraw this offer? This action cannot be undone.')
                ->action(function () {
                    $this->record->withdraw();
                    
                    Notification::make()
                        ->success()
                        ->title('Offer Withdrawn')
                        ->body('The offer has been withdrawn.')
                        ->send();
                }),

            Actions\Action::make('request_signature')
                ->label('Request Signature')
                ->icon('heroicon-o-pencil-square')
                ->color('info')
                ->visible(fn () => $this->record->isAccepted() && !$this->record->signature_document_id)
                ->form([
                    \Filament\Forms\Components\Select::make('provider')
                        ->label('Signature Provider')
                        ->options([
                            'docusign' => 'DocuSign',
                            'hellosign' => 'HelloSign',
                        ])
                        ->required(),
                ])
                ->action(function (array $data) {
                    $service = app(OfferLetterService::class);
                    
                    $documentId = match ($data['provider']) {
                        'docusign' => $service->requestDocuSignSignature($this->record),
                        'hellosign' => $service->requestHelloSignSignature($this->record),
                    };
                    
                    if ($documentId) {
                        Notification::make()
                            ->success()
                            ->title('Signature Requested')
                            ->body('Digital signature request has been sent.')
                            ->send();
                    } else {
                        Notification::make()
                            ->danger()
                            ->title('Failed')
                            ->body('Could not request digital signature. Please check your configuration.')
                            ->send();
                    }
                }),
        ];
    }
}
