<?php

namespace Mralston\Eav\Providers;

use Illuminate\Support\ServiceProvider;

class EavServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
//        $this->mergeConfigFrom(__DIR__ . '/../../config/config.php', 'eav');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

//        if ($this->app->runningInConsole()) {
//            $this->publishes([
//                __DIR__ . '/../../config/config.php' => config_path('eav.php'),
//            ], 'eav-config');
//        }
    }
}
