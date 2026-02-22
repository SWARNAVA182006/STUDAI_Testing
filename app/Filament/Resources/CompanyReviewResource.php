<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyReviewResource\Pages;
use App\Models\CompanyReview;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Actions;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CompanyReviewResource extends Resource
{
    protected static ?string $model = CompanyReview::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-star';

    protected static \UnitEnum|string|null $navigationGroup = 'Reviews & Ratings';

    protected static ?string $navigationLabel = 'Company Reviews';

    protected static ?int $navigationSort = 1;

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
                Section::make('Review Information')
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
                            ->maxLength(255)
                            ->disabled(),

                        Select::make('employment_status')
                            ->options([
                                'current' => 'Current Employee',
                                'former' => 'Former Employee',
                            ])
                            ->disabled(),
                    ]),

                Section::make('Ratings')
                    ->columns(3)
                    ->schema([
                        TextInput::make('overall_rating')
                            ->numeric()
                            ->disabled(),

                        TextInput::make('culture_rating')
                            ->numeric()
                            ->disabled(),

                        TextInput::make('compensation_rating')
                            ->numeric()
                            ->disabled(),

                        TextInput::make('work_life_balance_rating')
                            ->numeric()
                            ->disabled(),

                        TextInput::make('career_growth_rating')
                            ->numeric()
                            ->disabled(),

                        TextInput::make('management_rating')
                            ->numeric()
                            ->disabled(),
                    ]),

                Section::make('Review Content')
                    ->schema([
                        TextInput::make('headline')
                            ->maxLength(255)
                            ->disabled(),

                        Textarea::make('pros')
                            ->rows(4)
                            ->disabled(),

                        Textarea::make('cons')
                            ->rows(4)
                            ->disabled(),

                        Textarea::make('advice_to_management')
                            ->rows(3)
                            ->disabled(),
                    ]),

                Section::make('Moderation')
                    ->schema([
                        Select::make('status')
                            ->options([
                                'pending' => 'Pending Review',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                                'flagged' => 'Flagged for Review',
                            ])
                            ->required(),

                        Textarea::make('moderation_notes')
                            ->label('Moderation Notes')
                            ->placeholder('Add notes about moderation decision...')
                            ->rows(3),

                        Toggle::make('is_featured')
                            ->label('Feature this review')
                            ->helperText('Featured reviews appear prominently on the company page'),
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
                    ->label('Job Title')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('overall_rating')
                    ->label('Rating')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 4 => 'success',
                        $state >= 3 => 'warning',
                        default => 'danger',
                    }),

                Tables\Columns\TextColumn::make('headline')
                    ->label('Headline')
                    ->limit(40)
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        'flagged' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('employment_status')
                    ->label('Employee')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'current' => 'success',
                        'former' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('helpful_votes')
                    ->label('Helpful')
                    ->sortable(),

                Tables\Columns\IconColumn::make('recommend_to_friend')
                    ->label('Recommends')
                    ->boolean(),

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
                        'flagged' => 'Flagged',
                    ]),

                Tables\Filters\SelectFilter::make('company')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('created_today')
                    ->query(fn (Builder $query): Builder => $query->whereDate('created_at', today()))
                    ->label('Submitted Today'),

                Tables\Filters\Filter::make('high_rated')
                    ->query(fn (Builder $query): Builder => $query->where('overall_rating', '>=', 4))
                    ->label('High Rated (4+)'),
            ])
            ->actions([
                Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (CompanyReview $record): bool => $record->status !== 'approved')
                    ->action(function (CompanyReview $record): void {
                        $record->update(['status' => 'approved']);
                        $record->company->recalculateAllRatings();
                        Notification::make()
                            ->title('Review Approved')
                            ->success()
                            ->send();
                    }),

                Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (CompanyReview $record): bool => $record->status !== 'rejected')
                    ->requiresConfirmation()
                    ->action(function (CompanyReview $record): void {
                        $record->update(['status' => 'rejected']);
                        Notification::make()
                            ->title('Review Rejected')
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
                            $records->each(function ($record): void {
                                $record->update(['status' => 'approved']);
                                $record->company->recalculateAllRatings();
                            });
                            Notification::make()
                                ->title('Reviews Approved')
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
                                ->title('Reviews Rejected')
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
            'index' => Pages\ListCompanyReviews::route('/'),
            'view' => Pages\ViewCompanyReview::route('/{record}'),
            'edit' => Pages\EditCompanyReview::route('/{record}/edit'),
        ];
    }
}
