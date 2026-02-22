<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\BackgroundCheckResource\Pages;
use App\Models\BackgroundCheck;
use App\Models\BackgroundCheckPackage;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Actions;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BackgroundCheckResource extends Resource
{
    protected static ?string $model = BackgroundCheck::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-shield-check';

    protected static \UnitEnum|string|null $navigationGroup = 'Hiring';

    protected static ?string $navigationLabel = 'Background Checks';

    protected static ?int $navigationSort = 4;

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::whereIn('status', ['pending', 'awaiting_consent', 'in_progress'])->count();
    }

    public static function getNavigationBadgeColor(): string
    {
        $pending = static::getModel()::whereIn('status', ['pending', 'awaiting_consent', 'in_progress'])->count();
        return $pending > 0 ? 'warning' : 'success';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Candidate Information')
                    ->columns(2)
                    ->schema([
                        Select::make('candidate_id')
                            ->label('Candidate')
                            ->options(fn () => User::whereHas('roles', fn($q) => $q->where('name', 'user'))
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('application_id')
                            ->label('Job Application')
                            ->relationship('application', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->job?->title . ' - Applied: ' . $record->created_at->format('M d, Y'))
                            ->searchable()
                            ->preload(),
                    ]),

                Section::make('Check Configuration')
                    ->columns(2)
                    ->schema([
                        Select::make('provider')
                            ->options([
                                'checkr' => 'Checkr',
                                'sterling' => 'Sterling',
                                'goodhire' => 'GoodHire',
                            ])
                            ->required()
                            ->default('checkr'),

                        Select::make('package_id')
                            ->label('Check Package')
                            ->options(fn () => BackgroundCheckPackage::where('is_active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->preload(),

                        Select::make('check_types')
                            ->multiple()
                            ->options([
                                'criminal' => 'Criminal Background',
                                'employment' => 'Employment Verification',
                                'education' => 'Education Verification',
                                'mvr' => 'Motor Vehicle Report',
                                'credit' => 'Credit Check',
                                'drug_test' => 'Drug Test',
                                'professional_license' => 'Professional License Verification',
                                'reference' => 'Reference Check',
                                'identity' => 'Identity Verification',
                            ])
                            ->columnSpan(2),

                        Select::make('priority')
                            ->options([
                                'standard' => 'Standard',
                                'expedited' => 'Expedited',
                                'urgent' => 'Urgent',
                            ])
                            ->default('standard')
                            ->required(),
                    ]),

                Section::make('Status & Results')
                    ->columns(3)
                    ->visible(fn ($record) => $record && $record->exists)
                    ->schema([
                        Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'awaiting_consent' => 'Awaiting Consent',
                                'consent_received' => 'Consent Received',
                                'in_progress' => 'In Progress',
                                'completed' => 'Completed',
                                'failed' => 'Failed',
                                'cancelled' => 'Cancelled',
                                'expired' => 'Expired',
                            ])
                            ->disabled(),

                        Select::make('result')
                            ->options([
                                'clear' => 'Clear',
                                'consider' => 'Consider',
                                'review' => 'Review Required',
                                'failed' => 'Failed',
                            ])
                            ->disabled(),

                        TextInput::make('provider_check_id')
                            ->label('Provider Reference ID')
                            ->disabled(),
                    ]),

                Section::make('Timeline')
                    ->columns(4)
                    ->visible(fn ($record) => $record && $record->exists)
                    ->schema([
                        DatePicker::make('consent_sent_at')
                            ->label('Consent Sent')
                            ->disabled(),

                        DatePicker::make('consent_received_at')
                            ->label('Consent Received')
                            ->disabled(),

                        DatePicker::make('started_at')
                            ->label('Started')
                            ->disabled(),

                        DatePicker::make('completed_at')
                            ->label('Completed')
                            ->disabled(),
                    ]),

                Section::make('Internal Notes')
                    ->schema([
                        Textarea::make('internal_notes')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('candidate.name')
                    ->label('Candidate')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('application.job.title')
                    ->label('Position')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('provider')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'checkr' => 'Checkr',
                        'sterling' => 'Sterling',
                        'goodhire' => 'GoodHire',
                        default => ucfirst($state),
                    })
                    ->color('info'),

                TextColumn::make('package.name')
                    ->label('Package')
                    ->toggleable(),

                BadgeColumn::make('status')
                    ->colors([
                        'gray' => 'pending',
                        'warning' => fn ($state) => in_array($state, ['awaiting_consent', 'in_progress']),
                        'success' => 'completed',
                        'danger' => fn ($state) => in_array($state, ['failed', 'cancelled', 'expired']),
                        'info' => 'consent_received',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state))),

                BadgeColumn::make('result')
                    ->colors([
                        'success' => 'clear',
                        'warning' => 'consider',
                        'danger' => fn ($state) => in_array($state, ['review', 'failed']),
                    ])
                    ->formatStateUsing(fn (?string $state): string => $state ? ucfirst($state) : '-')
                    ->toggleable(),

                TextColumn::make('cost')
                    ->money('USD')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Requested')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('completed_at')
                    ->label('Completed')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'awaiting_consent' => 'Awaiting Consent',
                        'consent_received' => 'Consent Received',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled',
                    ]),
                SelectFilter::make('result')
                    ->options([
                        'clear' => 'Clear',
                        'consider' => 'Consider',
                        'review' => 'Review Required',
                        'failed' => 'Failed',
                    ]),
                SelectFilter::make('provider')
                    ->options([
                        'checkr' => 'Checkr',
                        'sterling' => 'Sterling',
                        'goodhire' => 'GoodHire',
                    ]),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
                Actions\Action::make('sendConsent')
                    ->label('Send Consent')
                    ->icon('heroicon-o-envelope')
                    ->color('warning')
                    ->visible(fn (BackgroundCheck $record) => $record->status === 'pending')
                    ->action(function (BackgroundCheck $record) {
                        // Update status to awaiting consent
                        $record->update([
                            'status' => 'awaiting_consent',
                            'consent_sent_at' => now(),
                        ]);
                        
                        Notification::make()
                            ->title('Consent request sent')
                            ->body('The candidate has been notified to provide consent.')
                            ->success()
                            ->send();
                    }),
                Actions\Action::make('downloadReport')
                    ->label('Download Report')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->visible(fn (BackgroundCheck $record) => $record->status === 'completed' && $record->report_pdf_path)
                    ->url(fn (BackgroundCheck $record) => route('background-checks.download', $record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                    Actions\BulkAction::make('bulkSendConsent')
                        ->label('Send Consent Requests')
                        ->icon('heroicon-o-envelope')
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->status === 'pending') {
                                    $record->update([
                                        'status' => 'awaiting_consent',
                                        'consent_sent_at' => now(),
                                    ]);
                                    $count++;
                                }
                            }
                            
                            Notification::make()
                                ->title("Sent {$count} consent requests")
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBackgroundChecks::route('/'),
            'create' => Pages\CreateBackgroundCheck::route('/create'),
            'view' => Pages\ViewBackgroundCheck::route('/{record}'),
            'edit' => Pages\EditBackgroundCheck::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['candidate', 'application.job', 'package', 'company']);
    }
}
