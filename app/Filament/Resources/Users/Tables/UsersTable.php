<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar')
                    ->label('Avatar')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&color=ec4899&background=fdf2f8')
                    ->size(40),

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->description(fn ($record) => $record->email)
                    ->copyable()
                    ->copyMessage('Name copied!')
                    ->tooltip('Click to copy'),

                TextColumn::make('account_type')
                    ->label('Type')
                    ->badge()
                    ->colors([
                        'success' => 'job_seeker',
                        'warning' => 'employer',
                        'danger' => 'admin',
                    ])
                    ->icons([
                        'heroicon-o-user' => 'job_seeker',
                        'heroicon-o-building-office-2' => 'employer',
                        'heroicon-o-shield-check' => 'admin',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state)))
                    ->sortable(),

                TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-o-phone')
                    ->placeholder('Not provided')
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable()
                    ->tooltip(fn ($state) => $state ? 'Active' : 'Inactive'),

                IconColumn::make('email_verified_at')
                    ->label('Verified')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->sortable()
                    ->tooltip(fn ($record) => $record->email_verified_at 
                        ? 'Verified on ' . $record->email_verified_at->format('M d, Y') 
                        : 'Not verified'),

                TextColumn::make('last_login_at')
                    ->label('Last Login')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->icon('heroicon-o-clock')
                    ->color('gray')
                    ->description(fn ($record) => $record->last_login_at 
                        ? $record->last_login_at->diffForHumans() 
                        : null)
                    ->placeholder('Never')
                    ->toggleable(),

                TextColumn::make('timezone')
                    ->label('Timezone')
                    ->icon('heroicon-o-globe-alt')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Joined')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->icon('heroicon-o-calendar')
                    ->description(fn ($record) => $record->created_at->diffForHumans())
                    ->toggleable(),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('deleted_at')
                    ->label('Deleted')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('account_type')
                    ->label('Account Type')
                    ->options([
                        'job_seeker' => 'Job Seeker',
                        'employer' => 'Employer',
                        'admin' => 'Admin',
                    ])
                    ->indicator('Type')
                    ->multiple(),

                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('All users')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only')
                    ->indicator('Status'),

                TernaryFilter::make('email_verified_at')
                    ->label('Email Verified')
                    ->placeholder('All users')
                    ->trueLabel('Verified only')
                    ->falseLabel('Unverified only')
                    ->indicator('Verification')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('email_verified_at'),
                        false: fn (Builder $query) => $query->whereNull('email_verified_at'),
                    ),

                Filter::make('last_login')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('logged_in_after')
                            ->label('Logged in after')
                            ->native(false),
                        \Filament\Forms\Components\DatePicker::make('logged_in_before')
                            ->label('Logged in before')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['logged_in_after'],
                                fn (Builder $query, $date): Builder => $query->whereDate('last_login_at', '>=', $date),
                            )
                            ->when(
                                $data['logged_in_before'],
                                fn (Builder $query, $date): Builder => $query->whereDate('last_login_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['logged_in_after'] ?? null) {
                            $indicators[] = 'Logged in after ' . \Carbon\Carbon::parse($data['logged_in_after'])->format('M d, Y');
                        }
                        if ($data['logged_in_before'] ?? null) {
                            $indicators[] = 'Logged in before ' . \Carbon\Carbon::parse($data['logged_in_before'])->format('M d, Y');
                        }
                        return $indicators;
                    }),

                Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('created_from')
                            ->label('Created from')
                            ->native(false),
                        \Filament\Forms\Components\DatePicker::make('created_until')
                            ->label('Created until')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),

                TrashedFilter::make()
                    ->label('Deleted Users'),
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                ViewAction::make()
                    ->iconButton()
                    ->tooltip('View details'),

                EditAction::make()
                    ->iconButton()
                    ->tooltip('Edit user'),

                Action::make('verify_email')
                    ->label('Verify Email')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Verify Email Address')
                    ->modalDescription('Are you sure you want to mark this email as verified?')
                    ->modalSubmitActionLabel('Yes, verify')
                    ->action(function ($record) {
                        $record->update(['email_verified_at' => now()]);
                        Notification::make()
                            ->title('Email Verified')
                            ->success()
                            ->body('Email address has been verified.')
                            ->send();
                    })
                    ->visible(fn ($record) => !$record->email_verified_at)
                    ->iconButton()
                    ->tooltip('Verify email'),

                Action::make('toggle_status')
                    ->label(fn ($record) => $record->is_active ? 'Deactivate' : 'Activate')
                    ->icon(fn ($record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record) => ($record->is_active ? 'Deactivate' : 'Activate') . ' User')
                    ->modalDescription(fn ($record) => 'Are you sure you want to ' . ($record->is_active ? 'deactivate' : 'activate') . ' this user?')
                    ->action(function ($record) {
                        $newStatus = !$record->is_active;
                        $record->update(['is_active' => $newStatus]);
                        Notification::make()
                            ->title($newStatus ? 'User Activated' : 'User Deactivated')
                            ->success()
                            ->body('User status has been updated.')
                            ->send();
                    })
                    ->iconButton()
                    ->tooltip(fn ($record) => $record->is_active ? 'Deactivate user' : 'Activate user'),

                Action::make('impersonate')
                    ->label('Impersonate')
                    ->icon('heroicon-o-user-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Impersonate User')
                    ->modalDescription(fn ($record) => 'You will be logged in as ' . $record->name . ' (' . $record->email . '). Continue?')
                    ->modalSubmitActionLabel('Start Impersonation')
                    ->action(function ($record) {
                        $currentAdminId = Auth::id();
                        
                        // Store admin ID in session for later restoration
                        session(['impersonating_admin_id' => $currentAdminId]);
                        session(['impersonating' => true]);
                        session(['impersonated_user_id' => $record->id]);
                        
                        // Log out current admin and log in as the user
                        Auth::login($record);
                        
                        Notification::make()
                            ->title('Impersonation Started')
                            ->warning()
                            ->body('You are now viewing as: ' . $record->name . '. You can stop impersonation from your profile.')
                            ->persistent()
                            ->send();
                        
                        // Redirect to user's dashboard
                        return redirect()->route($record->account_type === 'employer' ? 'employer.dashboard' : 'dashboard');
                    })
                    ->visible(fn ($record) => Auth::user()->account_type === 'admin' && $record->account_type !== 'admin' && $record->id !== Auth::id())
                    ->iconButton()
                    ->tooltip('Impersonate user'),

                DeleteAction::make()
                    ->iconButton()
                    ->tooltip('Soft delete'),

                ForceDeleteAction::make()
                    ->iconButton()
                    ->tooltip('Permanently delete'),

                RestoreAction::make()
                    ->iconButton()
                    ->tooltip('Restore user'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Soft Delete Selected')
                        ->icon('heroicon-o-trash')
                        ->deselectRecordsAfterCompletion(),

                    ForceDeleteBulkAction::make()
                        ->label('Permanently Delete Selected')
                        ->icon('heroicon-o-trash')
                        ->deselectRecordsAfterCompletion(),

                    RestoreBulkAction::make()
                        ->label('Restore Selected')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->deselectRecordsAfterCompletion(),

                    \Filament\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Activate Selected Users')
                        ->modalDescription('Are you sure you want to activate all selected users?')
                        ->action(function ($records) {
                            $records->each->update(['is_active' => true]);
                            Notification::make()
                                ->title('Users Activated')
                                ->success()
                                ->body(count($records) . ' users have been activated.')
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    \Filament\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Deactivate Selected Users')
                        ->modalDescription('Are you sure you want to deactivate all selected users?')
                        ->action(function ($records) {
                            $records->each->update(['is_active' => false]);
                            Notification::make()
                                ->title('Users Deactivated')
                                ->warning()
                                ->body(count($records) . ' users have been deactivated.')
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    \Filament\Actions\BulkAction::make('verify_emails')
                        ->label('Verify Emails')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Verify Email Addresses')
                        ->modalDescription('Mark all selected users as email verified?')
                        ->action(function ($records) {
                            $records->each->update(['email_verified_at' => now()]);
                            Notification::make()
                                ->title('Emails Verified')
                                ->success()
                                ->body(count($records) . ' email addresses have been verified.')
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    \Filament\Actions\BulkAction::make('export')
                        ->label('Export to Excel')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->action(function ($records) {
                            try {
                                $filename = 'users_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
                                $filePath = storage_path('app/public/exports/' . $filename);

                                // Ensure export directory exists
                                if (!file_exists(storage_path('app/public/exports'))) {
                                    mkdir(storage_path('app/public/exports'), 0755, true);
                                }

                                $writer = new \OpenSpout\Writer\XLSX\Writer();
                                $writer->openToFile($filePath);

                                // Header row
                                $headerRow = \OpenSpout\Writer\XLSX\Row::fromValues([
                                    'ID',
                                    'Name',
                                    'Email',
                                    'Account Type',
                                    'Phone',
                                    'Location',
                                    'Current Title',
                                    'Years of Experience',
                                    'Active',
                                    'Verified',
                                    'Created At',
                                    'Updated At',
                                ]);
                                $writer->addRow($headerRow);

                                // Data rows
                                foreach ($records as $user) {
                                    $row = \OpenSpout\Writer\XLSX\Row::fromValues([
                                        $user->id,
                                        $user->name,
                                        $user->email,
                                        ucfirst($user->account_type ?? 'Unknown'),
                                        $user->phone ?? 'N/A',
                                        $user->location ?? 'N/A',
                                        $user->current_title ?? 'N/A',
                                        $user->years_of_experience ?? 0,
                                        $user->is_active ? 'Yes' : 'No',
                                        $user->email_verified_at ? 'Yes' : 'No',
                                        $user->created_at?->format('Y-m-d H:i:s') ?? 'N/A',
                                        $user->updated_at?->format('Y-m-d H:i:s') ?? 'N/A',
                                    ]);
                                    $writer->addRow($row);
                                }

                                $writer->close();

                                Notification::make()
                                    ->title('Export Complete')
                                    ->success()
                                    ->body('Exported ' . count($records) . ' users. Download: ' . $filename)
                                    ->persistent()
                                    ->send();

                                // Return download response
                                return response()->download($filePath)->deleteFileAfterSend(true);
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Export Failed')
                                    ->danger()
                                    ->body('Error: ' . $e->getMessage())
                                    ->send();
                            }
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s')
            ->deferLoading()
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->recordUrl(fn ($record) => route('filament.studai.resources.users.view', $record))
            ->recordClasses(fn ($record) => $record->trashed() ? 'opacity-50' : null);
    }
}
