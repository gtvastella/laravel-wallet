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
        // No interface bindings needed anymore
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
