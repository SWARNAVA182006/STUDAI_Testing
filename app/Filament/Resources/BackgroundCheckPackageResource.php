<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\BackgroundCheckPackageResource\Pages;
use App\Models\BackgroundCheckPackage;
use Filament\Forms\Components\Checkbox;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Actions;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BackgroundCheckPackageResource extends Resource
{
    protected static ?string $model = BackgroundCheckPackage::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-cube';

    protected static \UnitEnum|string|null $navigationGroup = 'Hiring';

    protected static ?string $navigationLabel = 'Check Packages';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationParentItem = 'Background Checks';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Package Details')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1),

                        TextInput::make('slug')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->columnSpan(1),

                        Textarea::make('description')
                            ->rows(3)
                            ->columnSpan(2),
                    ]),

                Section::make('Provider Configuration')
                    ->columns(2)
                    ->schema([
                        Select::make('provider')
                            ->options([
                                'checkr' => 'Checkr',
                                'sterling' => 'Sterling',
                                'goodhire' => 'GoodHire',
                            ])
                            ->required(),

                        TextInput::make('provider_package_id')
                            ->label('Provider Package ID')
                            ->helperText('The package ID from the provider\'s system'),
                    ]),

                Section::make('Included Checks')
                    ->columns(3)
                    ->schema([
                        Select::make('checks_included')
                            ->label('Included Checks')
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
                                'ssn_trace' => 'SSN Trace',
                                'sex_offender' => 'Sex Offender Registry',
                                'global_watchlist' => 'Global Watchlist',
                            ])
                            ->columnSpan(3),
                    ]),

                Section::make('Pricing')
                    ->columns(3)
                    ->schema([
                        TextInput::make('price')
                            ->label('Base Price')
                            ->numeric()
                            ->prefix('$')
                            ->required()
                            ->minValue(0),

                        TextInput::make('estimated_days')
                            ->label('Est. Turnaround (Days)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(30),
                    ]),

                Section::make('Status & Settings')
                    ->columns(2)
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),

                        Toggle::make('is_default')
                            ->label('Default Package')
                            ->helperText('Use this package by default'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                BadgeColumn::make('provider')
                    ->colors([
                        'success' => 'checkr',
                        'info' => 'sterling',
                        'warning' => 'goodhire',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'checkr' => 'Checkr',
                        'sterling' => 'Sterling',
                        'goodhire' => 'GoodHire',
                        default => ucfirst($state),
                    }),

                TextColumn::make('checks_included')
                    ->label('Checks')
                    ->formatStateUsing(fn ($state) => is_array($state) ? count($state) . ' checks' : '-')
                    ->description(fn ($record) => is_array($record->checks_included) 
                        ? implode(', ', array_map(fn($c) => ucwords(str_replace('_', ' ', $c)), array_slice($record->checks_included, 0, 3)))
                        : ''
                    ),

                TextColumn::make('price')
                    ->label('Price')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('estimated_days')
                    ->label('Turnaround')
                    ->suffix(' days')
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray'),

                TextColumn::make('background_checks_count')
                    ->label('Uses')
                    ->counts('backgroundChecks')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('provider')
                    ->options([
                        'checkr' => 'Checkr',
                        'sterling' => 'Sterling',
                        'goodhire' => 'GoodHire',
                    ]),
                TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
                Actions\Action::make('setDefault')
                    ->label('Set as Default')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->visible(fn (BackgroundCheckPackage $record) => !$record->is_default && $record->is_active)
                    ->requiresConfirmation()
                    ->action(function (BackgroundCheckPackage $record) {
                        // Remove default from all packages of same provider
                        BackgroundCheckPackage::where('provider', $record->provider)
                            ->where('is_default', true)
                            ->update(['is_default' => false]);
                        
                        // Set this as default
                        $record->update(['is_default' => true]);
                    }),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                    Actions\BulkAction::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn ($records) => $records->each->update(['is_active' => true])),
                    Actions\BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn ($records) => $records->each->update(['is_active' => false])),
                ]),
            ])
            ->defaultSort('name');
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
            'index' => Pages\ListBackgroundCheckPackages::route('/'),
            'create' => Pages\CreateBackgroundCheckPackage::route('/create'),
            'view' => Pages\ViewBackgroundCheckPackage::route('/{record}'),
            'edit' => Pages\EditBackgroundCheckPackage::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('backgroundChecks');
    }
}
