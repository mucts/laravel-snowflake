<?php
/**
 * Snowflake
 *
 * SnowFlake的结构如下(每部分用-分开):
 * 0 - 0000000000 0000000000 0000000000 0000000000 0 - 00000 - 00000 - 000000000000
 * 1位标识，由于long基本类型在Java中是带符号的，最高位是符号位，正数是0，负数是1，所以id一般是正数，最高位是0
 * 41位时间截(毫秒级)，注意，41位时间截不是存储当前时间的时间截，而是存储时间截的差值（当前时间截 - 开始时间截)
 * 得到的值），这里的的开始时间截，一般是我们的id生成器开始使用的时间，由我们程序来指定的（如下下面程序IdWorker类的startTime属性）。41位的时间截，可以使用69年，年T = (1L << 41) / (1000L * 60 * 60 * 24 * 365) = 69
 * 10位的数据机器位，可以部署在1024个节点，包括5位datacenterId和5位workerId
 * 12位序列，毫秒内的计数，12位的计数顺序号支持每个节点每毫秒(同一机器，同一时间截)产生4096个ID序号
 * 加起来刚好64位，为一个Long型。
 * SnowFlake的优点是，整体上按照时间自增排序，并且整个分布式系统内不会产生ID碰撞(由数据中心ID和机器ID作区分)，并且效率较高，经测试，SnowFlake每秒能够产生26万ID左右。
 *
 * @author herry<yuandeng@aliyun.com>
 * @version 1.0
 * @copyright © 2020 MuCTS.com All Rights Reserved.
 */

namespace MuCTS\Laravel\Snowflake;

use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Class Snowflake
 * @package MuCTS\Laravel\Snowflake
 */
final class Snowflake
{
    /** 开始时间截 (2020-01-01) */
    private int $twEpoch;

    /** 机器id所占的位数 */
    private int $workerIdBits;

    /** 数据标识id所占的位数 */
    private int $dataCenterIdBits;

    /** 支持的最大机器id，结果是31 (这个移位算法可以很快的计算出几位二进制数所能表示的最大十进制数) */
    private int $maxWorkerId;

    /** 支持的最大数据标识id，结果是31 */
    private int $maxDataCenterId;


    /** 序列在id中占的位数 */
    private int $sequenceBits;

    /** 机器ID向左移12位 */
    private int $workerIdShift;

    /** 数据标识id向左移17位(12+5) */
    private int $dataCenterIdShift;

    /** 时间截向左移22位(5+5+12) */
    private int $timestampLeftShift;

    /** 生成序列的掩码，这里为4095 (0b111111111111=0xfff=4095) */
    private int $sequenceMask;

    /** 工作机器ID(0~31) */
    private int $workerId;

    /** 数据中心ID(0~31) */
    private int $dataCenterId;

    /**
     * 构造函数
     * @param array|null $config
     * @throws Exception
     */
    public function __construct(?array $config = null)
    {
        $config = $config ?? config('snowflake');
        $this->twEpoch = strtotime($config['tw_epoch']) * 1000;
        $this->workerIdBits = $config['worker_id_bits'];
        $this->maxWorkerId = -1 ^ (-1 << $this->workerIdBits);
        $this->dataCenterIdBits = $config['data_center_id_bits'];
        $this->maxDataCenterId = -1 ^ (-1 << $this->dataCenterIdBits);
        $this->sequenceBits = $config['sequence_bits'];
        $this->workerIdShift = $this->sequenceBits;
        $this->dataCenterIdShift = $this->sequenceBits + $this->workerIdBits;
        $this->timestampLeftShift = $this->sequenceBits + $this->workerIdBits + $this->dataCenterIdBits;
        $this->sequenceMask = -1 ^ (-1 << $this->sequenceBits);

        $this->workerId = $config['worker_id'];
        $this->dataCenterId = $config['data_center_id'];


        if ($this->workerId > $this->maxWorkerId || $this->workerId < 0) {
            throw new Exception(sprintf('worker Id can\'t be greater than %d or less than 0', $this->maxWorkerId));
        } elseif ($this->dataCenterId > $this->maxDataCenterId || $this->dataCenterId < 0) {
            throw new Exception(sprintf('data center Id can\'t be greater than %d or less than 0', $this->maxDataCenterId));
        }
    }

    private string $lockKey = 'MCTS:SF:LOCK';

    /**
     * 获得下一个ID (该方法是线程安全的)
     * @return string
     * @throws Exception
     */
    public function next(): ?string
    {
        return Cache::lock($this->lockKey)->get(function () {
            $timestamp = $this->timeGen();

            // 如果当前时间小于上一次ID生成的时间戳，说明系统时钟回退过这个时候应当抛出异常
            if ($timestamp < $this->getLastTimestamp()) {
                throw new Exception(
                    sprintf("Clock moved backwards.  Refusing to generate id for %d milliseconds", $this->getLastTimestamp() - $timestamp));
            }

            // 如果是同一时间生成的，则进行毫秒内序列
            $sequence = $this->getSequence();
            // 毫秒内序列溢出，阻塞到下一个毫秒,获得新的时间戳
            while ($this->getLastTimestamp() == $timestamp && $sequence == 0) {
                $timestamp = $this->tilNextMillis($timestamp);
                $sequence = $this->getSequence();
            }

            //上次生成ID的时间截
            $this->setLastTimestamp($timestamp);

            $gmpTimestamp = gmp_init($this->leftShift(bcsub($timestamp, $this->twEpoch), $this->timestampLeftShift));
            $gmpDataCenterId = gmp_init($this->leftShift($this->dataCenterId, $this->dataCenterIdShift));
            $gmpWorkerId = gmp_init($this->leftShift($this->workerId, $this->workerIdShift));
            $gmpSequence = gmp_init($sequence);

            return gmp_strval(gmp_or(gmp_or(gmp_or($gmpTimestamp, $gmpDataCenterId), $gmpWorkerId), $gmpSequence));
        });
    }

    private string $lastTimestampKey = 'MCTS:SF:LT';

    /**
     * 上次生成ID的时间截
     */
    private function getLastTimestamp(): ?int
    {
        return Cache::rememberForever($this->lastTimestampKey, function () {
            return -1;
        });
    }

    private function setLastTimestamp(int $timestamp): void
    {
        Cache::forever($this->lastTimestampKey, $timestamp);
    }

    private string $sequenceKey = 'MCTS:SF:SQ';

    /**
     * 毫秒内序列(0~4095)
     * @return int
     */
    private function getSequence(): int
    {
        $tts = Carbon::now()->addMicroseconds();
        $sequence = Cache::remember($this->sequenceKey, $tts, function () {
            return -1;
        });
        $sequence = ($sequence + 1) & $this->sequenceMask;
        Cache::put($this->sequenceKey, $sequence, $tts);
        return $sequence;
    }

    /**
     * 阻塞到下一个毫秒，直到获得新的时间戳
     * @param int $lastTimestamp 上次生成ID的时间截
     * @return int 当前时间戳
     */
    protected function tilNextMillis(int $lastTimestamp): int
    {
        $timestamp = $this->timeGen();
        while ($timestamp <= $lastTimestamp) {
            $timestamp = $this->timeGen();
        }
        return $timestamp;
    }

    /**
     * 返回以毫秒为单位的当前时间
     * @return int 当前时间(毫秒)
     */
    protected function timeGen(): int
    {
        return microtime(true) * 1000;
    }

    protected function leftShift(int $a, int $b): string
    {
        return bcmul($a, bcpow(2, $b));
    }
}