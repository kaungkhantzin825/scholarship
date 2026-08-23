<?php

namespace App\Filament\Widgets;

use App\Models\AdEvent;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdEventsOverview extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        // Suspicious events are excluded from the headline numbers so CTR
        // reflects real traffic — they're still stored and visible in the
        // Ad Events list for review, just not counted here.
        $clean = fn () => AdEvent::where('is_suspicious', false);

        $impressionsToday = $clean()->where('event_type', 'impression')->whereDate('created_at', today())->count();
        $clicksToday = $clean()->where('event_type', 'click')->whereDate('created_at', today())->count();
        $impressionsTotal = $clean()->where('event_type', 'impression')->count();
        $clicksTotal = $clean()->where('event_type', 'click')->count();
        $ctr = $impressionsTotal > 0 ? round($clicksTotal / $impressionsTotal * 100, 2) : 0;
        $suspiciousToday = AdEvent::where('is_suspicious', true)->whereDate('created_at', today())->count();

        return [
            Stat::make('Ad Impressions (Today)', $impressionsToday)
                ->description($impressionsTotal . ' all-time')
                ->descriptionIcon('heroicon-m-eye')
                ->color('info')
                ->chart(
                    $clean()->where('event_type', 'impression')
                        ->selectRaw('COUNT(*) as count')
                        ->groupBy(\DB::raw('DATE(created_at)'))
                        ->orderByRaw('DATE(created_at)')
                        ->limit(7)
                        ->pluck('count')
                        ->toArray()
                ),

            Stat::make('Ad Clicks (Today)', $clicksToday)
                ->description($clicksTotal . ' all-time')
                ->descriptionIcon('heroicon-m-cursor-arrow-rays')
                ->color('warning')
                ->chart(
                    $clean()->where('event_type', 'click')
                        ->selectRaw('COUNT(*) as count')
                        ->groupBy(\DB::raw('DATE(created_at)'))
                        ->orderByRaw('DATE(created_at)')
                        ->limit(7)
                        ->pluck('count')
                        ->toArray()
                ),

            Stat::make('Click-Through Rate', $ctr . '%')
                ->description('Clean clicks ÷ impressions, all-time')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($ctr > 5 ? 'danger' : 'success'),

            Stat::make('Flagged Suspicious (Today)', $suspiciousToday)
                ->description('Excluded from the stats above')
                ->descriptionIcon('heroicon-m-shield-exclamation')
                ->color($suspiciousToday > 0 ? 'danger' : 'success'),
        ];
    }
}
