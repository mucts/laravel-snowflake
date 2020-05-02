<?php

namespace MuCTS\LaravelSnowflake\Providers;

use Illuminate\Support\ServiceProvider;
use MuCTS\LaravelSnowflake\Snowflake;

class SnowflakeServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            dirname(__DIR__) . '../config/snowflake.php', 'snowflake'
        );
        $this->app->singleton('snowflake', function ($app) {
            return new Snowflake($app);
        });
    }

    public function boot()
    {
        if (!file_exists(config_path('snowflake.php'))) {
            $this->publishes([
                dirname(__DIR__) . '../config/snowflake.php' => config_path('snowflake.php'),
            ], 'config');
        }
    }

    public function provides()
    {
        return ['snowflake'];
    }
}