<?php

namespace App\Filament\Resources\Companies\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Schemas\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Company Information')
                    ->tabs([
                        Tabs\Tab::make('Basic Information')
                            ->icon('heroicon-o-building-office')
                            ->schema([
                                Section::make('Company Details')
                                    ->description('Essential company information and branding')
                                    ->icon('heroicon-o-identification')
                                    ->collapsible()
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('Company Name')
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('Acme Corporation')
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                if (! empty($state)) {
                                                    $set('slug', Str::slug($state));
                                                }
                                            })
                                            ->prefixIcon('heroicon-o-building-office-2'),

                                        TextInput::make('slug')
                                            ->label('URL Slug')
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(255)
                                            ->placeholder('acme-corporation')
                                            ->helperText('Auto-generated from company name, can be customized')
                                            ->alphaDash()
                                            ->prefixIcon('heroicon-o-link'),

                                        TextInput::make('website')
                                            ->label('Company Website')
                                            ->url()
                                            ->maxLength(255)
                                            ->placeholder('https://www.example.com')
                                            ->prefixIcon('heroicon-o-globe-alt')
                                            ->suffixIcon('heroicon-o-arrow-top-right-on-square'),

                                        Select::make('industry')
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
                                            ->searchable()
                                            ->native(false)
                                            ->prefixIcon('heroicon-o-briefcase'),

                                        Select::make('company_size')
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
                                            ->native(false)
                                            ->prefixIcon('heroicon-o-users'),

                                        TextInput::make('founded_year')
                                            ->label('Founded Year')
                                            ->numeric()
                                            ->minValue(1800)
                                            ->maxValue(date('Y'))
                                            ->placeholder(date('Y'))
                                            ->prefixIcon('heroicon-o-calendar'),

                                        FileUpload::make('logo')
                                            ->label('Company Logo')
                                            ->image()
                                            ->imageEditor()
                                            ->circleCropper()
                                            ->directory('company-logos')
                                            ->maxSize(2048)
                                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'])
                                            ->helperText('Recommended: Square image, minimum 200x200px')
                                            ->columnSpan(2),
                                    ]),

                                Section::make('Description')
                                    ->description('Company overview and culture')
                                    ->icon('heroicon-o-document-text')
                                    ->collapsible()
                                    ->schema([
                                        RichEditor::make('description')
                                            ->label('Company Description')
                                            ->toolbarButtons([
                                                'bold',
                                                'italic',
                                                'underline',
                                                'strike',
                                                'link',
                                                'bulletList',
                                                'orderedList',
                                                'h2',
                                                'h3',
                                                'blockquote',
                                            ])
                                            ->placeholder('Tell job seekers about your company, culture, mission, and values...')
                                            ->maxLength(5000)
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tabs\Tab::make('Locations & Contact')
                            ->icon('heroicon-o-map-pin')
                            ->schema([
                                Section::make('Headquarters')
                                    ->description('Primary company location')
                                    ->icon('heroicon-o-building-office')
                                    ->collapsible()
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('headquarters')
                                            ->label('Headquarters Location')
                                            ->maxLength(255)
                                            ->placeholder('San Francisco, CA, USA')
                                            ->prefixIcon('heroicon-o-map-pin')
                                            ->columnSpan(2),
                                    ]),

                                Section::make('Office Locations')
                                    ->description('Additional office locations worldwide')
                                    ->icon('heroicon-o-globe-alt')
                                    ->collapsible()
                                    ->schema([
                                        Repeater::make('locations')
                                            ->label('Office Locations')
                                            ->schema([
                                                Grid::make(2)
                                                    ->schema([
                                                        TextInput::make('city')
                                                            ->label('City')
                                                            ->required()
                                                            ->placeholder('New York'),

                                                        TextInput::make('country')
                                                            ->label('Country')
                                                            ->required()
                                                            ->placeholder('USA'),

                                                        TextInput::make('address')
                                                            ->label('Full Address')
                                                            ->placeholder('123 Main St, Suite 100')
                                                            ->columnSpan(2),

                                                        Toggle::make('is_remote')
                                                            ->label('Remote Office')
                                                            ->helperText('Check if this is a remote/virtual office'),

                                                        Toggle::make('is_hiring')
                                                            ->label('Currently Hiring')
                                                            ->helperText('Check if actively recruiting at this location')
                                                            ->default(false),
                                                    ]),
                                            ])
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => $state['city'] ?? null)
                                            ->addActionLabel('Add Office Location')
                                            ->reorderable()
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tabs\Tab::make('Benefits & Perks')
                            ->icon('heroicon-o-gift')
                            ->schema([
                                Section::make('Employee Benefits')
                                    ->description('Showcase what makes your company a great place to work')
                                    ->icon('heroicon-o-sparkles')
                                    ->collapsible()
                                    ->schema([
                                        Repeater::make('benefits')
                                            ->label('Company Benefits')
                                            ->schema([
                                                Select::make('category')
                                                    ->label('Category')
                                                    ->options([
                                                        'health' => '💊 Health & Wellness',
                                                        'financial' => '💰 Financial Benefits',
                                                        'work_life' => '⚖️ Work-Life Balance',
                                                        'professional' => '📚 Professional Development',
                                                        'perks' => '🎁 Perks & Amenities',
                                                        'insurance' => '🛡️ Insurance',
                                                        'vacation' => '🏖️ Time Off',
                                                        'other' => '✨ Other',
                                                    ])
                                                    ->required()
                                                    ->native(false),

                                                TextInput::make('title')
                                                    ->label('Benefit Title')
                                                    ->required()
                                                    ->placeholder('Health Insurance'),

                                                Textarea::make('description')
                                                    ->label('Description')
                                                    ->placeholder('Comprehensive health coverage for you and your family')
                                                    ->rows(2),
                                            ])
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                                            ->addActionLabel('Add Benefit')
                                            ->reorderable()
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tabs\Tab::make('Tech Stack')
                            ->icon('heroicon-o-code-bracket')
                            ->schema([
                                Section::make('Technology Stack')
                                    ->description('Technologies and tools your team uses')
                                    ->icon('heroicon-o-cpu-chip')
                                    ->collapsible()
                                    ->schema([
                                        TagsInput::make('tech_stack')
                                            ->label('Technologies & Tools')
                                            ->placeholder('Add technology (e.g., React, Python, AWS)')
                                            ->suggestions([
                                                'JavaScript', 'TypeScript', 'Python', 'Java', 'PHP', 'Ruby', 'Go', 'Rust',
                                                'React', 'Vue.js', 'Angular', 'Next.js', 'Laravel', 'Django', 'Spring Boot',
                                                'AWS', 'Azure', 'Google Cloud', 'Docker', 'Kubernetes', 'Jenkins',
                                                'PostgreSQL', 'MySQL', 'MongoDB', 'Redis', 'Elasticsearch',
                                                'Git', 'Jira', 'Slack', 'Figma', 'VS Code',
                                            ])
                                            ->helperText('Press Enter or comma to add each technology')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tabs\Tab::make('Verification & Settings')
                            ->icon('heroicon-o-shield-check')
                            ->schema([
                                Section::make('Company Status')
                                    ->description('Verification and visibility settings')
                                    ->icon('heroicon-o-cog-6-tooth')
                                    ->collapsible()
                                    ->columns(2)
                                    ->schema([
                                        Toggle::make('is_verified')
                                            ->label('Verified Company')
                                            ->helperText('Mark as verified after document verification')
                                            ->inline(false)
                                            ->onIcon('heroicon-o-check-badge')
                                            ->offIcon('heroicon-o-x-mark'),

                                        Toggle::make('is_featured')
                                            ->label('Featured Company')
                                            ->helperText('Show in featured companies section')
                                            ->inline(false)
                                            ->onIcon('heroicon-o-star')
                                            ->offIcon('heroicon-o-star'),

                                        TextInput::make('culture_rating')
                                            ->label('Culture Rating')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(5)
                                            ->step(0.1)
                                            ->suffix('/ 5.0')
                                            ->placeholder('4.5')
                                            ->helperText('Average employee culture rating')
                                            ->prefixIcon('heroicon-o-star'),
                                    ]),

                                Section::make('System Information')
                                    ->description('Read-only metadata')
                                    ->icon('heroicon-o-information-circle')
                                    ->collapsible()
                                    ->collapsed()
                                    ->columns(3)
                                    ->schema([
                                        TextInput::make('id')
                                            ->label('Company ID')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->visibleOn('edit'),

                                        DatePicker::make('created_at')
                                            ->label('Created At')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->displayFormat('M d, Y')
                                            ->visibleOn('edit')
                                            ->native(false),

                                        DatePicker::make('updated_at')
                                            ->label('Updated At')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->displayFormat('M d, Y')
                                            ->visibleOn('edit')
                                            ->native(false),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
