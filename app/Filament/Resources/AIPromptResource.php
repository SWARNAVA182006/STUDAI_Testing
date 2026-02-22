<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\AIPromptResource\Pages;
use App\Models\AIPrompt;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\KeyValue;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Actions;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AIPromptResource extends Resource
{
    protected static ?string $model = AIPrompt::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-cpu-chip';

    protected static \UnitEnum|string|null $navigationGroup = 'AI Management';

    protected static ?string $navigationLabel = 'AI Prompts';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('is_active', true)->count();
    }

    public static function getNavigationBadgeColor(): string
    {
        $count = static::getModel()::where('is_active', true)->count();
        return $count > 0 ? 'success' : 'gray';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Prompt Information')
                    ->description('Basic prompt configuration')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Prompt Name')
                            ->placeholder('e.g., resume_summary_generator')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Unique identifier for this prompt (use snake_case)'),

                        Select::make('category')
                            ->label('Category')
                            ->options(AIPrompt::CATEGORIES)
                            ->required()
                            ->helperText('Group this prompt belongs to'),

                        TextInput::make('version')
                            ->label('Version')
                            ->numeric()
                            ->default(1)
                            ->required()
                            ->disabled(fn ($context) => $context === 'edit')
                            ->helperText('Auto-incremented on new versions'),

                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Only active prompts are used by the system'),

                        Textarea::make('description')
                            ->label('Description')
                            ->placeholder('Describe what this prompt does...')
                            ->maxLength(500)
                            ->columnSpanFull()
                            ->helperText('Brief description for documentation'),
                    ]),

                Section::make('System Prompt')
                    ->description('The system instructions sent to the AI model')
                    ->schema([
                        Textarea::make('system_prompt')
                            ->label('System Prompt')
                            ->placeholder('You are a professional resume writer with 10 years of experience...')
                            ->rows(5)
                            ->maxLength(10000)
                            ->helperText('Sets the AI\'s behavior and context'),
                    ]),

                Section::make('Prompt Template')
                    ->description('The main prompt template with variable placeholders')
                    ->schema([
                        Textarea::make('template')
                            ->label('Prompt Template')
                            ->placeholder('Generate a professional summary for a {role} with experience in {skills}...')
                            ->rows(10)
                            ->required()
                            ->maxLength(50000)
                            ->helperText('Use {variable} or {{ variable }} for placeholders'),
                    ]),

                Section::make('Model Configuration')
                    ->description('AI model settings for this prompt')
                    ->columns(3)
                    ->schema([
                        Select::make('model_hint')
                            ->label('Preferred Model')
                            ->options([
                                'gpt-5.1' => 'GPT-5.1 (Default)',
                                'gpt-4o' => 'GPT-4o',
                                'claude-sonnet-4-5' => 'Claude Sonnet 4.5',
                                'claude-opus-4' => 'Claude Opus 4',
                            ])
                            ->helperText('Suggested model for this prompt'),

                        TextInput::make('max_tokens')
                            ->label('Max Tokens')
                            ->numeric()
                            ->placeholder('16384')
                            ->minValue(100)
                            ->maxValue(128000)
                            ->helperText('Maximum output tokens'),

                        TextInput::make('temperature')
                            ->label('Temperature')
                            ->numeric()
                            ->placeholder('0.7')
                            ->step(0.1)
                            ->minValue(0)
                            ->maxValue(2)
                            ->helperText('Creativity (0=deterministic, 2=creative)'),
                    ]),

                Section::make('Variables')
                    ->description('Define expected variables for this prompt')
                    ->collapsed()
                    ->schema([
                        KeyValue::make('variables')
                            ->label('Variable Definitions')
                            ->keyLabel('Variable Name')
                            ->valueLabel('Description')
                            ->helperText('Document expected variables and their purpose'),
                    ]),

                Section::make('Additional Metadata')
                    ->description('Extra configuration and notes')
                    ->collapsed()
                    ->schema([
                        KeyValue::make('metadata')
                            ->label('Metadata')
                            ->keyLabel('Key')
                            ->valueLabel('Value')
                            ->helperText('Additional key-value configuration'),
                    ]),

                Section::make('Performance Metrics')
                    ->description('Automatically tracked usage statistics')
                    ->columns(3)
                    ->visible(fn ($context) => $context === 'edit')
                    ->schema([
                        TextInput::make('usage_count')
                            ->label('Total Uses')
                            ->disabled()
                            ->default(0),

                        TextInput::make('avg_latency_ms')
                            ->label('Avg Latency (ms)')
                            ->disabled()
                            ->suffix('ms'),

                        TextInput::make('success_rate')
                            ->label('Success Rate')
                            ->disabled()
                            ->suffix('%'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),

                Tables\Columns\TextColumn::make('category')
                    ->label('Category')
                    ->badge()
                    ->formatStateUsing(fn ($state) => AIPrompt::CATEGORIES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'resume' => 'info',
                        'interview' => 'success',
                        'job_matching' => 'warning',
                        'cover_letter' => 'primary',
                        'skill_analysis' => 'danger',
                        'negotiation' => 'gray',
                        'career_advice' => 'success',
                        'scout' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('version')
                    ->label('Version')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('model_hint')
                    ->label('Model')
                    ->badge()
                    ->color('info')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('usage_count')
                    ->label('Uses')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('success_rate')
                    ->label('Success %')
                    ->numeric(2)
                    ->suffix('%')
                    ->sortable()
                    ->color(fn ($state) => match (true) {
                        $state >= 95 => 'success',
                        $state >= 80 => 'warning',
                        default => 'danger',
                    }),

                Tables\Columns\TextColumn::make('avg_latency_ms')
                    ->label('Latency')
                    ->numeric(0)
                    ->suffix('ms')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Category')
                    ->options(AIPrompt::CATEGORIES),

                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ]),

                Tables\Filters\SelectFilter::make('model_hint')
                    ->label('Model')
                    ->options([
                        'gpt-5.1' => 'GPT-5.1',
                        'gpt-4o' => 'GPT-4o',
                        'claude-sonnet-4-5' => 'Claude Sonnet 4.5',
                        'claude-opus-4' => 'Claude Opus 4',
                    ]),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
                Actions\Action::make('duplicate')
                    ->label('New Version')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Create New Version')
                    ->modalDescription('This will create a new version of this prompt.')
                    ->action(function (AIPrompt $record) {
                        $newVersion = $record->createNewVersion(['is_active' => false]);
                        return redirect()->to(static::getUrl('edit', ['record' => $newVersion]));
                    }),
                Actions\Action::make('activate')
                    ->label('Set Active')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (AIPrompt $record) => !$record->is_active)
                    ->action(fn (AIPrompt $record) => $record->setAsActive()),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name')
            ->defaultGroup('category');
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
            'index' => Pages\ListAIPrompts::route('/'),
            'create' => Pages\CreateAIPrompt::route('/create'),
            'view' => Pages\ViewAIPrompt::route('/{record}'),
            'edit' => Pages\EditAIPrompt::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'description', 'template'];
    }
}
