<?php

namespace App\Filament\Widgets;

use App\Models\Application;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class LatestApplications extends TableWidget
{
    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn (): Builder => 
                    class_exists(Application::class) 
                        ? Application::query()->latest()->limit(10)
                        : (new class extends \Illuminate\Database\Eloquent\Model {
                            protected $table = 'applications';
                        })->query()->latest()->limit(10)
            )
            ->heading('Latest Job Applications')
            ->columns([
                TextColumn::make('user.name')
                    ->label('Applicant')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record): string => $record->user?->email ?? ''),

                TextColumn::make('job.title')
                    ->label('Job')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->description(fn ($record): string => $record->job?->company?->name ?? ''),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'reviewing' => 'info',
                        'shortlisted' => 'primary',
                        'accepted' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                TextColumn::make('created_at')
                    ->label('Applied')
                    ->dateTime('d M Y, H:i')
                    ->description(fn ($record): string => $record->created_at->diffForHumans())
                    ->sortable(),
            ])
            ->paginated(false);
    }
}
