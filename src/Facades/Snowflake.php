<?php
/**
 * 雪花算法 Facade
 *
 * @author herry@<yuandeng@aliyun.com>
 * @version 1.0
 * @copyright © 2020 MuCTS.com All Rights Reserved.
 */

namespace MuCTS\Laravel\Snowflake\Facades;


use Illuminate\Support\Facades\Facade;

/**
 * Class Snowflake
 *
 * @method static string next()
 * @method static \Illuminate\Support\Collection info(string $snowflakeId)
 * @method static \MuCTS\Laravel\Snowflake\Snowflake setTwEpoch($twEpoch)
 * @method static \MuCTS\Laravel\Snowflake\Snowflake setWorkerIdBits(?int $workerIdBits)
 * @method static \MuCTS\Laravel\Snowflake\Snowflake setDataCenterIdBits(?int $dataCenterIdBits)
 * @method static \MuCTS\Laravel\Snowflake\Snowflake setSequenceBits(?int $sequenceBits)
 * @method static \MuCTS\Laravel\Snowflake\Snowflake setWorkerId(?int $workerId)
 * @method static \MuCTS\Laravel\Snowflake\Snowflake setDataCenterId(?int $dataCenterId)
 * @package MuCTS\Laravel\Snowflake\Facades
 */
class Snowflake extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'snowflake';
    }
}