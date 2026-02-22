<?php

namespace App\Filament\Resources\UserSubscriptions;

use App\Filament\Resources\UserSubscriptions\Pages\CreateUserSubscription;
use App\Filament\Resources\UserSubscriptions\Pages\EditUserSubscription;
use App\Filament\Resources\UserSubscriptions\Pages\ListUserSubscriptions;
use App\Filament\Resources\UserSubscriptions\Pages\ViewUserSubscription;
use App\Filament\Resources\UserSubscriptions\Schemas\UserSubscriptionForm;
use App\Filament\Resources\UserSubscriptions\Schemas\UserSubscriptionInfolist;
use App\Filament\Resources\UserSubscriptions\Tables\UserSubscriptionsTable;
use App\Models\UserSubscription;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class UserSubscriptionResource extends Resource
{
    protected static ?string $model = UserSubscription::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-credit-card';

    protected static \UnitEnum|string|null $navigationGroup = 'Subscriptions & Billing';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'User Subscriptions';

    protected static ?string $modelLabel = 'Subscription';

    protected static ?string $pluralModelLabel = 'User Subscriptions';

    public static function getNavigationBadge(): ?string
    {
        $activeCount = static::getModel()::where('status', 'active')->count();
        $trialCount = static::getModel()::where('status', 'trialing')->count();
        
        return $trialCount > 0 ? "{$activeCount} active / {$trialCount} trial" : (string) $activeCount;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        $expiringCount = static::getModel()::where('status', 'active')
            ->where('ends_at', '<=', now()->addDays(7))
            ->count();
        
        if ($expiringCount > 10) {
            return 'danger'; // Many subscriptions expiring soon
        }
        
        if ($expiringCount > 5) {
            return 'warning'; // Some subscriptions expiring
        }
        
        return 'success'; // Healthy subscription base
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['user.name', 'user.email', 'subscription_plan.name', 'gateway_subscription_id'];
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'User' => $record->user?->name,
            'Plan' => $record->subscriptionPlan?->name,
            'Status' => ucfirst($record->status),
            'Ends' => $record->ends_at?->format('d M Y'),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return UserSubscriptionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return UserSubscriptionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UserSubscriptionsTable::configure($table);
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
            'index' => ListUserSubscriptions::route('/'),
            'create' => CreateUserSubscription::route('/create'),
            'view' => ViewUserSubscription::route('/{record}'),
            'edit' => EditUserSubscription::route('/{record}/edit'),
        ];
    }
}
