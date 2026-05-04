<?php

namespace App\Filament\Widgets;

use App\Models\Application;
use App\Models\BlogPost;
use App\Models\Scholarship;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Scholarships', Scholarship::count())
                ->description(Scholarship::where('status', 'active')->count() . ' active')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('primary')
                ->chart(
                    Scholarship::selectRaw('COUNT(*) as count')
                        ->groupBy(\DB::raw('DATE(created_at)'))
                        ->orderByRaw('DATE(created_at)')
                        ->limit(7)
                        ->pluck('count')
                        ->toArray()
                ),

            Stat::make('Total Applications', Application::count())
                ->description(Application::where('status', 'pending')->count() . ' pending review')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('warning')
                ->chart(
                    Application::selectRaw('COUNT(*) as count')
                        ->groupBy(\DB::raw('DATE(applied_at)'))
                        ->orderByRaw('DATE(applied_at)')
                        ->limit(7)
                        ->pluck('count')
                        ->toArray()
                ),

            Stat::make('Registered Users', User::count())
                ->description('Total registered students')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('Blog Posts', BlogPost::count())
                ->description(BlogPost::published()->count() . ' published')
                ->descriptionIcon('heroicon-m-newspaper')
                ->color('info'),

            Stat::make('Approved Applications', Application::where('status', 'approved')->count())
                ->description('Successfully approved')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),

            Stat::make('Expiring Soon', Scholarship::active()
                    ->where('deadline', '<=', now()->addDays(7))
                    ->where('deadline', '>=', now())
                    ->count())
                ->description('Deadlines in next 7 days')
                ->descriptionIcon('heroicon-m-clock')
                ->color('danger'),
        ];
    }
}
