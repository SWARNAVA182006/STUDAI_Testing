<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\VideoInterviewSessionResource\Pages;
use App\Models\VideoInterviewSession;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DateTimePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Actions;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VideoInterviewSessionResource extends Resource
{
    protected static ?string $model = VideoInterviewSession::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-video-camera';

    protected static \UnitEnum|string|null $navigationGroup = 'Interviews';

    protected static ?string $navigationLabel = 'Video Interviews';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('status', 'in_progress')->count();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Session Information')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255),

                        Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('company_id')
                            ->relationship('company', 'name')
                            ->searchable()
                            ->preload(),

                        Select::make('job_id')
                            ->relationship('job', 'title')
                            ->searchable()
                            ->preload(),

                        Select::make('type')
                            ->options([
                                'async' => 'Asynchronous',
                                'live' => 'Live',
                                'mock' => 'Practice/Mock',
                                'hybrid' => 'Hybrid',
                            ])
                            ->required(),

                        Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'in_progress' => 'In Progress',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                                'expired' => 'Expired',
                            ])
                            ->required(),
                    ]),

                Section::make('Timing')
                    ->columns(2)
                    ->schema([
                        DateTimePicker::make('scheduled_at'),
                        DateTimePicker::make('expires_at'),
                        DateTimePicker::make('started_at')
                            ->disabled(),
                        DateTimePicker::make('completed_at')
                            ->disabled(),
                    ]),

                Section::make('Settings')
                    ->columns(3)
                    ->schema([
                        TextInput::make('max_duration_minutes')
                            ->numeric()
                            ->default(60),

                        Toggle::make('allow_retakes')
                            ->default(false),

                        TextInput::make('max_retakes')
                            ->numeric()
                            ->default(1),

                        Toggle::make('has_screen_share'),

                        Toggle::make('is_recording_enabled')
                            ->default(true),
                    ]),

                Section::make('Analysis Summary')
                    ->schema([
                        TextInput::make('overall_score')
                            ->numeric()
                            ->disabled(),
                    ])
                    ->visible(fn ($record) => $record && $record->status === 'completed'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Candidate')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'async' => 'info',
                        'live' => 'success',
                        'mock' => 'warning',
                        'hybrid' => 'primary',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'in_progress' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        'expired' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('overall_score')
                    ->label('Score')
                    ->suffix('%')
                    ->color(fn ($state): string => match (true) {
                        $state >= 85 => 'success',
                        $state >= 70 => 'warning',
                        $state > 0 => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('questions_count')
                    ->label('Questions')
                    ->counts('questions'),

                Tables\Columns\TextColumn::make('recordings_count')
                    ->label('Recordings')
                    ->counts('recordings'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('completed_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        'expired' => 'Expired',
                    ]),

                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'async' => 'Asynchronous',
                        'live' => 'Live',
                        'mock' => 'Practice/Mock',
                        'hybrid' => 'Hybrid',
                    ]),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListVideoInterviewSessions::route('/'),
            'create' => Pages\CreateVideoInterviewSession::route('/create'),
            'view' => Pages\ViewVideoInterviewSession::route('/{record}'),
            'edit' => Pages\EditVideoInterviewSession::route('/{record}/edit'),
        ];
    }
}
