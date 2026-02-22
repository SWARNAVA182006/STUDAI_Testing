<?php

declare(strict_types=1);

namespace App\Filament\Resources\AIPromptResource\Pages;

use App\Filament\Resources\AIPromptResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewAIPrompt extends ViewRecord
{
    protected static string $resource = AIPromptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('duplicate')
                ->label('Create New Version')
                ->icon('heroicon-o-document-duplicate')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Create New Version')
                ->modalDescription('This will create a new version of this prompt.')
                ->action(function () {
                    $newVersion = $this->record->createNewVersion(['is_active' => false]);
                    return redirect()->to(AIPromptResource::getUrl('edit', ['record' => $newVersion]));
                }),
        ];
    }

    public function infolist(Schema $infolist): Schema
    {
        return $infolist
            ->schema([
                Section::make('Prompt Information')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('name')
                            ->label('Name')
                            ->copyable()
                            ->weight('bold'),
                        TextEntry::make('category')
                            ->label('Category')
                            ->badge(),
                        TextEntry::make('version')
                            ->label('Version')
                            ->badge()
                            ->color('gray'),
                        IconEntry::make('is_active')
                            ->label('Active')
                            ->boolean(),
                        TextEntry::make('description')
                            ->label('Description')
                            ->columnSpanFull(),
                    ]),

                Section::make('System Prompt')
                    ->schema([
                        TextEntry::make('system_prompt')
                            ->label('')
                            ->markdown()
                            ->prose(),
                    ])
                    ->collapsed(fn ($record) => empty($record->system_prompt)),

                Section::make('Prompt Template')
                    ->schema([
                        TextEntry::make('template')
                            ->label('')
                            ->markdown()
                            ->prose(),
                    ]),

                Section::make('Model Configuration')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('model_hint')
                            ->label('Preferred Model')
                            ->badge()
                            ->color('info'),
                        TextEntry::make('max_tokens')
                            ->label('Max Tokens'),
                        TextEntry::make('temperature')
                            ->label('Temperature'),
                    ]),

                Section::make('Performance Metrics')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('usage_count')
                            ->label('Total Uses')
                            ->numeric(),
                        TextEntry::make('avg_latency_ms')
                            ->label('Avg Latency')
                            ->suffix(' ms'),
                        TextEntry::make('success_rate')
                            ->label('Success Rate')
                            ->suffix('%')
                            ->color(fn ($state) => match (true) {
                                $state >= 95 => 'success',
                                $state >= 80 => 'warning',
                                default => 'danger',
                            }),
                    ]),

                Section::make('Variables')
                    ->schema([
                        KeyValueEntry::make('variables')
                            ->label(''),
                    ])
                    ->collapsed()
                    ->visible(fn ($record) => !empty($record->variables)),

                Section::make('Metadata')
                    ->schema([
                        KeyValueEntry::make('metadata')
                            ->label(''),
                    ])
                    ->collapsed()
                    ->visible(fn ($record) => !empty($record->metadata)),

                Section::make('Audit Information')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        TextEntry::make('creator.name')
                            ->label('Created By'),
                        TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime(),
                        TextEntry::make('updater.name')
                            ->label('Updated By'),
                        TextEntry::make('updated_at')
                            ->label('Updated At')
                            ->dateTime(),
                    ]),
            ]);
    }
}
