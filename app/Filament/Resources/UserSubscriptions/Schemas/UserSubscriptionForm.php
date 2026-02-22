<?php

namespace App\Filament\Resources\UserSubscriptions\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserSubscriptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Subscription Information')
                    ->tabs([
                        // Tab 1: Basic Information
                        Tabs\Tab::make('Basic Info')
                            ->icon('heroicon-o-identification')
                            ->schema([
                                Section::make('Subscription Details')
                                    ->schema([
                                        Select::make('user_id')
                                            ->label('User')
                                            ->relationship('user', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->columnSpan(1),
                                        
                                        Select::make('subscription_plan_id')
                                            ->label('Subscription Plan')
                                            ->relationship('subscriptionPlan', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->helperText('The plan the user is subscribed to')
                                            ->columnSpan(1),
                                        
                                        Select::make('status')
                                            ->label('Status')
                                            ->options([
                                                'active' => 'Active',
                                                'canceled' => 'Canceled',
                                                'expired' => 'Expired',
                                                'trialing' => 'Trialing',
                                            ])
                                            ->default('active')
                                            ->required()
                                            ->helperText('Current subscription status')
                                            ->columnSpan(1),
                                        
                                        Select::make('payment_gateway')
                                            ->label('Payment Gateway')
                                            ->options([
                                                'razorpay' => 'Razorpay',
                                                'payu' => 'PayU',
                                            ])
                                            ->helperText('Gateway used for payment')
                                            ->columnSpan(1),
                                        
                                        TextInput::make('gateway_subscription_id')
                                            ->label('Gateway Subscription ID')
                                            ->maxLength(255)
                                            ->helperText('Subscription ID from payment gateway')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),
                                
                                Section::make('Subscription Period')
                                    ->schema([
                                        DateTimePicker::make('starts_at')
                                            ->label('Start Date')
                                            ->native(false)
                                            ->displayFormat('d M Y, H:i')
                                            ->helperText('When the subscription started')
                                            ->columnSpan(1),
                                        
                                        DateTimePicker::make('ends_at')
                                            ->label('End Date')
                                            ->native(false)
                                            ->displayFormat('d M Y, H:i')
                                            ->helperText('When the subscription ends')
                                            ->columnSpan(1),
                                        
                                        DateTimePicker::make('trial_ends_at')
                                            ->label('Trial End Date')
                                            ->native(false)
                                            ->displayFormat('d M Y, H:i')
                                            ->helperText('When the trial period ends (if applicable)')
                                            ->columnSpan(1),
                                        
                                        DateTimePicker::make('canceled_at')
                                            ->label('Canceled At')
                                            ->native(false)
                                            ->displayFormat('d M Y, H:i')
                                            ->helperText('When the subscription was canceled')
                                            ->columnSpan(1),
                                    ])
                                    ->columns(2),
                                
                                Section::make('Current Billing Period')
                                    ->schema([
                                        DateTimePicker::make('current_period_start')
                                            ->label('Period Start')
                                            ->native(false)
                                            ->displayFormat('d M Y, H:i')
                                            ->helperText('Start of current billing period')
                                            ->columnSpan(1),
                                        
                                        DateTimePicker::make('current_period_end')
                                            ->label('Period End')
                                            ->native(false)
                                            ->displayFormat('d M Y, H:i')
                                            ->helperText('End of current billing period')
                                            ->columnSpan(1),
                                    ])
                                    ->columns(2),
                            ]),
                        
                        // Tab 2: Usage Tracking
                        Tabs\Tab::make('Usage')
                            ->icon('heroicon-o-chart-bar')
                            ->schema([
                                Section::make('Monthly Usage')
                                    ->description('Track resource consumption for the current billing period')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('applications_used_this_month')
                                                    ->label('Applications Used')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->minValue(0)
                                                    ->helperText('Number of job applications this month')
                                                    ->suffixIcon('heroicon-o-document-text'),
                                                
                                                TextInput::make('ai_credits_used_this_month')
                                                    ->label('AI Credits Used')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->minValue(0)
                                                    ->helperText('AI credits consumed this month')
                                                    ->suffixIcon('heroicon-o-sparkles'),
                                            ]),
                                        
                                        Placeholder::make('usage_info')
                                            ->label('Usage Information')
                                            ->content(fn ($record): string => $record ? 
                                                "User has used {$record->applications_used_this_month} applications and {$record->ai_credits_used_this_month} AI credits in the current month. These counters reset at the start of each billing period." : 
                                                'Usage tracking will be available after the subscription is created.')
                                            ->columnSpanFull(),
                                    ]),
                                
                                Section::make('Plan Limits')
                                    ->description('View the limits from the selected subscription plan')
                                    ->schema([
                                        Placeholder::make('plan_details')
                                            ->label('Plan Details')
                                            ->content(fn ($record): string => $record && $record->subscriptionPlan ? 
                                                "Plan: {$record->subscriptionPlan->name}\n" .
                                                "Applications Limit: {$record->subscriptionPlan->applications_per_month}\n" .
                                                "AI Credits Limit: {$record->subscriptionPlan->ai_credits_per_month}\n" .
                                                "Price: ₹{$record->subscriptionPlan->price}" : 
                                                'Select a subscription plan to view limits')
                                            ->columnSpanFull(),
                                    ])
                                    ->visible(fn ($record) => $record !== null),
                            ]),
                        
                        // Tab 3: System Information
                        Tabs\Tab::make('System Info')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Section::make('Record Information')
                                    ->schema([
                                        Placeholder::make('id')
                                            ->label('Subscription ID')
                                            ->content(fn ($record): string => $record?->id ?? '-'),
                                        
                                        Placeholder::make('created_at')
                                            ->label('Created At')
                                            ->content(fn ($record): string => $record?->created_at?->format('d M Y, H:i') . ' (' . $record?->created_at?->diffForHumans() . ')' ?? '-'),
                                        
                                        Placeholder::make('updated_at')
                                            ->label('Last Updated')
                                            ->content(fn ($record): string => $record?->updated_at?->format('d M Y, H:i') . ' (' . $record?->updated_at?->diffForHumans() . ')' ?? '-'),
                                        
                                        Placeholder::make('duration')
                                            ->label('Subscription Duration')
                                            ->content(fn ($record): string => $record && $record->starts_at && $record->ends_at ? 
                                                $record->starts_at->diffInDays($record->ends_at) . ' days' : 
                                                '-')
                                            ->columnSpan(1),
                                        
                                        Placeholder::make('remaining_days')
                                            ->label('Days Remaining')
                                            ->content(fn ($record): string => $record && $record->ends_at ? 
                                                max(0, now()->diffInDays($record->ends_at, false)) . ' days' : 
                                                '-')
                                            ->columnSpan(1),
                                    ])
                                    ->columns(2)
                                    ->visible(fn ($record) => $record !== null),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
