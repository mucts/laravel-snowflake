<?php


namespace MuCTS\LaravelSnowflake\Facades;


use Illuminate\Support\Facades\Facade;

/**
 * Class Snowflake
 *
 * @method static string next()
 * @package MuCTS\LaravelSnowflake\Facades
 */
class Snowflake extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'snowflake';
    }
}