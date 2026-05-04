<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (!app()->environment('local') || str_contains(request()->getHost(), 'scolarship.pnpmyanmar.com')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}
