<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\SocialProviderResource\Pages;
use App\Models\SocialProvider;
use Filament\Forms\Components\ColorPicker;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\KeyValue;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Actions;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SocialProviderResource extends Resource
{
    protected static ?string $model = SocialProvider::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-share';

    protected static \UnitEnum|string|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Social Login Providers';

    protected static ?int $navigationSort = 10;

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('is_enabled', true)->count();
    }

    public static function getNavigationBadgeColor(): string
    {
        $count = static::getModel()::where('is_enabled', true)->count();
        return $count > 0 ? 'success' : 'gray';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Provider Information')
                    ->description('Basic provider configuration')
                    ->columns(2)
                    ->schema([
                        Select::make('slug')
                            ->label('Provider')
                            ->options([
                                'google' => 'Google',
                                'linkedin' => 'LinkedIn',
                                'apple' => 'Apple',
                                'microsoft' => 'Microsoft',
                                'facebook' => 'Facebook',
                                'github' => 'GitHub',
                                'twitter' => 'Twitter/X',
                            ])
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $names = [
                                    'google' => 'Google',
                                    'linkedin' => 'LinkedIn',
                                    'apple' => 'Apple',
                                    'microsoft' => 'Microsoft',
                                    'facebook' => 'Facebook',
                                    'github' => 'GitHub',
                                    'twitter' => 'Twitter/X',
                                ];
                                $set('name', $names[$state] ?? $state);
                            }),

                        TextInput::make('name')
                            ->label('Display Name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('sort_order')
                            ->label('Display Order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Lower numbers appear first'),

                        Toggle::make('is_enabled')
                            ->label('Enabled')
                            ->default(false)
                            ->helperText('Enable this provider for social login'),
                    ]),

                Section::make('OAuth Credentials')
                    ->description('Enter the credentials from your OAuth provider dashboard')
                    ->columns(1)
                    ->schema([
                        TextInput::make('client_id')
                            ->label('Client ID')
                            ->placeholder('Enter your OAuth Client ID')
                            ->maxLength(255)
                            ->helperText('The Client ID from your OAuth provider')
                            ->required(),

                        TextInput::make('client_secret')
                            ->label('Client Secret')
                            ->placeholder('Enter your OAuth Client Secret')
                            ->password()
                            ->revealable()
                            ->maxLength(500)
                            ->helperText('The Client Secret from your OAuth provider')
                            ->required(),

                        TextInput::make('redirect_url')
                            ->label('Redirect URL')
                            ->placeholder('https://yourdomain.com/auth/{provider}/callback')
                            ->helperText(function () {
                                return 'Your callback URL. Configure this in your OAuth provider dashboard. Default: ' . url('/auth/{provider}/callback');
                            })
                            ->maxLength(500),
                    ]),

                Section::make('Scopes & Permissions')
                    ->description('Configure OAuth scopes (leave empty for defaults)')
                    ->schema([
                        TagsInput::make('scopes')
                            ->label('OAuth Scopes')
                            ->placeholder('Add scope')
                            ->helperText('Leave empty to use default scopes for this provider'),
                    ]),

                Section::make('Appearance')
                    ->description('Customize how this provider appears')
                    ->columns(2)
                    ->schema([
                        TextInput::make('icon')
                            ->label('Icon Class')
                            ->placeholder('heroicon-o-user')
                            ->maxLength(100)
                            ->helperText('Icon class (Heroicons or custom)'),

                        ColorPicker::make('color')
                            ->label('Brand Color')
                            ->helperText('The brand color for this provider'),
                    ]),

                Section::make('Advanced Settings')
                    ->description('Additional OAuth configuration')
                    ->collapsed()
                    ->schema([
                        KeyValue::make('additional_config')
                            ->label('Additional Settings')
                            ->keyLabel('Setting Name')
                            ->valueLabel('Setting Value')
                            ->helperText('Additional provider-specific settings'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable()
                    ->width(50),

                Tables\Columns\TextColumn::make('name')
                    ->label('Provider')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\IconColumn::make('is_configured')
                    ->label('Configured')
                    ->getStateUsing(fn ($record) => $record->isConfigured())
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\ToggleColumn::make('is_enabled')
                    ->label('Enabled')
                    ->sortable(),

                Tables\Columns\TextColumn::make('social_accounts_count')
                    ->label('Linked Accounts')
                    ->counts('socialAccounts')
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_enabled')
                    ->label('Status')
                    ->options([
                        true => 'Enabled',
                        false => 'Disabled',
                    ]),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\Action::make('test')
                    ->label('Test')
                    ->icon('heroicon-o-play')
                    ->color('info')
                    ->url(fn ($record) => route('social.redirect', $record->slug))
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => $record->isConfigured() && $record->is_enabled),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order');
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
            'index' => Pages\ListSocialProviders::route('/'),
            'create' => Pages\CreateSocialProvider::route('/create'),
            'edit' => Pages\EditSocialProvider::route('/{record}/edit'),
        ];
    }
}
