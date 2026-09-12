<?php

namespace App\Providers;

use App\Services\ContextService;
use App\Support\RebaseCachedStoragePaths;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        RebaseCachedStoragePaths::apply();

        $this->app->singleton(ContextService::class, function ($app) {
            return new ContextService;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
