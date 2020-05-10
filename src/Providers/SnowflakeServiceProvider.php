<?php
/**
 * 雪花算法 Provider
 *
 * @author herry@<yuandeng@aliyun.com>
 * @version 1.0
 * @copyright © 2020 MuCTS.com All Rights Reserved.
 */

namespace MuCTS\Laravel\Snowflake\Providers;

use Illuminate\Support\ServiceProvider;
use MuCTS\Laravel\Snowflake\Snowflake;

/**
 * Class SnowflakeServiceProvider
 * @package MuCTS\Laravel\Snowflake\Providers
 */
class SnowflakeServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            dirname(__DIR__) . '/../config/snowflake.php', 'snowflake'
        );
        $this->app->singleton('snowflake', function ($app) {
            return new Snowflake($app->config['snowflake']);
        });
    }

    public function boot()
    {
        if (!file_exists(config_path('snowflake.php'))) {
            $this->publishes([
                dirname(__DIR__) . '/../config/snowflake.php' => config_path('snowflake.php'),
            ], 'config');
        }
    }

    public function provides()
    {
        return ['snowflake'];
    }
}