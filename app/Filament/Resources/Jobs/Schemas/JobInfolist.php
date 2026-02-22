<?php

namespace App\Filament\Resources\Jobs\Schemas;

use App\Models\Job;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class JobInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('company_id')
                    ->numeric(),
                TextEntry::make('posted_by')
                    ->numeric(),
                TextEntry::make('title'),
                TextEntry::make('slug'),
                TextEntry::make('description')
                    ->columnSpanFull(),
                TextEntry::make('requirements')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('responsibilities')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('nice_to_have')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('employment_type'),
                TextEntry::make('experience_level'),
                TextEntry::make('salary_min')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('salary_max')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('salary_currency'),
                IconEntry::make('salary_negotiable')
                    ->boolean(),
                TextEntry::make('location')
                    ->placeholder('-'),
                TextEntry::make('work_mode')
                    ->badge(),
                TextEntry::make('benefits')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('openings')
                    ->numeric(),
                TextEntry::make('deadline')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('views')
                    ->numeric(),
                TextEntry::make('applications_count')
                    ->numeric(),
                IconEntry::make('is_featured')
                    ->boolean(),
                IconEntry::make('is_urgent')
                    ->boolean(),
                TextEntry::make('ai_insights')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('extracted_skills')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('quality_score')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (Job $record): bool => $record->trashed()),
            ]);
    }
}
