<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\OfferLetterResource\Pages;
use App\Models\OfferLetter;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Actions;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OfferLetterResource extends Resource
{
    protected static ?string $model = OfferLetter::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    protected static \UnitEnum|string|null $navigationGroup = 'Hiring';

    protected static ?string $navigationLabel = 'Offer Letters';

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::whereIn('status', ['sent', 'viewed', 'under_review', 'counter_offered'])->count();
    }

    public static function getNavigationBadgeColor(): string
    {
        $pending = static::getModel()::whereIn('status', ['sent', 'viewed', 'under_review', 'counter_offered'])->count();
        return $pending > 0 ? 'warning' : 'success';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Position Details')
                    ->columns(2)
                    ->schema([
                        Select::make('candidate_id')
                            ->label('Candidate')
                            ->options(fn () => User::whereHas('roles', fn($q) => $q->where('name', 'user'))
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('job_id')
                            ->label('Job Posting')
                            ->relationship('job', 'title')
                            ->searchable()
                            ->preload(),

                        TextInput::make('job_title')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('department')
                            ->maxLength(255),

                        Select::make('employment_type')
                            ->options([
                                'full-time' => 'Full Time',
                                'part-time' => 'Part Time',
                                'contract' => 'Contract',
                                'internship' => 'Internship',
                            ])
                            ->required()
                            ->default('full-time'),

                        Select::make('work_arrangement')
                            ->options([
                                'on-site' => 'On-Site',
                                'remote' => 'Remote',
                                'hybrid' => 'Hybrid',
                            ])
                            ->required()
                            ->default('on-site'),

                        TextInput::make('work_location')
                            ->maxLength(255),

                        TextInput::make('reporting_to')
                            ->label('Reports To')
                            ->maxLength(255),
                    ]),

                Section::make('Compensation')
                    ->columns(3)
                    ->schema([
                        TextInput::make('base_salary')
                            ->numeric()
                            ->prefix('$')
                            ->required()
                            ->minValue(0),

                        Select::make('salary_period')
                            ->options([
                                'hourly' => 'Hourly',
                                'weekly' => 'Weekly',
                                'bi-weekly' => 'Bi-Weekly',
                                'monthly' => 'Monthly',
                                'annually' => 'Annually',
                            ])
                            ->required()
                            ->default('annually'),

                        Select::make('currency')
                            ->options([
                                'USD' => 'USD ($)',
                                'EUR' => 'EUR (€)',
                                'GBP' => 'GBP (£)',
                                'CAD' => 'CAD ($)',
                                'AUD' => 'AUD ($)',
                            ])
                            ->required()
                            ->default('USD'),

                        TextInput::make('signing_bonus')
                            ->numeric()
                            ->prefix('$')
                            ->minValue(0),

                        TextInput::make('annual_bonus_target')
                            ->label('Annual Bonus Target (%)')
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100),

                        Textarea::make('bonus_structure')
                            ->rows(2)
                            ->columnSpan(1),
                    ]),

                Section::make('Equity')
                    ->columns(3)
                    ->schema([
                        TextInput::make('equity_shares')
                            ->numeric()
                            ->minValue(0),

                        TextInput::make('equity_type')
                            ->placeholder('e.g., Stock Options, RSUs')
                            ->maxLength(100),

                        Textarea::make('vesting_schedule')
                            ->placeholder('e.g., 4-year vesting with 1-year cliff')
                            ->rows(2),
                    ]),

                Section::make('Dates & Deadlines')
                    ->columns(3)
                    ->schema([
                        DatePicker::make('start_date')
                            ->required()
                            ->minDate(now()),

                        DatePicker::make('offer_expiry_date')
                            ->required()
                            ->minDate(now()),

                        DatePicker::make('response_deadline')
                            ->minDate(now()),
                    ]),

                Section::make('Benefits & Additional Details')
                    ->schema([
                        Select::make('benefits_package_id')
                            ->relationship('benefitsPackage', 'name')
                            ->searchable()
                            ->preload(),

                        Select::make('template_id')
                            ->label('Letter Template')
                            ->relationship('template', 'name')
                            ->searchable()
                            ->preload(),

                        RichEditor::make('letter_content')
                            ->label('Custom Letter Content')
                            ->columnSpanFull(),

                        Textarea::make('special_conditions')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('candidate.name')
                    ->label('Candidate')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('job_title')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('company.name')
                    ->label('Company')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('formatted_salary')
                    ->label('Salary'),

                TextColumn::make('total_compensation')
                    ->label('Total Comp')
                    ->money('USD')
                    ->sortable(),

                BadgeColumn::make('status')
                    ->colors([
                        'gray' => 'draft',
                        'warning' => ['pending_approval', 'sent', 'viewed', 'under_review'],
                        'success' => 'accepted',
                        'danger' => ['declined', 'withdrawn', 'expired'],
                        'info' => 'counter_offered',
                    ]),

                TextColumn::make('start_date')
                    ->date()
                    ->sortable(),

                TextColumn::make('offer_expiry_date')
                    ->label('Expires')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => $record->is_expired ? 'danger' : null),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'pending_approval' => 'Pending Approval',
                        'approved' => 'Approved',
                        'sent' => 'Sent',
                        'viewed' => 'Viewed',
                        'under_review' => 'Under Review',
                        'accepted' => 'Accepted',
                        'declined' => 'Declined',
                        'counter_offered' => 'Counter Offered',
                        'withdrawn' => 'Withdrawn',
                        'expired' => 'Expired',
                    ]),

                SelectFilter::make('employment_type')
                    ->options([
                        'full-time' => 'Full Time',
                        'part-time' => 'Part Time',
                        'contract' => 'Contract',
                        'internship' => 'Internship',
                    ]),

                SelectFilter::make('work_arrangement')
                    ->options([
                        'on-site' => 'On-Site',
                        'remote' => 'Remote',
                        'hybrid' => 'Hybrid',
                    ]),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\EditAction::make()
                    ->visible(fn ($record) => $record->isDraft()),
                Actions\Action::make('send')
                    ->label('Send Offer')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->visible(fn ($record) => $record->isDraft())
                    ->requiresConfirmation()
                    ->action(function (OfferLetter $record) {
                        $service = app(\App\Services\OfferLetterService::class);
                        $success = $service->sendOffer($record);
                        
                        if ($success) {
                            Notification::make()
                                ->success()
                                ->title('Offer Sent')
                                ->body('The offer letter has been sent to the candidate.')
                                ->send();
                        } else {
                            Notification::make()
                                ->danger()
                                ->title('Failed to Send')
                                ->body('There was an error sending the offer letter.')
                                ->send();
                        }
                    }),
                Actions\Action::make('download')
                    ->label('Download PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn ($record) => route('offer-letters.download', $record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->hasRole('super-admin')),
                ]),
            ]);
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
            'index' => Pages\ListOfferLetters::route('/'),
            'create' => Pages\CreateOfferLetter::route('/create'),
            'view' => Pages\ViewOfferLetter::route('/{record}'),
            'edit' => Pages\EditOfferLetter::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        $query = parent::getEloquentQuery()->with(['candidate', 'company', 'job']);

        // Filter by company for non-admin users
        if (!$user->hasRole('super-admin') && $user->company_id) {
            $query->where('company_id', $user->company_id);
        }

        return $query;
    }
}
