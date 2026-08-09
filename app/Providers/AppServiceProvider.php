<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

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
        RateLimiter::for('wallet-auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('tip-api', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        \Illuminate\Support\Facades\View::composer('*', function ($view) {
            if (!\Illuminate\Support\Facades\Schema::hasTable('blockchains')) {
                $view->with('blockchains', collect());
                return;
            }

            $view->with('blockchains', \App\Models\Blockchain::where('active', 1)->get());
        });
    }
}
