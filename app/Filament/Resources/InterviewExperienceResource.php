<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\InterviewExperienceResource\Pages;
use App\Models\InterviewExperience;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Actions;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InterviewExperienceResource extends Resource
{
    protected static ?string $model = InterviewExperience::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static \UnitEnum|string|null $navigationGroup = 'Reviews & Ratings';

    protected static ?string $navigationLabel = 'Interview Experiences';

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('status', 'pending')->count();
    }

    public static function getNavigationBadgeColor(): string
    {
        return static::getModel()::where('status', 'pending')->count() > 0 ? 'warning' : 'success';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Interview Information')
                    ->columns(2)
                    ->schema([
                        Select::make('company_id')
                            ->relationship('company', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(),

                        Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->disabled(),

                        TextInput::make('job_title')
                            ->disabled(),

                        TextInput::make('department')
                            ->disabled(),

                        TextInput::make('location')
                            ->disabled(),

                        DatePicker::make('interview_date')
                            ->disabled(),
                    ]),

                Section::make('Experience Details')
                    ->columns(3)
                    ->schema([
                        Select::make('overall_experience')
                            ->options([
                                'positive' => 'Positive',
                                'neutral' => 'Neutral',
                                'negative' => 'Negative',
                            ])
                            ->disabled(),

                        TextInput::make('difficulty_rating')
                            ->numeric()
                            ->disabled(),

                        Select::make('outcome')
                            ->options([
                                'accepted' => 'Accepted Offer',
                                'declined' => 'Declined Offer',
                                'rejected' => 'No Offer',
                                'no_response' => 'No Response',
                                'pending' => 'Pending',
                            ])
                            ->disabled(),

                        TextInput::make('application_source')
                            ->disabled(),

                        TextInput::make('interview_type')
                            ->disabled(),

                        TextInput::make('interview_duration')
                            ->disabled(),
                    ]),

                Section::make('Interview Content')
                    ->schema([
                        Textarea::make('interview_process')
                            ->rows(4)
                            ->disabled(),

                        Textarea::make('tips_for_interview')
                            ->rows(3)
                            ->disabled(),

                        TagsInput::make('interview_questions')
                            ->disabled(),
                    ]),

                Section::make('Moderation')
                    ->schema([
                        Select::make('status')
                            ->options([
                                'pending' => 'Pending Review',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                            ])
                            ->required(),

                        Textarea::make('moderation_notes')
                            ->label('Moderation Notes')
                            ->placeholder('Add notes about moderation decision...')
                            ->rows(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Company')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('job_title')
                    ->label('Position')
                    ->searchable()
                    ->limit(25),

                Tables\Columns\TextColumn::make('overall_experience')
                    ->label('Experience')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'positive' => 'success',
                        'neutral' => 'warning',
                        'negative' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('difficulty_rating')
                    ->label('Difficulty')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 4 => 'danger',
                        $state >= 3 => 'warning',
                        default => 'success',
                    }),

                Tables\Columns\TextColumn::make('outcome')
                    ->label('Outcome')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'accepted' => 'success',
                        'declined' => 'info',
                        'rejected' => 'danger',
                        'no_response' => 'gray',
                        'pending' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('helpful_votes')
                    ->label('Helpful')
                    ->sortable(),

                Tables\Columns\TextColumn::make('interview_date')
                    ->label('Interview Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),

                Tables\Filters\SelectFilter::make('overall_experience')
                    ->options([
                        'positive' => 'Positive',
                        'neutral' => 'Neutral',
                        'negative' => 'Negative',
                    ]),

                Tables\Filters\SelectFilter::make('outcome')
                    ->options([
                        'accepted' => 'Accepted Offer',
                        'declined' => 'Declined Offer',
                        'rejected' => 'No Offer',
                        'no_response' => 'No Response',
                        'pending' => 'Pending',
                    ]),

                Tables\Filters\SelectFilter::make('company')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (InterviewExperience $record): bool => $record->status !== 'approved')
                    ->action(function (InterviewExperience $record): void {
                        $record->update(['status' => 'approved']);
                        Notification::make()
                            ->title('Interview Experience Approved')
                            ->success()
                            ->send();
                    }),

                Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (InterviewExperience $record): bool => $record->status !== 'rejected')
                    ->requiresConfirmation()
                    ->action(function (InterviewExperience $record): void {
                        $record->update(['status' => 'rejected']);
                        Notification::make()
                            ->title('Interview Experience Rejected')
                            ->danger()
                            ->send();
                    }),

                Actions\ViewAction::make(),
                Actions\EditAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\BulkAction::make('approve_selected')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records): void {
                            $records->each(fn ($record) => $record->update(['status' => 'approved']));
                            Notification::make()
                                ->title('Interview Experiences Approved')
                                ->success()
                                ->send();
                        }),

                    Actions\BulkAction::make('reject_selected')
                        ->label('Reject Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records): void {
                            $records->each(fn ($record) => $record->update(['status' => 'rejected']));
                            Notification::make()
                                ->title('Interview Experiences Rejected')
                                ->danger()
                                ->send();
                        }),

                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInterviewExperiences::route('/'),
            'view' => Pages\ViewInterviewExperience::route('/{record}'),
            'edit' => Pages\EditInterviewExperience::route('/{record}/edit'),
        ];
    }
}
