<?php
/**
 * 雪花算法算法配置
 *
 * @author herry@<yuandeng@aliyun.com>
 * @version 1.0
 * @copyright © 2020 MuCTS.com All Rights Reserved.
 */
return [
    /*
     |--------------------------------------------------------------------------
     | Snowflake Epoch
     |--------------------------------------------------------------------------
     |
     | Here you may configure the log settings for snowflake. Set the date
     | the application was develop started. Don't set the date of the future.
     | If service starts to move, don't change.
     |
     | Available Settings: Y-m-d H:i:s.f
     |
     | 在这里，您可以为雪花算法开始日期配置设置。设置应用程序开发启动的日期，不要设定未来的日期。
     | 注：如果服务已经上线，请不要再做调整。
     |
     | 设置格式：Y-m-d H:i:s.f
     */
    'tw_epoch' => env('SNOWFLAKE_TW_EPOCH', '2020-01-01 00:00:00.0'),
    /*
     |--------------------------------------------------------------------------
     | Snowflake Bits
     |--------------------------------------------------------------------------
     | Here you may configure the worker id bits settings for snowflake.
     | Set snowflake Id to reserve worker id bits, the default is 5 bits.
     | If service starts to move, don't change.
     |
     | 在这里，您可以为机器设置预留位数，默认：5 bits
     | 注：如果服务已经上线，请不要再做调整。
     */
    'worker_id_bits' => env('SNOWFLAKE_WORKER_ID_BITS', 5),
    /*
     |--------------------------------------------------------------------------
     | Snowflake Bits
     |--------------------------------------------------------------------------
     | Here you may configure the data center id bits settings for snowflake.
     | Set snowflake Id to reserve data center id bits, the default is 5 bits.
     | If service starts to move, don't change.
     |
     | 在这里，您可以为数据标识设置预留位数，默认：5 bits
     | 注：如果服务已经上线，请不要再做调整。
     */
    'data_center_id_bits' => env('SNOWFLAKE_DATA_CENTER_ID_BITS', 5),
    /*
     |--------------------------------------------------------------------------
     | Snowflake Bits
     |--------------------------------------------------------------------------
     | Here you may configure the sequence bits settings for snowflake.
     | Set snowflake Id to reserve sequence bits, the default is 12 bits.
     | If service starts to move, don't change.
     |
     | 在这里，您可以为计数顺序号预留位数，默认：12 bits
     | 注：如果服务已经上线，请不要再做调整。
     */
    'sequence_bits' => env('SNOWFLAKE_SEQUENCE_BITS', 12),
    /*
     |--------------------------------------------------------------------------
     | Snowflake Configuration
     |--------------------------------------------------------------------------
     | Here you may configure the log settings for snowflake.
     | If you are using multiple servers, please assign unique
     | ID for Snowflake. The relevant setting cannot be greater
     | than the maximum value that the Bits you set can store
     |
     | 在这里你可以设置当前机器的机器Id和数据中心ID
     | 注：相关设置不能大于您所设置的Bits所能存储的最大值
     */
    'worker_id' => env('SNOWFLAKE_WORKER_ID', 1),
    'data_center_id' => env('SNOWFLAKE_DATA_CENTER_ID', 1),
];