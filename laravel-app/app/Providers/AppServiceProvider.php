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
        // Dynamic configuration from settings table
        if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
            $apiKey = \App\Models\Setting::getVal('fonnte_api_key');
            if ($apiKey) {
                config(['services.fonnte.api_key' => $apiKey]);
            }
        }
    }
}
