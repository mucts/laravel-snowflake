<?php

return [
    'tw_epoch' => env('TW_EPOCH', '2020-01-01 00:00:00'),// 开始时间截 (默认2020-01-01)
    'worker_id_bits' => env('WORKER_ID_BITS', 5),// 机器id所占的位数
    'data_center_id_bits' => env('DATA_CENTER_ID_BITS', 5),// 数据标识id所占的位数
    'sequence_bits' => env('SEQUENCE_BITS', 12),// 序列在id中占的位数
    'worker_id' => env('WORKER_ID', 1),// 工作机器ID(0~31)
    'data_center_id' => env('data_center_id', 1),// 数据中心ID(0~31)
];