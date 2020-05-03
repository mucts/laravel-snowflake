<?php
/**
 * 雪花算法 Facade
 *
 * @author herry@<yuandeng@aliyun.com>
 * @version 1.0
 * @copyright © 2020 MuCTS.com All Rights Reserved.
 */

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