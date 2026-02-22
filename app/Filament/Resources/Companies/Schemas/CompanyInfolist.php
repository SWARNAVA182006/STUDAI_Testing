<?php

namespace App\Filament\Resources\Companies\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CompanyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('slug'),
                TextEntry::make('description')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('logo')
                    ->placeholder('-'),
                TextEntry::make('website')
                    ->placeholder('-'),
                TextEntry::make('industry')
                    ->placeholder('-'),
                TextEntry::make('company_size')
                    ->placeholder('-'),
                TextEntry::make('founded_year')
                    ->placeholder('-'),
                TextEntry::make('headquarters')
                    ->placeholder('-'),
                TextEntry::make('locations')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('benefits')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('tech_stack')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('culture_rating')
                    ->numeric()
                    ->placeholder('-'),
                IconEntry::make('is_verified')
                    ->boolean(),
                IconEntry::make('is_featured')
                    ->boolean(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
