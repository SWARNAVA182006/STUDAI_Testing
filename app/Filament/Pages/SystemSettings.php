<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\KeyValue;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;

class SystemSettings extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static \UnitEnum|string|null $navigationGroup = 'System & Tools';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.system-settings';

    protected static ?string $title = 'System Settings';

    protected static ?string $navigationLabel = 'Settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->loadSettings();
    }

    protected function loadSettings(): void
    {
        $this->data = [
            // General Settings
            'site_name' => config('app.name', 'StudAI Career'),
            'site_tagline' => 'Find Your Perfect Career Path with AI',
            'support_email' => config('mail.from.address', 'support@studai.com'),
            'contact_phone' => '+91 1234567890',
            'timezone' => config('app.timezone', 'Asia/Kolkata'),
            'locale' => config('app.locale', 'en'),
            
            // Feature Flags
            'enable_registrations' => true,
            'enable_ai_features' => true,
            'enable_job_posting' => true,
            'enable_applications' => true,
            'maintenance_mode' => false,
            
            // AI Settings
            'openai_api_key' => config('services.openai.api_key', ''),
            'openai_model' => config('services.openai.model', 'gpt-4'),
            'ai_max_tokens' => 2000,
            'ai_temperature' => 0.7,
            
            // Payment Gateway
            'razorpay_key_id' => config('services.razorpay.key_id', ''),
            'razorpay_key_secret' => config('services.razorpay.key_secret', ''),
            'payu_merchant_key' => config('services.payu.merchant_key', ''),
            'payu_merchant_salt' => config('services.payu.merchant_salt', ''),
            'payment_test_mode' => true,
            
            // Limits & Quotas
            'free_applications_per_month' => 5,
            'free_ai_credits_per_month' => 100,
            'max_jobs_per_company' => 50,
            'max_file_upload_size' => 5, // MB
            
            // Email Settings
            'mail_driver' => config('mail.default', 'smtp'),
            'smtp_host' => config('mail.mailers.smtp.host', ''),
            'smtp_port' => config('mail.mailers.smtp.port', 587),
            'smtp_username' => config('mail.mailers.smtp.username', ''),
            'smtp_password' => config('mail.mailers.smtp.password', ''),
            
            // Cache & Performance
            'cache_driver' => config('cache.default', 'redis'),
            'queue_driver' => config('queue.default', 'redis'),
            'session_lifetime' => config('session.lifetime', 120),
        ];
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Tabs::make('Settings')
                    ->tabs([
                        Tabs\Tab::make('General')
                            ->icon('heroicon-o-globe-alt')
                            ->schema([
                                Section::make('Site Information')
                                    ->schema([
                                        TextInput::make('site_name')
                                            ->label('Site Name')
                                            ->required()
                                            ->maxLength(100),
                                        
                                        TextInput::make('site_tagline')
                                            ->label('Tagline')
                                            ->maxLength(200),
                                        
                                        TextInput::make('support_email')
                                            ->label('Support Email')
                                            ->email()
                                            ->required(),
                                        
                                        TextInput::make('contact_phone')
                                            ->label('Contact Phone')
                                            ->tel(),
                                        
                                        Select::make('timezone')
                                            ->label('Timezone')
                                            ->options([
                                                'Asia/Kolkata' => 'India (IST)',
                                                'UTC' => 'UTC',
                                                'America/New_York' => 'Eastern Time',
                                                'America/Los_Angeles' => 'Pacific Time',
                                                'Europe/London' => 'London',
                                            ])
                                            ->required(),
                                        
                                        Select::make('locale')
                                            ->label('Language')
                                            ->options([
                                                'en' => 'English',
                                                'hi' => 'Hindi',
                                            ])
                                            ->required(),
                                    ])
                                    ->columns(2),
                            ]),
                        
                        Tabs\Tab::make('Features')
                            ->icon('heroicon-o-puzzle-piece')
                            ->schema([
                                Section::make('Feature Flags')
                                    ->description('Enable or disable platform features')
                                    ->schema([
                                        Toggle::make('enable_registrations')
                                            ->label('Enable User Registrations')
                                            ->helperText('Allow new users to register on the platform')
                                            ->inline(false),
                                        
                                        Toggle::make('enable_ai_features')
                                            ->label('Enable AI Features')
                                            ->helperText('Career coach, resume analysis, job matching')
                                            ->inline(false),
                                        
                                        Toggle::make('enable_job_posting')
                                            ->label('Enable Job Posting')
                                            ->helperText('Allow employers to post jobs')
                                            ->inline(false),
                                        
                                        Toggle::make('enable_applications')
                                            ->label('Enable Job Applications')
                                            ->helperText('Allow job seekers to apply for jobs')
                                            ->inline(false),
                                        
                                        Toggle::make('maintenance_mode')
                                            ->label('Maintenance Mode')
                                            ->helperText('Put the site in maintenance mode')
                                            ->inline(false),
                                    ])
                                    ->columns(1),
                            ]),
                        
                        Tabs\Tab::make('AI Configuration')
                            ->icon('heroicon-o-sparkles')
                            ->schema([
                                Section::make('OpenAI API Settings')
                                    ->schema([
                                        TextInput::make('openai_api_key')
                                            ->label('OpenAI API Key')
                                            ->password()
                                            ->revealable()
                                            ->helperText('Your OpenAI API key for AI features'),
                                        
                                        Select::make('openai_model')
                                            ->label('Model')
                                            ->options([
                                                'gpt-4' => 'GPT-4 (Recommended)',
                                                'gpt-4-turbo' => 'GPT-4 Turbo',
                                                'gpt-3.5-turbo' => 'GPT-3.5 Turbo (Cheaper)',
                                            ])
                                            ->required(),
                                        
                                        TextInput::make('ai_max_tokens')
                                            ->label('Max Tokens')
                                            ->numeric()
                                            ->minValue(100)
                                            ->maxValue(4000)
                                            ->helperText('Maximum tokens per AI request'),
                                        
                                        TextInput::make('ai_temperature')
                                            ->label('Temperature')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(2)
                                            ->step(0.1)
                                            ->helperText('Creativity level (0-2)'),
                                    ])
                                    ->columns(2),
                            ]),
                        
                        Tabs\Tab::make('Payment Gateways')
                            ->icon('heroicon-o-credit-card')
                            ->schema([
                                Section::make('Razorpay Configuration')
                                    ->schema([
                                        TextInput::make('razorpay_key_id')
                                            ->label('Key ID')
                                            ->helperText('Razorpay Key ID'),
                                        
                                        TextInput::make('razorpay_key_secret')
                                            ->label('Key Secret')
                                            ->password()
                                            ->revealable()
                                            ->helperText('Razorpay Key Secret'),
                                    ])
                                    ->columns(2),
                                
                                Section::make('PayU Configuration')
                                    ->schema([
                                        TextInput::make('payu_merchant_key')
                                            ->label('Merchant Key')
                                            ->helperText('PayU Merchant Key'),
                                        
                                        TextInput::make('payu_merchant_salt')
                                            ->label('Merchant Salt')
                                            ->password()
                                            ->revealable()
                                            ->helperText('PayU Merchant Salt'),
                                    ])
                                    ->columns(2),
                                
                                Section::make('Payment Settings')
                                    ->schema([
                                        Toggle::make('payment_test_mode')
                                            ->label('Test Mode')
                                            ->helperText('Use sandbox/test mode for payments')
                                            ->inline(false),
                                    ]),
                            ]),
                        
                        Tabs\Tab::make('Limits & Quotas')
                            ->icon('heroicon-o-chart-bar')
                            ->schema([
                                Section::make('Platform Limits')
                                    ->schema([
                                        TextInput::make('free_applications_per_month')
                                            ->label('Free Applications Per Month')
                                            ->numeric()
                                            ->minValue(0)
                                            ->helperText('Number of free applications for non-paying users'),
                                        
                                        TextInput::make('free_ai_credits_per_month')
                                            ->label('Free AI Credits Per Month')
                                            ->numeric()
                                            ->minValue(0)
                                            ->helperText('AI credits for free tier users'),
                                        
                                        TextInput::make('max_jobs_per_company')
                                            ->label('Max Jobs Per Company')
                                            ->numeric()
                                            ->minValue(1)
                                            ->helperText('Maximum active jobs per company'),
                                        
                                        TextInput::make('max_file_upload_size')
                                            ->label('Max File Upload Size (MB)')
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(20)
                                            ->helperText('Maximum file size for uploads'),
                                    ])
                                    ->columns(2),
                            ]),
                        
                        Tabs\Tab::make('Email')
                            ->icon('heroicon-o-envelope')
                            ->schema([
                                Section::make('SMTP Configuration')
                                    ->schema([
                                        Select::make('mail_driver')
                                            ->label('Mail Driver')
                                            ->options([
                                                'smtp' => 'SMTP',
                                                'sendmail' => 'Sendmail',
                                                'mailgun' => 'Mailgun',
                                                'ses' => 'Amazon SES',
                                            ])
                                            ->required(),
                                        
                                        TextInput::make('smtp_host')
                                            ->label('SMTP Host')
                                            ->helperText('e.g., smtp.gmail.com'),
                                        
                                        TextInput::make('smtp_port')
                                            ->label('SMTP Port')
                                            ->numeric()
                                            ->helperText('Usually 587 for TLS or 465 for SSL'),
                                        
                                        TextInput::make('smtp_username')
                                            ->label('SMTP Username')
                                            ->helperText('Your email address'),
                                        
                                        TextInput::make('smtp_password')
                                            ->label('SMTP Password')
                                            ->password()
                                            ->revealable()
                                            ->helperText('Your email password or app password'),
                                    ])
                                    ->columns(2),
                            ]),
                        
                        Tabs\Tab::make('Cache & Performance')
                            ->icon('heroicon-o-bolt')
                            ->schema([
                                Section::make('Performance Settings')
                                    ->schema([
                                        Select::make('cache_driver')
                                            ->label('Cache Driver')
                                            ->options([
                                                'redis' => 'Redis (Recommended)',
                                                'file' => 'File',
                                                'database' => 'Database',
                                                'memcached' => 'Memcached',
                                            ])
                                            ->required(),
                                        
                                        Select::make('queue_driver')
                                            ->label('Queue Driver')
                                            ->options([
                                                'redis' => 'Redis (Recommended)',
                                                'database' => 'Database',
                                                'sync' => 'Sync (Development Only)',
                                            ])
                                            ->required(),
                                        
                                        TextInput::make('session_lifetime')
                                            ->label('Session Lifetime (minutes)')
                                            ->numeric()
                                            ->minValue(5)
                                            ->maxValue(1440)
                                            ->helperText('How long users stay logged in'),
                                    ])
                                    ->columns(2),
                            ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();
            
            // Save to cache (in production, save to database settings table)
            Cache::put('system_settings', $data, now()->addDays(30));
            
            // Update .env file for critical settings (optional)
            // $this->updateEnvFile($data);
            
            Notification::make()
                ->title('Settings Saved')
                ->success()
                ->body('System settings have been updated successfully.')
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Save Failed')
                ->danger()
                ->body('Error: ' . $e->getMessage())
                ->send();
        }
    }

    public function clearAllCaches(): void
    {
        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            
            Notification::make()
                ->title('Caches Cleared')
                ->success()
                ->body('All application caches have been cleared.')
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Clear Failed')
                ->danger()
                ->body('Error: ' . $e->getMessage())
                ->send();
        }
    }

    public function optimizeApplication(): void
    {
        try {
            Artisan::call('config:cache');
            Artisan::call('route:cache');
            Artisan::call('view:cache');
            
            Notification::make()
                ->title('Application Optimized')
                ->success()
                ->body('Configuration, routes, and views have been cached for better performance.')
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Optimization Failed')
                ->danger()
                ->body('Error: ' . $e->getMessage())
                ->send();
        }
    }

    protected function updateEnvFile(array $data): void
    {
        // This is a simplified version - in production, use a proper .env updater package
        $envFile = base_path('.env');
        $envContent = file_get_contents($envFile);
        
        $updates = [
            'APP_NAME' => $data['site_name'] ?? config('app.name'),
            'APP_TIMEZONE' => $data['timezone'] ?? config('app.timezone'),
            'MAIL_HOST' => $data['smtp_host'] ?? '',
            'MAIL_PORT' => $data['smtp_port'] ?? '',
        ];
        
        foreach ($updates as $key => $value) {
            $pattern = "/^{$key}=.*/m";
            $replacement = "{$key}={$value}";
            $envContent = preg_replace($pattern, $replacement, $envContent);
        }
        
        file_put_contents($envFile, $envContent);
    }
}
