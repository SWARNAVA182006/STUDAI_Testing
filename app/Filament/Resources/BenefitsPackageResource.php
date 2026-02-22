<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\BenefitsPackageResource\Pages;
use App\Models\BenefitsPackage;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Actions;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BenefitsPackageResource extends Resource
{
    protected static ?string $model = BenefitsPackage::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-gift';

    protected static \UnitEnum|string|null $navigationGroup = 'Hiring';

    protected static ?string $navigationLabel = 'Benefits Packages';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Package Information')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Textarea::make('description')
                            ->rows(2)
                            ->columnSpanFull(),

                        Toggle::make('is_default')
                            ->label('Default Package')
                            ->helperText('Use as default for new offers'),

                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ]),

                Section::make('Benefits')
                    ->schema([
                        Repeater::make('benefits')
                            ->schema([
                                Select::make('category')
                                    ->options([
                                        'Health & Wellness' => 'Health & Wellness',
                                        'Time Off' => 'Time Off',
                                        'Retirement' => 'Retirement',
                                        'Professional Development' => 'Professional Development',
                                        'Work Flexibility' => 'Work Flexibility',
                                        'Equipment' => 'Equipment',
                                        'Financial' => 'Financial',
                                        'Family' => 'Family',
                                        'Other' => 'Other',
                                    ])
                                    ->required(),

                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),

                                Textarea::make('description')
                                    ->rows(2),

                                TextInput::make('annual_value')
                                    ->label('Annual Value ($)')
                                    ->numeric()
                                    ->prefix('$')
                                    ->minValue(0)
                                    ->default(0),
                            ])
                            ->columns(2)
                            ->defaultItems(1)
                            ->addActionLabel('Add Benefit')
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => ($state['category'] ?? '') . ': ' . ($state['name'] ?? '')),
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

                TextColumn::make('company.name')
                    ->label('Company')
                    ->toggleable(),

                TextColumn::make('total_value')
                    ->label('Total Value')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('benefits')
                    ->label('Benefits Count')
                    ->formatStateUsing(fn ($state) => count($state ?? []))
                    ->badge(),

                IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                TextColumn::make('offer_letters_count')
                    ->label('Uses')
                    ->counts('offerLetters'),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active'),

                TernaryFilter::make('is_default')
                    ->label('Default'),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
                Actions\Action::make('duplicate')
                    ->label('Duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->action(function (BenefitsPackage $record) {
                        $new = $record->replicate();
                        $new->name = $record->name . ' (Copy)';
                        $new->is_default = false;
                        $new->save();
                    }),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListBenefitsPackages::route('/'),
            'create' => Pages\CreateBenefitsPackage::route('/create'),
            'view' => Pages\ViewBenefitsPackage::route('/{record}'),
            'edit' => Pages\EditBenefitsPackage::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        $query = parent::getEloquentQuery()->with(['company']);

        // Filter by company for non-admin users
        if (!$user->hasRole('super-admin') && $user->company_id) {
            $query->where('company_id', $user->company_id);
        }

        return $query;
    }
}
