<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\OfferLetterTemplateResource\Pages;
use App\Models\OfferLetterTemplate;
use Filament\Forms\Components\RichEditor;
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

class OfferLetterTemplateResource extends Resource
{
    protected static ?string $model = OfferLetterTemplate::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-duplicate';

    protected static \UnitEnum|string|null $navigationGroup = 'Hiring';

    protected static ?string $navigationLabel = 'Offer Templates';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Template Information')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('slug')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Auto-generated from name'),

                        Textarea::make('description')
                            ->rows(2)
                            ->columnSpanFull(),

                        Select::make('type')
                            ->options([
                                'custom' => 'Custom',
                                'system' => 'System',
                            ])
                            ->default('custom')
                            ->disabled(fn () => !auth()->user()->hasRole('super-admin')),

                        Toggle::make('is_default')
                            ->label('Default Template')
                            ->helperText('Use as default for new offers'),

                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ]),

                Section::make('Template Content')
                    ->schema([
                        RichEditor::make('content_html')
                            ->label('Letter Content')
                            ->required()
                            ->toolbarButtons([
                                'blockquote',
                                'bold',
                                'bulletList',
                                'h2',
                                'h3',
                                'italic',
                                'link',
                                'orderedList',
                                'redo',
                                'strike',
                                'underline',
                                'undo',
                            ])
                            ->helperText('Use {{variable_name}} for dynamic content. Available: {{candidate_name}}, {{job_title}}, {{department}}, {{base_salary}}, {{currency}}, {{start_date}}, {{offer_expiry_date}}, {{company_name}}, {{today_date}}')
                            ->columnSpanFull(),
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

                BadgeColumn::make('type')
                    ->colors([
                        'primary' => 'system',
                        'secondary' => 'custom',
                    ]),

                TextColumn::make('company.name')
                    ->label('Company')
                    ->placeholder('System-wide')
                    ->toggleable(),

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
                SelectFilter::make('type')
                    ->options([
                        'system' => 'System',
                        'custom' => 'Custom',
                    ]),

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
                    ->action(function (OfferLetterTemplate $record) {
                        $new = $record->replicate();
                        $new->name = $record->name . ' (Copy)';
                        $new->slug = null; // Will be auto-generated
                        $new->is_default = false;
                        $new->type = 'custom';
                        $new->company_id = auth()->user()->company_id;
                        $new->save();
                    }),
                Actions\DeleteAction::make()
                    ->visible(fn ($record) => $record->type !== 'system'),
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
            'index' => Pages\ListOfferLetterTemplates::route('/'),
            'create' => Pages\CreateOfferLetterTemplate::route('/create'),
            'view' => Pages\ViewOfferLetterTemplate::route('/{record}'),
            'edit' => Pages\EditOfferLetterTemplate::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        $query = parent::getEloquentQuery();

        // Show system templates + company templates for non-admin users
        if (!$user->hasRole('super-admin') && $user->company_id) {
            $query->where(function ($q) use ($user) {
                $q->where('company_id', $user->company_id)
                  ->orWhere('type', 'system');
            });
        }

        return $query;
    }
}
