<?php

namespace App\Filament\Resources\Jobs\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class JobsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Job Title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record): string => $record->company?->name ?? 'No Company')
                    ->copyable()
                    ->copyMessage('Job title copied'),
                
                TextColumn::make('company.name')
                    ->label('Company')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'active' => 'success',
                        'paused' => 'warning',
                        'closed' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable()
                    ->searchable(),
                
                TextColumn::make('employment_type')
                    ->label('Type')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn (string $state): string => str_replace('-', ' ', ucwords($state, '-')))
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('experience_level')
                    ->label('Level')
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                
                TextColumn::make('work_mode')
                    ->label('Work Mode')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'remote' => 'success',
                        'hybrid' => 'warning',
                        'onsite' => 'info',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'remote' => 'heroicon-o-globe-alt',
                        'hybrid' => 'heroicon-o-building-office',
                        'onsite' => 'heroicon-o-map-pin',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable()
                    ->toggleable(),
                
                TextColumn::make('location')
                    ->icon('heroicon-o-map-pin')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                
                TextColumn::make('salary_range')
                    ->label('Salary')
                    ->getStateUsing(function ($record): string {
                        if (!$record->salary_min && !$record->salary_max) {
                            return 'Not Disclosed';
                        }
                        
                        $currency = $record->salary_currency === 'INR' ? '₹' : $record->salary_currency . ' ';
                        
                        if ($record->salary_min && $record->salary_max) {
                            $min = number_format($record->salary_min / 100000, 1) . 'L';
                            $max = number_format($record->salary_max / 100000, 1) . 'L';
                            $range = "{$currency}{$min} - {$max}";
                        } elseif ($record->salary_min) {
                            $min = number_format($record->salary_min / 100000, 1) . 'L';
                            $range = "{$currency}{$min}+";
                        } else {
                            $max = number_format($record->salary_max / 100000, 1) . 'L';
                            $range = "Up to {$currency}{$max}";
                        }
                        
                        return $record->salary_negotiable ? "{$range} (Negotiable)" : $range;
                    })
                    ->icon('heroicon-o-currency-rupee')
                    ->color('success')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('salary_min', $direction);
                    })
                    ->toggleable(),
                
                TextColumn::make('openings')
                    ->label('Positions')
                    ->numeric()
                    ->icon('heroicon-o-users')
                    ->sortable()
                    ->toggleable(),
                
                TextColumn::make('deadline')
                    ->label('Deadline')
                    ->date('d M Y')
                    ->description(fn ($record): ?string => $record->deadline ? $record->deadline->diffForHumans() : null)
                    ->icon('heroicon-o-calendar')
                    ->color(fn ($record): string => $record->deadline && $record->deadline->isPast() ? 'danger' : 'gray')
                    ->sortable()
                    ->toggleable(),
                
                IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->sortable()
                    ->toggleable(),
                
                IconColumn::make('is_urgent')
                    ->label('Urgent')
                    ->boolean()
                    ->trueIcon('heroicon-o-bolt')
                    ->falseIcon('heroicon-o-bolt')
                    ->trueColor('danger')
                    ->falseColor('gray')
                    ->sortable()
                    ->toggleable(),
                
                TextColumn::make('views')
                    ->label('Views')
                    ->numeric()
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->sortable()
                    ->toggleable(),
                
                TextColumn::make('applications_count')
                    ->label('Applications')
                    ->numeric()
                    ->icon('heroicon-o-document-text')
                    ->color('primary')
                    ->sortable(),
                
                TextColumn::make('quality_score')
                    ->label('Quality')
                    ->numeric(decimalPlaces: 1)
                    ->suffix(' / 100')
                    ->color(fn ($state): string => match (true) {
                        $state >= 80 => 'success',
                        $state >= 60 => 'info',
                        $state >= 40 => 'warning',
                        default => 'danger',
                    })
                    ->icon('heroicon-o-star')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('created_at')
                    ->label('Posted')
                    ->dateTime('d M Y, H:i')
                    ->description(fn ($record): string => $record->created_at->diffForHumans())
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('d M Y, H:i')
                    ->description(fn ($record): string => $record->updated_at->diffForHumans())
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'active' => 'Active',
                        'paused' => 'Paused',
                        'closed' => 'Closed',
                    ])
                    ->multiple()
                    ->indicator('Status'),
                
                SelectFilter::make('employment_type')
                    ->label('Employment Type')
                    ->options([
                        'full-time' => 'Full Time',
                        'part-time' => 'Part Time',
                        'contract' => 'Contract',
                        'internship' => 'Internship',
                        'temporary' => 'Temporary',
                        'freelance' => 'Freelance',
                    ])
                    ->multiple()
                    ->indicator('Type'),
                
                SelectFilter::make('experience_level')
                    ->label('Experience Level')
                    ->options([
                        'entry' => 'Entry Level',
                        'mid' => 'Mid Level',
                        'senior' => 'Senior Level',
                        'lead' => 'Lead',
                        'executive' => 'Executive',
                    ])
                    ->multiple()
                    ->indicator('Experience'),
                
                SelectFilter::make('work_mode')
                    ->label('Work Mode')
                    ->options([
                        'remote' => 'Remote',
                        'hybrid' => 'Hybrid',
                        'onsite' => 'Onsite',
                    ])
                    ->multiple()
                    ->indicator('Work Mode'),
                
                SelectFilter::make('company_id')
                    ->label('Company')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple()
                    ->indicator('Company'),
                
                TernaryFilter::make('is_featured')
                    ->label('Featured Jobs')
                    ->indicator('Featured')
                    ->trueLabel('Featured only')
                    ->falseLabel('Not featured')
                    ->placeholder('All jobs'),
                
                TernaryFilter::make('is_urgent')
                    ->label('Urgent Hiring')
                    ->indicator('Urgent')
                    ->trueLabel('Urgent only')
                    ->falseLabel('Not urgent')
                    ->placeholder('All jobs'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->iconButton()
                    ->tooltip('View Job'),
                
                EditAction::make()
                    ->iconButton()
                    ->tooltip('Edit Job'),
                
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Job Posting')
                    ->modalDescription('This will make the job active and visible on the platform.')
                    ->action(function ($record) {
                        $record->update(['status' => 'active']);
                        Notification::make()
                            ->title('Job Approved')
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record): bool => $record->status === 'draft'),
                
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Reject Job Posting')
                    ->modalDescription('This will close the job and prevent it from being published.')
                    ->action(function ($record) {
                        $record->update(['status' => 'closed']);
                        Notification::make()
                            ->title('Job Rejected')
                            ->warning()
                            ->send();
                    })
                    ->visible(fn ($record): bool => $record->status === 'draft'),
                
                Action::make('pause')
                    ->label('Pause')
                    ->icon('heroicon-o-pause-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Pause Job Posting')
                    ->modalDescription('This will temporarily hide the job from the platform.')
                    ->action(function ($record) {
                        $record->update(['status' => 'paused']);
                        Notification::make()
                            ->title('Job Paused')
                            ->warning()
                            ->send();
                    })
                    ->visible(fn ($record): bool => $record->status === 'active'),
                
                Action::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-o-play-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Activate Job Posting')
                    ->modalDescription('This will make the job active and visible on the platform.')
                    ->action(function ($record) {
                        $record->update(['status' => 'active']);
                        Notification::make()
                            ->title('Job Activated')
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record): bool => in_array($record->status, ['paused', 'closed'])),
                
                Action::make('toggle_featured')
                    ->label(fn ($record): string => $record->is_featured ? 'Unfeature' : 'Feature')
                    ->icon(fn ($record): string => $record->is_featured ? 'heroicon-o-star' : 'heroicon-o-star')
                    ->color(fn ($record): string => $record->is_featured ? 'gray' : 'warning')
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record): string => $record->is_featured ? 'Remove Featured Status' : 'Feature Job')
                    ->action(function ($record) {
                        $newStatus = !$record->is_featured;
                        $record->update(['is_featured' => $newStatus]);
                        Notification::make()
                            ->title($newStatus ? 'Job Featured' : 'Featured Status Removed')
                            ->success()
                            ->send();
                    }),
                
                Action::make('toggle_urgent')
                    ->label(fn ($record): string => $record->is_urgent ? 'Remove Urgent' : 'Mark Urgent')
                    ->icon('heroicon-o-bolt')
                    ->color(fn ($record): string => $record->is_urgent ? 'gray' : 'danger')
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record): string => $record->is_urgent ? 'Remove Urgent Status' : 'Mark as Urgent')
                    ->action(function ($record) {
                        $newStatus = !$record->is_urgent;
                        $record->update(['is_urgent' => $newStatus]);
                        Notification::make()
                            ->title($newStatus ? 'Job Marked as Urgent' : 'Urgent Status Removed')
                            ->success()
                            ->send();
                    }),
                
                Action::make('view_applications')
                    ->label('Applications')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->url(fn ($record): string => route('filament.admin.resources.applications.index', ['job_id' => $record->id]))
                    ->visible(fn ($record): bool => $record->applications_count > 0),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    
                    BulkAction::make('approve')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $count = $records->count();
                            $records->each->update(['status' => 'active']);
                            Notification::make()
                                ->title("{$count} job(s) approved")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    
                    BulkAction::make('pause')
                        ->label('Pause Selected')
                        ->icon('heroicon-o-pause-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $count = $records->count();
                            $records->each->update(['status' => 'paused']);
                            Notification::make()
                                ->title("{$count} job(s) paused")
                                ->warning()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    
                    BulkAction::make('close')
                        ->label('Close Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $count = $records->count();
                            $records->each->update(['status' => 'closed']);
                            Notification::make()
                                ->title("{$count} job(s) closed")
                                ->danger()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    
                    BulkAction::make('feature')
                        ->label('Feature Selected')
                        ->icon('heroicon-o-star')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $count = $records->count();
                            $records->each->update(['is_featured' => true]);
                            Notification::make()
                                ->title("{$count} job(s) featured")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    
                    BulkAction::make('unfeature')
                        ->label('Remove Featured')
                        ->icon('heroicon-o-x-mark')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $count = $records->count();
                            $records->each->update(['is_featured' => false]);
                            Notification::make()
                                ->title("Featured status removed from {$count} job(s)")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    
                    BulkAction::make('export')
                        ->label('Export to Excel')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->action(function (Collection $records) {
                            // TODO: Implement OpenSpout export functionality
                            Notification::make()
                                ->title('Export functionality coming soon')
                                ->info()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s')
            ->deferLoading()
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->recordUrl(fn ($record): string => route('filament.admin.resources.jobs.view', ['record' => $record]));
    }
}
