<?php

namespace App\Filament\Widgets;

use App\Models\AdEvent;
use Filament\Widgets\ChartWidget;

class AdEventsChart extends ChartWidget
{
    protected static ?string $heading = 'Ad Impressions vs Clicks (last 14 days)';
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $days = collect(range(13, 0))->map(fn ($i) => today()->subDays($i));

        $countsFor = fn (string $eventType) => $days->map(
            fn ($day) => AdEvent::where('event_type', $eventType)
                ->where('is_suspicious', false)
                ->whereDate('created_at', $day)
                ->count()
        );

        return [
            'datasets' => [
                [
                    'label' => 'Impressions',
                    'data' => $countsFor('impression')->toArray(),
                    'borderColor' => '#4F46E5',
                    'backgroundColor' => 'rgba(79, 70, 229, 0.1)',
                ],
                [
                    'label' => 'Clicks',
                    'data' => $countsFor('click')->toArray(),
                    'borderColor' => '#F59E0B',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                ],
            ],
            'labels' => $days->map(fn ($day) => $day->format('M j'))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
