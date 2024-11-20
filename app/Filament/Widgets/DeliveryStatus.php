<?php

namespace App\Filament\Widgets;

use App\Models\SmsOutbox;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class DeliveryStatus extends ChartWidget
{
    protected static ?string $heading = 'Message Delivery Status';
    protected static ?int $sort = 3;
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $data = SmsOutbox::select('status', DB::raw('count(*) as count'))
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('status')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Messages by Status',
                    'data' => $data->pluck('count')->toArray(),
                    'backgroundColor' => [
                        'rgb(34, 197, 94)', // success
                        'rgb(239, 68, 68)', // danger
                        'rgb(234, 179, 8)',  // warning
                        'rgb(156, 163, 175)', // gray
                    ],
                ],
            ],
            'labels' => $data->pluck('status')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    public static function canView(): bool
    {
        return auth()->user()->can('view_stats');
    }
}