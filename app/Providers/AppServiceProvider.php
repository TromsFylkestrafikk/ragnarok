<?php

namespace App\Providers;

use App\Services\RagnarokSinks;
use App\Services\Updater;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public $singletons = [
        RagnarokSinks::class => RagnarokSinks::class,
        Updater::class => Updater::class,
    ];

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
        //
    }
}
