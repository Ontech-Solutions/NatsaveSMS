<?php

namespace App\Filament\Widgets;

use App\Models\SmsOutbox;
use App\Models\Contact;
use App\Models\BulkMessage;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class StatsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '15s';

    protected function getStats(): array
    {
        // Get total messages sent today
        $todayMessages = SmsOutbox::whereDate('created_at', today())->count();
        
        // Get delivery success rate
        $totalMessages = SmsOutbox::where('created_at', '>=', now()->subDays(30))->count();
        $deliveredMessages = SmsOutbox::where('created_at', '>=', now()->subDays(30))
            ->where('status', 'delivered')
            ->count();
        $deliveryRate = $totalMessages > 0 
            ? round(($deliveredMessages / $totalMessages) * 100, 1) 
            : 0;

        // Get pending scheduled messages
        $pendingScheduled = BulkMessage::where('status', 'pending')
            ->where('scheduled_at', '>', now())
            ->count();

        // Get total contacts
        $totalContacts = Contact::count();

        // Get monthly trend
        $monthlyTrend = SmsOutbox::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->get()
            ->pluck('count')
            ->toArray();

        return [
            Stat::make('Messages Sent Today', $todayMessages)
                ->description('Total messages sent today')
                ->descriptionIcon('heroicon-m-envelope')
                ->color('primary'),

            Stat::make('Delivery Success Rate', $deliveryRate . '%')
                ->description('Last 30 days average')
                ->descriptionIcon('heroicon-m-check-circle')
                ->chart($monthlyTrend)
                ->color($deliveryRate >= 95 ? 'success' : ($deliveryRate >= 90 ? 'warning' : 'danger')),

            Stat::make('Scheduled Messages', $pendingScheduled)
                ->description('Pending scheduled messages')
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),

            Stat::make('Total Contacts', $totalContacts)
                ->description('Active contacts in database')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),
        ];
    }

    public static function canView(): bool
    {
        // Only show for users with appropriate permissions
        return auth()->user()->can('view_stats');
    }
}