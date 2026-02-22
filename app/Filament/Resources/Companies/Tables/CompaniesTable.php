<?php

namespace App\Filament\Resources\Companies\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CompaniesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo')
                    ->label('Logo')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&color=a855f7&background=fdf4ff')
                    ->size(40),

                TextColumn::make('name')
                    ->label('Company Name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->description(fn ($record) => $record->industry ?? 'No industry specified')
                    ->copyable()
                    ->copyMessage('Company name copied!')
                    ->tooltip('Click to copy'),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-o-link')
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('website')
                    ->label('Website')
                    ->searchable()
                    ->url(fn ($record) => $record->website)
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-globe-alt')
                    ->color('primary')
                    ->placeholder('No website')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->website),

                TextColumn::make('industry')
                    ->label('Industry')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state)))
                    ->searchable()
                    ->sortable()
                    ->placeholder('Not specified'),

                TextColumn::make('company_size')
                    ->label('Size')
                    ->icon('heroicon-o-users')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Not specified'),

                TextColumn::make('headquarters')
                    ->label('Headquarters')
                    ->icon('heroicon-o-map-pin')
                    ->searchable()
                    ->placeholder('Not specified')
                    ->toggleable(),

                TextColumn::make('founded_year')
                    ->label('Founded')
                    ->icon('heroicon-o-calendar')
                    ->sortable()
                    ->placeholder('N/A')
                    ->toggleable(),

                TextColumn::make('culture_rating')
                    ->label('Culture')
                    ->numeric(decimalPlaces: 1)
                    ->sortable()
                    ->icon('heroicon-o-star')
                    ->color(fn ($state) => match(true) {
                        $state >= 4.5 => 'success',
                        $state >= 4.0 => 'info',
                        $state >= 3.5 => 'warning',
                        default => 'danger',
                    })
                    ->suffix(' / 5.0')
                    ->placeholder('No rating')
                    ->toggleable(),

                IconColumn::make('is_verified')
                    ->label('Verified')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->sortable()
                    ->tooltip(fn ($state) => $state ? 'Verified Company' : 'Not Verified'),

                IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->sortable()
                    ->tooltip(fn ($state) => $state ? 'Featured' : 'Not Featured'),

                TextColumn::make('created_at')
                    ->label('Added')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->icon('heroicon-o-calendar')
                    ->description(fn ($record) => $record->created_at->diffForHumans())
                    ->toggleable(),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('industry')
                    ->label('Industry')
                    ->options([
                        'technology' => 'Technology',
                        'finance' => 'Finance & Banking',
                        'healthcare' => 'Healthcare',
                        'education' => 'Education',
                        'ecommerce' => 'E-commerce & Retail',
                        'manufacturing' => 'Manufacturing',
                        'consulting' => 'Consulting',
                        'marketing' => 'Marketing & Advertising',
                        'real_estate' => 'Real Estate',
                        'automotive' => 'Automotive',
                        'hospitality' => 'Hospitality & Travel',
                        'logistics' => 'Logistics & Supply Chain',
                        'telecommunications' => 'Telecommunications',
                        'energy' => 'Energy & Utilities',
                        'agriculture' => 'Agriculture',
                        'media' => 'Media & Entertainment',
                        'nonprofit' => 'Non-Profit',
                        'government' => 'Government',
                        'other' => 'Other',
                    ])
                    ->indicator('Industry')
                    ->multiple(),

                SelectFilter::make('company_size')
                    ->label('Company Size')
                    ->options([
                        '1-10' => '1-10 employees',
                        '11-50' => '11-50 employees',
                        '51-200' => '51-200 employees',
                        '201-500' => '201-500 employees',
                        '501-1000' => '501-1,000 employees',
                        '1001-5000' => '1,001-5,000 employees',
                        '5001-10000' => '5,001-10,000 employees',
                        '10000+' => '10,000+ employees',
                    ])
                    ->indicator('Size')
                    ->multiple(),

                TernaryFilter::make('is_verified')
                    ->label('Verification Status')
                    ->placeholder('All companies')
                    ->trueLabel('Verified only')
                    ->falseLabel('Unverified only')
                    ->indicator('Verification'),

                TernaryFilter::make('is_featured')
                    ->label('Featured Status')
                    ->placeholder('All companies')
                    ->trueLabel('Featured only')
                    ->falseLabel('Not featured only')
                    ->indicator('Featured'),
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                ViewAction::make()
                    ->iconButton()
                    ->tooltip('View details'),

                EditAction::make()
                    ->iconButton()
                    ->tooltip('Edit company'),

                Action::make('verify')
                    ->label('Verify')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Verify Company')
                    ->modalDescription('Mark this company as verified after document verification?')
                    ->modalSubmitActionLabel('Yes, verify')
                    ->action(function ($record) {
                        $record->update(['is_verified' => true]);
                        Notification::make()
                            ->title('Company Verified')
                            ->success()
                            ->body($record->name . ' has been verified.')
                            ->send();
                    })
                    ->visible(fn ($record) => !$record->is_verified)
                    ->iconButton()
                    ->tooltip('Verify company'),

                Action::make('unverify')
                    ->label('Remove Verification')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Remove Verification')
                    ->modalDescription('Remove verification status from this company?')
                    ->modalSubmitActionLabel('Yes, remove')
                    ->action(function ($record) {
                        $record->update(['is_verified' => false]);
                        Notification::make()
                            ->title('Verification Removed')
                            ->warning()
                            ->body('Verification removed from ' . $record->name)
                            ->send();
                    })
                    ->visible(fn ($record) => $record->is_verified)
                    ->iconButton()
                    ->tooltip('Remove verification'),

                Action::make('toggle_featured')
                    ->label(fn ($record) => $record->is_featured ? 'Unfeature' : 'Feature')
                    ->icon(fn ($record) => $record->is_featured ? 'heroicon-o-x-mark' : 'heroicon-o-star')
                    ->color(fn ($record) => $record->is_featured ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record) => ($record->is_featured ? 'Unfeature' : 'Feature') . ' Company')
                    ->modalDescription(fn ($record) => 'Are you sure you want to ' . ($record->is_featured ? 'remove this company from featured?' : 'feature this company?'))
                    ->action(function ($record) {
                        $newStatus = !$record->is_featured;
                        $record->update(['is_featured' => $newStatus]);
                        Notification::make()
                            ->title($newStatus ? 'Company Featured' : 'Company Unfeatured')
                            ->success()
                            ->body($record->name . ' has been ' . ($newStatus ? 'added to' : 'removed from') . ' featured companies.')
                            ->send();
                    })
                    ->iconButton()
                    ->tooltip(fn ($record) => $record->is_featured ? 'Remove from featured' : 'Add to featured'),

                Action::make('view_website')
                    ->label('Visit Website')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('info')
                    ->url(fn ($record) => $record->website)
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => !empty($record->website))
                    ->iconButton()
                    ->tooltip('Visit company website'),

                DeleteAction::make()
                    ->iconButton()
                    ->tooltip('Delete company'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Delete Selected')
                        ->icon('heroicon-o-trash')
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('verify')
                        ->label('Verify Selected')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Verify Selected Companies')
                        ->modalDescription('Mark all selected companies as verified?')
                        ->action(function ($records) {
                            $records->each->update(['is_verified' => true]);
                            Notification::make()
                                ->title('Companies Verified')
                                ->success()
                                ->body(count($records) . ' companies have been verified.')
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('unverify')
                        ->label('Remove Verification')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Remove Verification')
                        ->modalDescription('Remove verification from all selected companies?')
                        ->action(function ($records) {
                            $records->each->update(['is_verified' => false]);
                            Notification::make()
                                ->title('Verification Removed')
                                ->warning()
                                ->body('Verification removed from ' . count($records) . ' companies.')
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('feature')
                        ->label('Feature Selected')
                        ->icon('heroicon-o-star')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Feature Selected Companies')
                        ->modalDescription('Add all selected companies to featured list?')
                        ->action(function ($records) {
                            $records->each->update(['is_featured' => true]);
                            Notification::make()
                                ->title('Companies Featured')
                                ->success()
                                ->body(count($records) . ' companies have been featured.')
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('unfeature')
                        ->label('Unfeature Selected')
                        ->icon('heroicon-o-x-mark')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Unfeature Selected Companies')
                        ->modalDescription('Remove all selected companies from featured list?')
                        ->action(function ($records) {
                            $records->each->update(['is_featured' => false]);
                            Notification::make()
                                ->title('Companies Unfeatured')
                                ->warning()
                                ->body(count($records) . ' companies have been removed from featured.')
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('export')
                        ->label('Export to Excel')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->action(function ($records) {
                            // TODO: Implement Excel export using OpenSpout
                            Notification::make()
                                ->title('Export Started')
                                ->info()
                                ->body('Exporting ' . count($records) . ' companies...')
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s')
            ->deferLoading()
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->recordUrl(fn ($record) => route('filament.studai.resources.companies.view', $record));
    }
}
