<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        // Limiter bernama agar tiap aksi punya hitungan sendiri per pengguna.
        $by = fn(Request $request) => $request->user()?->id ?: $request->ip();
        RateLimiter::for('hunian-chat', fn(Request $request) => Limit::perMinute(30)->by($by($request)));
        RateLimiter::for('hunian-ticket', fn(Request $request) => Limit::perMinute(10)->by($by($request)));
        RateLimiter::for('hunian-password', fn(Request $request) => Limit::perMinute(5)->by($by($request)));
    }
}
