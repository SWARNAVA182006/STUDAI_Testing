<?php

namespace App\Filament\Resources\UserSubscriptions\Tables;

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

class UserSubscriptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record): string => $record->user?->email ?? '')
                    ->copyable()
                    ->copyMessage('User name copied'),
                
                TextColumn::make('subscriptionPlan.name')
                    ->label('Plan')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->description(fn ($record): string => $record->subscriptionPlan ? 
                        "₹{$record->subscriptionPlan->price} / {$record->subscriptionPlan->billing_cycle}" : 
                        'No Plan'),
                
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'trialing' => 'info',
                        'canceled' => 'warning',
                        'expired' => 'danger',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'active' => 'heroicon-o-check-circle',
                        'trialing' => 'heroicon-o-clock',
                        'canceled' => 'heroicon-o-x-circle',
                        'expired' => 'heroicon-o-exclamation-triangle',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable()
                    ->searchable(),
                
                TextColumn::make('payment_gateway')
                    ->label('Gateway')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn (?string $state): string => $state ? ucfirst($state) : 'N/A')
                    ->sortable()
                    ->toggleable(),
                
                TextColumn::make('gateway_subscription_id')
                    ->label('Gateway ID')
                    ->copyable()
                    ->copyMessage('Gateway ID copied')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(20),
                
                TextColumn::make('starts_at')
                    ->label('Start Date')
                    ->date('d M Y')
                    ->description(fn ($record): ?string => $record->starts_at ? $record->starts_at->diffForHumans() : null)
                    ->sortable()
                    ->toggleable(),
                
                TextColumn::make('ends_at')
                    ->label('End Date')
                    ->date('d M Y')
                    ->description(fn ($record): ?string => $record->ends_at ? $record->ends_at->diffForHumans() : null)
                    ->color(fn ($record): string => $record->ends_at && $record->ends_at->isPast() ? 'danger' : 
                        ($record->ends_at && $record->ends_at->diffInDays() <= 7 ? 'warning' : 'gray'))
                    ->icon(fn ($record): ?string => $record->ends_at && $record->ends_at->diffInDays() <= 7 ? 'heroicon-o-exclamation-triangle' : null)
                    ->sortable(),
                
                TextColumn::make('trial_ends_at')
                    ->label('Trial End')
                    ->date('d M Y')
                    ->description(fn ($record): ?string => $record->trial_ends_at ? $record->trial_ends_at->diffForHumans() : null)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('current_period_start')
                    ->label('Period Start')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('current_period_end')
                    ->label('Period End')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('applications_used_this_month')
                    ->label('Apps Used')
                    ->numeric()
                    ->icon('heroicon-o-document-text')
                    ->description(fn ($record): string => $record->subscriptionPlan ? 
                        "Limit: {$record->subscriptionPlan->applications_per_month}" : 
                        'No Limit')
                    ->color(function ($record): string {
                        if (!$record->subscriptionPlan) return 'gray';
                        $percentage = ($record->applications_used_this_month / $record->subscriptionPlan->applications_per_month) * 100;
                        if ($percentage >= 100) return 'danger';
                        if ($percentage >= 80) return 'warning';
                        return 'success';
                    })
                    ->sortable(),
                
                TextColumn::make('ai_credits_used_this_month')
                    ->label('AI Credits')
                    ->numeric()
                    ->icon('heroicon-o-sparkles')
                    ->description(fn ($record): string => $record->subscriptionPlan ? 
                        "Limit: {$record->subscriptionPlan->ai_credits_per_month}" : 
                        'No Limit')
                    ->color(function ($record): string {
                        if (!$record->subscriptionPlan) return 'gray';
                        $percentage = ($record->ai_credits_used_this_month / $record->subscriptionPlan->ai_credits_per_month) * 100;
                        if ($percentage >= 100) return 'danger';
                        if ($percentage >= 80) return 'warning';
                        return 'success';
                    })
                    ->sortable(),
                
                TextColumn::make('canceled_at')
                    ->label('Canceled')
                    ->dateTime('d M Y, H:i')
                    ->description(fn ($record): ?string => $record->canceled_at ? $record->canceled_at->diffForHumans() : null)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('created_at')
                    ->label('Created')
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
                        'active' => 'Active',
                        'trialing' => 'Trialing',
                        'canceled' => 'Canceled',
                        'expired' => 'Expired',
                    ])
                    ->multiple()
                    ->indicator('Status'),
                
                SelectFilter::make('payment_gateway')
                    ->label('Payment Gateway')
                    ->options([
                        'razorpay' => 'Razorpay',
                        'payu' => 'PayU',
                    ])
                    ->multiple()
                    ->indicator('Gateway'),
                
                SelectFilter::make('subscription_plan_id')
                    ->label('Subscription Plan')
                    ->relationship('subscriptionPlan', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple()
                    ->indicator('Plan'),
                
                TernaryFilter::make('expiring_soon')
                    ->label('Expiring Soon')
                    ->queries(
                        true: fn (Builder $query) => $query->where('status', 'active')
                            ->where('ends_at', '<=', now()->addDays(7))
                            ->where('ends_at', '>=', now()),
                        false: fn (Builder $query) => $query->where(function ($q) {
                            $q->where('ends_at', '>', now()->addDays(7))
                              ->orWhereNull('ends_at');
                        }),
                    )
                    ->indicator('Expiring'),
                
                TernaryFilter::make('over_usage')
                    ->label('Over Usage Limit')
                    ->queries(
                        true: fn (Builder $query) => $query->whereHas('subscriptionPlan', function ($q) {
                            $q->whereRaw('applications_used_this_month >= subscription_plans.applications_per_month')
                              ->orWhereRaw('ai_credits_used_this_month >= subscription_plans.ai_credits_per_month');
                        }),
                    )
                    ->indicator('Over Limit'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->iconButton()
                    ->tooltip('View Subscription'),
                
                EditAction::make()
                    ->iconButton()
                    ->tooltip('Edit Subscription'),
                
                Action::make('renew')
                    ->label('Renew')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Renew Subscription')
                    ->modalDescription('Extend the subscription by one billing cycle.')
                    ->action(function ($record) {
                        if ($record->subscriptionPlan) {
                            $billingCycle = $record->subscriptionPlan->billing_cycle;
                            $newEndDate = ($record->ends_at ?? now())->addMonths($billingCycle === 'monthly' ? 1 : 12);
                            
                            $record->update([
                                'ends_at' => $newEndDate,
                                'current_period_start' => $record->ends_at ?? now(),
                                'current_period_end' => $newEndDate,
                                'status' => 'active',
                            ]);
                            
                            Notification::make()
                                ->title('Subscription Renewed')
                                ->body("New end date: {$newEndDate->format('d M Y')}")
                                ->success()
                                ->send();
                        }
                    })
                    ->visible(fn ($record): bool => in_array($record->status, ['canceled', 'expired'])),
                
                Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Cancel Subscription')
                    ->modalDescription('The subscription will remain active until the current period ends.')
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'canceled',
                            'canceled_at' => now(),
                        ]);
                        
                        Notification::make()
                            ->title('Subscription Canceled')
                            ->body("Will remain active until {$record->ends_at->format('d M Y')}")
                            ->warning()
                            ->send();
                    })
                    ->visible(fn ($record): bool => $record->status === 'active'),
                
                Action::make('reactivate')
                    ->label('Reactivate')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Reactivate Subscription')
                    ->modalDescription('This will reactivate the canceled subscription.')
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'active',
                            'canceled_at' => null,
                        ]);
                        
                        Notification::make()
                            ->title('Subscription Reactivated')
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record): bool => $record->status === 'canceled' && $record->ends_at && $record->ends_at->isFuture()),
                
                Action::make('reset_usage')
                    ->label('Reset Usage')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Reset Monthly Usage')
                    ->modalDescription('This will reset applications and AI credits usage counters to zero.')
                    ->action(function ($record) {
                        $record->update([
                            'applications_used_this_month' => 0,
                            'ai_credits_used_this_month' => 0,
                        ]);
                        
                        Notification::make()
                            ->title('Usage Reset')
                            ->success()
                            ->send();
                    }),
                
                Action::make('view_user')
                    ->label('View User')
                    ->icon('heroicon-o-user')
                    ->color('info')
                    ->url(fn ($record): string => route('filament.admin.resources.users.view', ['record' => $record->user_id]))
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    
                    BulkAction::make('cancel')
                        ->label('Cancel Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $count = $records->count();
                            $records->each->update([
                                'status' => 'canceled',
                                'canceled_at' => now(),
                            ]);
                            
                            Notification::make()
                                ->title("{$count} subscription(s) canceled")
                                ->warning()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    
                    BulkAction::make('reactivate')
                        ->label('Reactivate Selected')
                        ->icon('heroicon-o-arrow-path')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $count = $records->count();
                            $records->each->update([
                                'status' => 'active',
                                'canceled_at' => null,
                            ]);
                            
                            Notification::make()
                                ->title("{$count} subscription(s) reactivated")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    
                    BulkAction::make('reset_usage')
                        ->label('Reset Usage')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $count = $records->count();
                            $records->each->update([
                                'applications_used_this_month' => 0,
                                'ai_credits_used_this_month' => 0,
                            ]);
                            
                            Notification::make()
                                ->title("Usage reset for {$count} subscription(s)")
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
            ->recordUrl(fn ($record): string => route('filament.admin.resources.user-subscriptions.view', ['record' => $record]));
    }
}
