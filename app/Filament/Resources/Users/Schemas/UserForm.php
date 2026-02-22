<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('User Information')
                    ->tabs([
                        Tabs\Tab::make('Basic Information')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Section::make('Personal Details')
                                    ->description('Basic user information and contact details')
                                    ->icon('heroicon-o-identification')
                                    ->collapsible()
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('Full Name')
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('John Doe')
                                            ->autocomplete('name')
                                            ->prefixIcon('heroicon-o-user'),

                                        TextInput::make('email')
                                            ->label('Email Address')
                                            ->email()
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(255)
                                            ->placeholder('john@example.com')
                                            ->autocomplete('email')
                                            ->prefixIcon('heroicon-o-envelope'),

                                        TextInput::make('phone')
                                            ->label('Phone Number')
                                            ->tel()
                                            ->maxLength(20)
                                            ->placeholder('+91 9876543210')
                                            ->prefixIcon('heroicon-o-phone'),

                                        Select::make('account_type')
                                            ->label('Account Type')
                                            ->options([
                                                'job_seeker' => 'Job Seeker',
                                                'employer' => 'Employer',
                                                'admin' => 'Admin',
                                            ])
                                            ->default('job_seeker')
                                            ->required()
                                            ->native(false)
                                            ->prefixIcon('heroicon-o-briefcase'),

                                        Select::make('timezone')
                                            ->label('Timezone')
                                            ->options([
                                                'Asia/Kolkata' => 'India (IST)',
                                                'UTC' => 'UTC',
                                                'America/New_York' => 'New York (EST)',
                                                'America/Los_Angeles' => 'Los Angeles (PST)',
                                                'Europe/London' => 'London (GMT)',
                                                'Asia/Singapore' => 'Singapore (SGT)',
                                                'Australia/Sydney' => 'Sydney (AEST)',
                                            ])
                                            ->default('Asia/Kolkata')
                                            ->required()
                                            ->searchable()
                                            ->native(false)
                                            ->prefixIcon('heroicon-o-globe-alt'),

                                        FileUpload::make('avatar')
                                            ->label('Profile Picture')
                                            ->image()
                                            ->avatar()
                                            ->imageEditor()
                                            ->circleCropper()
                                            ->directory('avatars')
                                            ->maxSize(2048)
                                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                            ->columnSpan(2),
                                    ]),

                                Section::make('Account Status')
                                    ->description('Manage account access and verification status')
                                    ->icon('heroicon-o-shield-check')
                                    ->collapsible()
                                    ->columns(2)
                                    ->schema([
                                        Toggle::make('is_active')
                                            ->label('Account Active')
                                            ->helperText('When disabled, user cannot log in')
                                            ->default(true)
                                            ->inline(false)
                                            ->onIcon('heroicon-o-check-circle')
                                            ->offIcon('heroicon-o-x-circle'),

                                        DateTimePicker::make('email_verified_at')
                                            ->label('Email Verified At')
                                            ->displayFormat('M d, Y H:i')
                                            ->timezone('Asia/Kolkata')
                                            ->native(false)
                                            ->prefixIcon('heroicon-o-check-badge'),

                                        DateTimePicker::make('last_login_at')
                                            ->label('Last Login')
                                            ->displayFormat('M d, Y H:i')
                                            ->timezone('Asia/Kolkata')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->prefixIcon('heroicon-o-clock'),
                                    ]),
                            ]),

                        Tabs\Tab::make('Security')
                            ->icon('heroicon-o-lock-closed')
                            ->schema([
                                Section::make('Password')
                                    ->description('Set or change user password')
                                    ->icon('heroicon-o-key')
                                    ->collapsible()
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('password')
                                            ->label('Password')
                                            ->password()
                                            ->revealable()
                                            ->required(fn (string $context): bool => $context === 'create')
                                            ->dehydrated(fn ($state) => filled($state))
                                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                                            ->minLength(8)
                                            ->maxLength(255)
                                            ->placeholder('Enter password')
                                            ->helperText('Minimum 8 characters')
                                            ->prefixIcon('heroicon-o-lock-closed'),

                                        TextInput::make('password_confirmation')
                                            ->label('Confirm Password')
                                            ->password()
                                            ->revealable()
                                            ->required(fn (string $context): bool => $context === 'create')
                                            ->dehydrated(false)
                                            ->same('password')
                                            ->minLength(8)
                                            ->maxLength(255)
                                            ->placeholder('Confirm password')
                                            ->prefixIcon('heroicon-o-lock-closed'),
                                    ]),

                                Section::make('Two-Factor Authentication')
                                    ->description('Manage 2FA settings')
                                    ->icon('heroicon-o-device-phone-mobile')
                                    ->collapsible()
                                    ->collapsed()
                                    ->schema([
                                        Toggle::make('two_factor_enabled')
                                            ->label('Enable Two-Factor Authentication')
                                            ->helperText('Require 2FA code on login')
                                            ->default(false)
                                            ->inline(false)
                                            ->onIcon('heroicon-o-shield-check')
                                            ->offIcon('heroicon-o-shield-exclamation'),
                                    ]),
                            ]),

                        Tabs\Tab::make('Preferences')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Section::make('User Preferences')
                                    ->description('Job seeker preferences and settings')
                                    ->icon('heroicon-o-adjustments-horizontal')
                                    ->collapsible()
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Select::make('preferences.job_alert_frequency')
                                                    ->label('Job Alert Frequency')
                                                    ->options([
                                                        'instant' => 'Instant',
                                                        'daily' => 'Daily Digest',
                                                        'weekly' => 'Weekly Digest',
                                                        'never' => 'Never',
                                                    ])
                                                    ->default('daily')
                                                    ->native(false)
                                                    ->prefixIcon('heroicon-o-bell'),

                                                Select::make('preferences.notification_method')
                                                    ->label('Notification Method')
                                                    ->options([
                                                        'email' => 'Email Only',
                                                        'sms' => 'SMS Only',
                                                        'both' => 'Email & SMS',
                                                        'none' => 'No Notifications',
                                                    ])
                                                    ->default('email')
                                                    ->native(false)
                                                    ->prefixIcon('heroicon-o-chat-bubble-left-right'),

                                                Toggle::make('preferences.public_profile')
                                                    ->label('Public Profile')
                                                    ->helperText('Make profile visible to employers')
                                                    ->default(true)
                                                    ->inline(false),

                                                Toggle::make('preferences.open_to_opportunities')
                                                    ->label('Open to Opportunities')
                                                    ->helperText('Show "Open to Work" badge')
                                                    ->default(false)
                                                    ->inline(false),
                                            ]),
                                    ]),
                            ]),

                        Tabs\Tab::make('Metadata')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Section::make('System Information')
                                    ->description('Read-only system metadata')
                                    ->icon('heroicon-o-server')
                                    ->collapsible()
                                    ->columns(3)
                                    ->schema([
                                        TextInput::make('id')
                                            ->label('User ID')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->visibleOn('edit'),

                                        DateTimePicker::make('created_at')
                                            ->label('Created At')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->displayFormat('M d, Y H:i')
                                            ->visibleOn('edit'),

                                        DateTimePicker::make('updated_at')
                                            ->label('Updated At')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->displayFormat('M d, Y H:i')
                                            ->visibleOn('edit'),

                                        DateTimePicker::make('deleted_at')
                                            ->label('Deleted At')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->displayFormat('M d, Y H:i')
                                            ->visible(fn ($record) => $record?->trashed()),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
