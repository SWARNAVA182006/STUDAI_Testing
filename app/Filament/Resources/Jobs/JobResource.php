<?php

namespace App\Filament\Resources\Jobs;

use App\Filament\Resources\Jobs\Pages\CreateJob;
use App\Filament\Resources\Jobs\Pages\EditJob;
use App\Filament\Resources\Jobs\Pages\ListJobs;
use App\Filament\Resources\Jobs\Pages\ViewJob;
use App\Filament\Resources\Jobs\Schemas\JobForm;
use App\Filament\Resources\Jobs\Schemas\JobInfolist;
use App\Filament\Resources\Jobs\Tables\JobsTable;
use App\Models\Job;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class JobResource extends Resource
{
    protected static ?string $model = Job::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-briefcase';

    protected static \UnitEnum|string|null $navigationGroup = 'Content Management';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getNavigationBadge(): ?string
    {
        $activeCount = static::getModel()::where('status', 'active')->count();
        $pendingCount = static::getModel()::where('status', 'draft')->count();
        
        return $pendingCount > 0 ? "{$activeCount} active / {$pendingCount} pending" : (string) $activeCount;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        $pendingCount = static::getModel()::where('status', 'draft')->count();
        
        if ($pendingCount > 10) {
            return 'danger'; // Too many pending jobs need moderation
        }
        
        if ($pendingCount > 5) {
            return 'warning'; // Some pending jobs
        }
        
        return 'success'; // Few or no pending jobs
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'slug', 'description', 'location', 'company.name'];
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'Company' => $record->company?->name,
            'Location' => $record->location,
            'Type' => ucfirst($record->employment_type),
            'Status' => ucfirst($record->status),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return JobForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return JobInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return JobsTable::configure($table);
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
            'index' => ListJobs::route('/'),
            'create' => CreateJob::route('/create'),
            'view' => ViewJob::route('/{record}'),
            'edit' => EditJob::route('/{record}/edit'),
        ];
    }
}
