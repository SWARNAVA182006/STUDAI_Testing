<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\AgentConfiguration;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use BackedEnum;
use UnitEnum;

class AgentEmergencyControls extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-exclamation';

    protected string $view = 'filament.pages.agent-emergency-controls';

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 100;

    protected static ?string $title = 'Agent Emergency Controls';

    public bool $globalKillSwitchActive = false;

    public ?array $killSwitchInfo = null;

    public function mount(): void
    {
        $this->globalKillSwitchActive = AgentConfiguration::isGlobalKillSwitchActive();
        $this->killSwitchInfo = AgentConfiguration::getGlobalKillSwitchInfo();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('activateKillSwitch')
                ->label('Activate Global Kill Switch')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Activate Global Kill Switch')
                ->modalDescription('This will immediately stop ALL agent operations across the entire platform. This action should only be used in emergencies.')
                ->modalSubmitActionLabel('Yes, Stop All Agents')
                ->form([
                    Textarea::make('reason')
                        ->label('Reason for Emergency Stop')
                        ->required()
                        ->placeholder('Describe the reason for activating the kill switch...')
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $count = AgentConfiguration::activateGlobalKillSwitch(
                        auth()->id(),
                        $data['reason']
                    );

                    Notification::make()
                        ->title('Global Kill Switch Activated')
                        ->body("{$count} agents have been stopped.")
                        ->danger()
                        ->send();

                    $this->globalKillSwitchActive = true;
                    $this->killSwitchInfo = AgentConfiguration::getGlobalKillSwitchInfo();
                })
                ->visible(fn () => !$this->globalKillSwitchActive),

            Action::make('deactivateKillSwitch')
                ->label('Deactivate Global Kill Switch')
                ->icon('heroicon-o-shield-check')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Deactivate Global Kill Switch')
                ->modalDescription('This will allow agents to be reactivated. Individual agents will NOT be automatically restarted - users will need to reactivate them manually.')
                ->action(function () {
                    AgentConfiguration::deactivateGlobalKillSwitch();

                    Notification::make()
                        ->title('Global Kill Switch Deactivated')
                        ->body('Agents can now be reactivated by users.')
                        ->success()
                        ->send();

                    $this->globalKillSwitchActive = false;
                    $this->killSwitchInfo = null;
                })
                ->visible(fn () => $this->globalKillSwitchActive),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(AgentConfiguration::query())
            ->columns([
                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                IconColumn::make('emergency_stopped_at')
                    ->label('Emergency Stopped')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->isEmergencyStopped()),

                TextColumn::make('emergency_stop_reason')
                    ->label('Stop Reason')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->emergency_stop_reason),

                TextColumn::make('emergency_stopped_at')
                    ->label('Stopped At')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('stoppedByUser.name')
                    ->label('Stopped By'),

                TextColumn::make('applications_this_month')
                    ->label('Apps This Month')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('emergencyStop')
                    ->label('Emergency Stop')
                    ->icon('heroicon-o-stop-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('reason')
                            ->label('Reason')
                            ->required(),
                    ])
                    ->action(function (AgentConfiguration $record, array $data) {
                        $record->emergencyStop(auth()->id(), $data['reason']);

                        Notification::make()
                            ->title('Agent Stopped')
                            ->body("Agent for {$record->user->name} has been emergency stopped.")
                            ->warning()
                            ->send();
                    })
                    ->visible(fn (AgentConfiguration $record) => !$record->isEmergencyStopped()),

                Action::make('clearEmergencyStop')
                    ->label('Clear Stop')
                    ->icon('heroicon-o-play-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (AgentConfiguration $record) {
                        $record->clearEmergencyStop();

                        Notification::make()
                            ->title('Emergency Stop Cleared')
                            ->body("Agent for {$record->user->name} can now be reactivated.")
                            ->success()
                            ->send();
                    })
                    ->visible(fn (AgentConfiguration $record) => $record->isEmergencyStopped()),
            ])
            ->bulkActions([
                //
            ])
            ->defaultSort('emergency_stopped_at', 'desc');
    }

    public function getStatsData(): array
    {
        return [
            'total_agents' => AgentConfiguration::count(),
            'active_agents' => AgentConfiguration::where('is_active', true)->count(),
            'emergency_stopped' => AgentConfiguration::emergencyStopped()->count(),
            'globally_stopped' => AgentConfiguration::globallyStopped()->count(),
        ];
    }
}
