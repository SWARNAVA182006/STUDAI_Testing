<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\SalaryReportResource\Pages;
use App\Models\SalaryReport;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Actions;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SalaryReportResource extends Resource
{
    protected static ?string $model = SalaryReport::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-currency-dollar';

    protected static \UnitEnum|string|null $navigationGroup = 'Reviews & Ratings';

    protected static ?string $navigationLabel = 'Salary Reports';

    protected static ?int $navigationSort = 2;

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
                Section::make('Employment Information')
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

                        TextInput::make('years_of_experience')
                            ->numeric()
                            ->disabled(),
                    ]),

                Section::make('Compensation')
                    ->columns(3)
                    ->schema([
                        TextInput::make('base_salary')
                            ->prefix('$')
                            ->numeric()
                            ->disabled(),

                        TextInput::make('bonus')
                            ->prefix('$')
                            ->numeric()
                            ->disabled(),

                        TextInput::make('stock_options')
                            ->prefix('$')
                            ->numeric()
                            ->disabled(),

                        TextInput::make('signing_bonus')
                            ->prefix('$')
                            ->numeric()
                            ->disabled(),

                        TextInput::make('total_compensation')
                            ->prefix('$')
                            ->numeric()
                            ->disabled(),

                        Select::make('pay_period')
                            ->options([
                                'hourly' => 'Hourly',
                                'monthly' => 'Monthly',
                                'yearly' => 'Yearly',
                            ])
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
                    ->label('Job Title')
                    ->searchable()
                    ->limit(25),

                Tables\Columns\TextColumn::make('location')
                    ->label('Location')
                    ->searchable()
                    ->limit(20),

                Tables\Columns\TextColumn::make('base_salary')
                    ->label('Base Salary')
                    ->money('usd')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_compensation')
                    ->label('Total Comp')
                    ->money('usd')
                    ->sortable(),

                Tables\Columns\TextColumn::make('years_of_experience')
                    ->label('Experience')
                    ->suffix(' yrs'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\IconColumn::make('is_current_employee')
                    ->label('Current')
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
                    ]),

                Tables\Filters\SelectFilter::make('company')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('high_salary')
                    ->query(fn (Builder $query): Builder => $query->where('base_salary', '>=', 150000))
                    ->label('High Salary ($150k+)'),
            ])
            ->actions([
                Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (SalaryReport $record): bool => $record->status !== 'approved')
                    ->action(function (SalaryReport $record): void {
                        $record->update(['status' => 'approved']);
                        Notification::make()
                            ->title('Salary Report Approved')
                            ->success()
                            ->send();
                    }),

                Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (SalaryReport $record): bool => $record->status !== 'rejected')
                    ->requiresConfirmation()
                    ->action(function (SalaryReport $record): void {
                        $record->update(['status' => 'rejected']);
                        Notification::make()
                            ->title('Salary Report Rejected')
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
                                ->title('Salary Reports Approved')
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
                                ->title('Salary Reports Rejected')
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
            'index' => Pages\ListSalaryReports::route('/'),
            'view' => Pages\ViewSalaryReport::route('/{record}'),
            'edit' => Pages\EditSalaryReport::route('/{record}/edit'),
        ];
    }
}
