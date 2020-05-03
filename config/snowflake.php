<?php
/**
 * 雪花算法算法配置
 *
 * @author herry@<yuandeng@aliyun.com>
 * @version 1.0
 * @copyright © 2020 MuCTS.com All Rights Reserved.
 */
return [
    'tw_epoch' => env('SNOWFLAKE_TW_EPOCH', '2020-01-01 00:00:00'),// 开始时间截 (默认2020-01-01)
    'worker_id_bits' => env('SNOWFLAKE_WORKER_ID_BITS', 5),// 机器id所占的位数
    'data_center_id_bits' => env('SNOWFLAKE_DATA_CENTER_ID_BITS', 5),// 数据标识id所占的位数
    'sequence_bits' => env('SNOWFLAKE_SEQUENCE_BITS', 12),// 序列在id中占的位数
    'worker_id' => env('SNOWFLAKE_WORKER_ID', 1),// 工作机器ID(0~31)
    'data_center_id' => env('SNOWFLAKE_DATA_CENTER_ID', 1),// 数据中心ID(0~31)
];