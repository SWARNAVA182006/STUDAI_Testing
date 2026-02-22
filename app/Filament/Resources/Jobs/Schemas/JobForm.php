<?php

namespace App\Filament\Resources\Jobs\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Schemas\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class JobForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Job Information')
                    ->tabs([
                        // Tab 1: Basic Job Details
                        Tabs\Tab::make('Basic Details')
                            ->icon('heroicon-o-briefcase')
                            ->schema([
                                Section::make('Job Information')
                                    ->schema([
                                        Select::make('company_id')
                                            ->label('Company')
                                            ->relationship('company', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->columnSpan(1),
                                        
                                        Hidden::make('posted_by')
                                            ->default(fn() => auth()->id()),
                                        
                                        TextInput::make('title')
                                            ->label('Job Title')
                                            ->required()
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (Get $get, $set, ?string $state) {
                                                if (!$get('slug') || $get('slug') === Str::slug($get('title'))) {
                                                    $set('slug', Str::slug($state));
                                                }
                                            })
                                            ->columnSpan(1),
                                        
                                        TextInput::make('slug')
                                            ->label('URL Slug')
                                            ->required()
                                            ->maxLength(255)
                                            ->unique(ignoreRecord: true)
                                            ->rules(['alpha_dash'])
                                            ->helperText('Auto-generated from job title')
                                            ->columnSpan(1),
                                        
                                        Select::make('employment_type')
                                            ->label('Employment Type')
                                            ->options([
                                                'full-time' => 'Full Time',
                                                'part-time' => 'Part Time',
                                                'contract' => 'Contract',
                                                'internship' => 'Internship',
                                                'temporary' => 'Temporary',
                                                'freelance' => 'Freelance',
                                            ])
                                            ->required()
                                            ->searchable()
                                            ->columnSpan(1),
                                        
                                        Select::make('experience_level')
                                            ->label('Experience Level')
                                            ->options([
                                                'entry' => 'Entry Level (0-2 years)',
                                                'mid' => 'Mid Level (3-5 years)',
                                                'senior' => 'Senior Level (6-10 years)',
                                                'lead' => 'Lead (10+ years)',
                                                'executive' => 'Executive',
                                            ])
                                            ->required()
                                            ->searchable()
                                            ->columnSpan(1),
                                        
                                        Select::make('work_mode')
                                            ->label('Work Mode')
                                            ->options([
                                                'remote' => 'Remote',
                                                'hybrid' => 'Hybrid',
                                                'onsite' => 'Onsite',
                                            ])
                                            ->required()
                                            ->searchable()
                                            ->columnSpan(1),
                                        
                                        TextInput::make('location')
                                            ->label('Location')
                                            ->maxLength(255)
                                            ->helperText('City, State/Country (e.g., "Bangalore, India")')
                                            ->columnSpan(1),
                                        
                                        TextInput::make('openings')
                                            ->label('Number of Openings')
                                            ->required()
                                            ->numeric()
                                            ->default(1)
                                            ->minValue(1)
                                            ->maxValue(100)
                                            ->columnSpan(1),
                                        
                                        DatePicker::make('deadline')
                                            ->label('Application Deadline')
                                            ->native(false)
                                            ->displayFormat('d M Y')
                                            ->minDate(now())
                                            ->helperText('Optional: Set a deadline for applications')
                                            ->columnSpan(1),
                                    ])
                                    ->columns(2),
                                
                                Section::make('Job Description')
                                    ->schema([
                                        RichEditor::make('description')
                                            ->label('Description')
                                            ->required()
                                            ->toolbarButtons([
                                                'bold',
                                                'italic',
                                                'underline',
                                                'link',
                                                'bulletList',
                                                'orderedList',
                                                'h2',
                                                'h3',
                                                'blockquote',
                                                'codeBlock',
                                            ])
                                            ->columnSpanFull()
                                            ->helperText('Describe the role, team, and what makes this opportunity unique'),
                                    ]),
                            ]),
                        
                        // Tab 2: Requirements & Qualifications
                        Tabs\Tab::make('Requirements')
                            ->icon('heroicon-o-academic-cap')
                            ->schema([
                                Section::make('Required Qualifications')
                                    ->schema([
                                        Repeater::make('requirements')
                                            ->label('Requirements')
                                            ->schema([
                                                TextInput::make('requirement')
                                                    ->label('Requirement')
                                                    ->required()
                                                    ->maxLength(500)
                                                    ->columnSpanFull(),
                                            ])
                                            ->defaultItems(3)
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => $state['requirement'] ?? null)
                                            ->addActionLabel('Add Requirement')
                                            ->columnSpanFull()
                                            ->helperText('Essential skills, qualifications, or experience required'),
                                    ]),
                                
                                Section::make('Responsibilities')
                                    ->schema([
                                        Repeater::make('responsibilities')
                                            ->label('Key Responsibilities')
                                            ->schema([
                                                TextInput::make('responsibility')
                                                    ->label('Responsibility')
                                                    ->required()
                                                    ->maxLength(500)
                                                    ->columnSpanFull(),
                                            ])
                                            ->defaultItems(3)
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => $state['responsibility'] ?? null)
                                            ->addActionLabel('Add Responsibility')
                                            ->columnSpanFull()
                                            ->helperText('What will the person be doing day-to-day?'),
                                    ]),
                                
                                Section::make('Nice to Have')
                                    ->schema([
                                        Repeater::make('nice_to_have')
                                            ->label('Nice to Have')
                                            ->schema([
                                                TextInput::make('skill')
                                                    ->label('Skill/Qualification')
                                                    ->required()
                                                    ->maxLength(500)
                                                    ->columnSpanFull(),
                                            ])
                                            ->defaultItems(0)
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => $state['skill'] ?? null)
                                            ->addActionLabel('Add Nice-to-Have')
                                            ->columnSpanFull()
                                            ->helperText('Optional skills that would be a plus'),
                                    ]),
                            ]),
                        
                        // Tab 3: Compensation & Benefits
                        Tabs\Tab::make('Compensation')
                            ->icon('heroicon-o-currency-rupee')
                            ->schema([
                                Section::make('Salary Information')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                TextInput::make('salary_min')
                                                    ->label('Minimum Salary')
                                                    ->numeric()
                                                    ->prefix('₹')
                                                    ->step(10000)
                                                    ->minValue(0)
                                                    ->helperText('Annual CTC in INR'),
                                                
                                                TextInput::make('salary_max')
                                                    ->label('Maximum Salary')
                                                    ->numeric()
                                                    ->prefix('₹')
                                                    ->step(10000)
                                                    ->minValue(0)
                                                    ->helperText('Annual CTC in INR'),
                                                
                                                Select::make('salary_currency')
                                                    ->label('Currency')
                                                    ->options([
                                                        'INR' => 'INR (₹)',
                                                        'USD' => 'USD ($)',
                                                        'EUR' => 'EUR (€)',
                                                        'GBP' => 'GBP (£)',
                                                    ])
                                                    ->default('INR')
                                                    ->required(),
                                            ]),
                                        
                                        Toggle::make('salary_negotiable')
                                            ->label('Salary is Negotiable')
                                            ->default(false)
                                            ->inline(false)
                                            ->helperText('Enable if salary range is flexible'),
                                    ]),
                                
                                Section::make('Benefits & Perks')
                                    ->schema([
                                        Repeater::make('benefits')
                                            ->label('Benefits')
                                            ->schema([
                                                Select::make('category')
                                                    ->label('Category')
                                                    ->options([
                                                        '🏥 Health' => 'Health & Wellness',
                                                        '💰 Financial' => 'Financial',
                                                        '⚖️ Work-Life' => 'Work-Life Balance',
                                                        '📚 Professional' => 'Professional Development',
                                                        '🎉 Perks' => 'Perks & Amenities',
                                                        '🛡️ Insurance' => 'Insurance',
                                                        '🏖️ Vacation' => 'Vacation & Time Off',
                                                        '👨‍👩‍👧 Family' => 'Family Support',
                                                    ])
                                                    ->required()
                                                    ->searchable(),
                                                
                                                TextInput::make('title')
                                                    ->label('Benefit Title')
                                                    ->required()
                                                    ->maxLength(255),
                                                
                                                TextInput::make('description')
                                                    ->label('Description')
                                                    ->maxLength(500)
                                                    ->columnSpanFull(),
                                            ])
                                            ->defaultItems(0)
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                                            ->addActionLabel('Add Benefit')
                                            ->columnSpanFull()
                                            ->columns(2),
                                    ]),
                            ]),
                        
                        // Tab 4: AI Insights
                        Tabs\Tab::make('AI Insights')
                            ->icon('heroicon-o-sparkles')
                            ->schema([
                                Section::make('AI-Generated Insights')
                                    ->description('AI-powered analysis and extracted information')
                                    ->schema([
                                        TagsInput::make('extracted_skills')
                                            ->label('Extracted Skills')
                                            ->helperText('AI-extracted skills from job description')
                                            ->placeholder('Skills will be auto-extracted by AI')
                                            ->suggestions([
                                                'JavaScript', 'TypeScript', 'Python', 'Java', 'PHP', 'Ruby', 'Go', 'Rust',
                                                'React', 'Vue.js', 'Angular', 'Next.js', 'Node.js', 'Express.js',
                                                'Laravel', 'Django', 'Spring Boot', 'Ruby on Rails',
                                                'AWS', 'Azure', 'Google Cloud', 'Docker', 'Kubernetes',
                                                'PostgreSQL', 'MySQL', 'MongoDB', 'Redis', 'Elasticsearch',
                                                'Git', 'CI/CD', 'Agile', 'Scrum', 'REST API', 'GraphQL',
                                                'Machine Learning', 'Data Analysis', 'DevOps', 'Testing',
                                            ])
                                            ->columnSpanFull(),
                                        
                                        Repeater::make('ai_insights')
                                            ->label('AI Insights')
                                            ->schema([
                                                Select::make('type')
                                                    ->label('Insight Type')
                                                    ->options([
                                                        'skill_match' => 'Skill Match Analysis',
                                                        'market_rate' => 'Market Rate Comparison',
                                                        'quality' => 'Quality Assessment',
                                                        'suggestion' => 'Improvement Suggestion',
                                                        'warning' => 'Warning/Flag',
                                                    ])
                                                    ->required(),
                                                
                                                TextInput::make('title')
                                                    ->label('Insight Title')
                                                    ->required()
                                                    ->maxLength(255),
                                                
                                                TextInput::make('value')
                                                    ->label('Value/Description')
                                                    ->required()
                                                    ->maxLength(500)
                                                    ->columnSpanFull(),
                                            ])
                                            ->defaultItems(0)
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                                            ->addActionLabel('Add Insight')
                                            ->columnSpanFull()
                                            ->columns(2),
                                        
                                        TextInput::make('quality_score')
                                            ->label('Quality Score')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->step(0.1)
                                            ->suffix('/ 100')
                                            ->helperText('AI-calculated job posting quality score')
                                            ->columnSpan(1),
                                    ]),
                            ]),
                        
                        // Tab 5: Moderation & Settings
                        Tabs\Tab::make('Settings')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Section::make('Job Status & Moderation')
                                    ->schema([
                                        Select::make('status')
                                            ->label('Status')
                                            ->options([
                                                'draft' => 'Draft',
                                                'active' => 'Active',
                                                'paused' => 'Paused',
                                                'closed' => 'Closed',
                                            ])
                                            ->default('draft')
                                            ->required()
                                            ->helperText('Draft: Not visible | Active: Live on platform | Paused: Temporarily hidden | Closed: No longer accepting')
                                            ->columnSpan(1),
                                        
                                        Toggle::make('is_featured')
                                            ->label('Featured Job')
                                            ->default(false)
                                            ->inline(false)
                                            ->helperText('Featured jobs appear at the top of search results')
                                            ->onIcon('heroicon-o-star')
                                            ->offIcon('heroicon-o-star')
                                            ->columnSpan(1),
                                        
                                        Toggle::make('is_urgent')
                                            ->label('Urgent Hiring')
                                            ->default(false)
                                            ->inline(false)
                                            ->helperText('Display "Urgent" badge for immediate hiring needs')
                                            ->onIcon('heroicon-o-bolt')
                                            ->offIcon('heroicon-o-bolt')
                                            ->columnSpan(1),
                                    ])
                                    ->columns(3),
                                
                                Section::make('Statistics')
                                    ->description('Job performance metrics')
                                    ->schema([
                                        TextInput::make('views')
                                            ->label('Total Views')
                                            ->numeric()
                                            ->default(0)
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->columnSpan(1),
                                        
                                        TextInput::make('applications_count')
                                            ->label('Total Applications')
                                            ->numeric()
                                            ->default(0)
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->columnSpan(1),
                                    ])
                                    ->columns(2)
                                    ->visible(fn ($record) => $record !== null),
                                
                                Section::make('System Information')
                                    ->schema([
                                        Placeholder::make('created_at')
                                            ->label('Created At')
                                            ->content(fn ($record): string => $record?->created_at?->diffForHumans() ?? '-'),
                                        
                                        Placeholder::make('updated_at')
                                            ->label('Last Updated')
                                            ->content(fn ($record): string => $record?->updated_at?->diffForHumans() ?? '-'),
                                    ])
                                    ->columns(2)
                                    ->visible(fn ($record) => $record !== null),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
