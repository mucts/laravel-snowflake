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

use DateTime;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Class Snowflake
 * @package MuCTS\Laravel\Snowflake
 */
final class Snowflake
{
    /** @var Carbon 开始时间截 (2020-01-01) */
    private Carbon $twEpoch;

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
        $this->setTwEpoch($config['tw_epoch']);
        $this->setWorkerIdBits($config['worker_id_bits']);
        $this->setDataCenterIdBits($config['data_center_id_bits']);
        $this->setSequenceBits($config['sequence_bits']);

        $this->setWorkerId($config['worker_id']);
        $this->setDataCenterId($config['data_center_id']);
    }

    /**
     * Set snowflake start epoch carbon
     *
     * @param Carbon|DateTime|int|string $twEpoch
     * @return $this
     */
    public function setTwEpoch($twEpoch): self
    {
        if ($twEpoch instanceof DateTime) {
            $twEpoch = Carbon::createFromTimestamp($twEpoch->getTimestamp())->setMillisecond(0);
        } elseif (is_int($twEpoch)) {
            $twEpoch = Carbon::createFromTimestampMs($twEpoch);
        } elseif (is_string($twEpoch)) {
            $twEpoch = Carbon::parse($twEpoch);
        }
        $this->twEpoch = $twEpoch;
        return $this;
    }

    /**
     * Set worker id bits
     *
     * @param int $workerIdBits
     * @return $this
     */
    public function setWorkerIdBits(?int $workerIdBits): self
    {
        $this->workerIdBits = $workerIdBits ?? 5;
        $this->maxWorkerId = -1 ^ (-1 << $this->workerIdBits);
        return $this;
    }

    /**
     * Set data center id bits
     *
     * @param int|null $dataCenterIdBits
     * @return $this
     */
    public function setDataCenterIdBits(?int $dataCenterIdBits): self
    {
        $this->dataCenterIdBits = $dataCenterIdBits ?? 5;
        $this->maxDataCenterId = -1 ^ (-1 << $this->dataCenterIdBits);
        return $this;
    }

    /**
     * Set sequence bits
     *
     * @param int|null $sequenceBits
     * @return $this
     */
    public function setSequenceBits(?int $sequenceBits): self
    {
        $this->sequenceBits = $sequenceBits ?? 12;
        $this->workerIdShift = $this->sequenceBits;
        $this->dataCenterIdShift = $this->sequenceBits + $this->workerIdBits;
        $this->timestampLeftShift = $this->sequenceBits + $this->workerIdBits + $this->dataCenterIdBits;
        $this->sequenceMask = -1 ^ (-1 << $this->sequenceBits);
        return $this;
    }

    /**
     * Set worker id
     *
     * @param int|null $workerId
     * @return $this
     * @throws Exception
     */
    public function setWorkerId(?int $workerId): self
    {
        $this->workerId = $workerId ?? 1;
        if ($this->workerId > $this->maxWorkerId || $this->workerId < 0) {
            throw new Exception(sprintf('worker Id can\'t be greater than %d or less than 0', $this->maxWorkerId));
        }
        return $this;
    }

    /**
     * Set data center id
     *
     * @param int|null $dataCenterId
     * @return $this
     * @throws Exception
     */
    public function setDataCenterId(?int $dataCenterId): self
    {
        $this->dataCenterId = $dataCenterId ?? 1;
        if ($this->dataCenterId > $this->maxDataCenterId || $this->dataCenterId < 0) {
            throw new Exception(sprintf('data center Id can\'t be greater than %d or less than 0', $this->maxDataCenterId));
        }
        return $this;
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
            if ($timestamp->lt($this->getLastTimestamp())) {
                throw new Exception(
                    sprintf("Clock moved backwards.  Refusing to generate id for %d milliseconds", $this->getLastTimestamp() - $timestamp));
            }

            // 如果是同一时间生成的，则进行毫秒内序列
            $sequence = $this->getSequence($timestamp);
            // 毫秒内序列溢出，阻塞到下一个毫秒,获得新的时间戳
            while ($timestamp->eq($this->getLastTimestamp()) && $sequence == 0) {
                $timestamp = $this->tilNextMillis($timestamp);
                $sequence = $this->getSequence($timestamp);
            }

            //上次生成ID的时间截
            $this->setLastTimestamp($timestamp);

            $gmpTimestamp = gmp_init($this->leftShift($timestamp->diffInMilliseconds($this->twEpoch), $this->timestampLeftShift));
            $gmpDataCenterId = gmp_init($this->leftShift($this->dataCenterId, $this->dataCenterIdShift));
            $gmpWorkerId = gmp_init($this->leftShift($this->workerId, $this->workerIdShift));
            $gmpSequence = gmp_init($sequence);

            return gmp_strval(gmp_or(gmp_or(gmp_or($gmpTimestamp, $gmpDataCenterId), $gmpWorkerId), $gmpSequence));
        });
    }

    /**
     * 解析ID信息
     *
     * @param string $snowflakeId
     * @return Collection
     */
    public function info(string $snowflakeId): Collection
    {
        $snowflakeId = gmp_strval($snowflakeId, 2);
        $len = strlen($snowflakeId);
        $sequenceStart = $len < $this->workerIdShift ? 0 : $len - $this->workerIdShift;
        $workerStart = $len < $this->dataCenterIdShift ? 0 : $len - $this->dataCenterIdShift;
        $timeStart = $len < $this->timestampLeftShift ? 0 : $len - $this->timestampLeftShift;
        $sequence = substr($snowflakeId, $sequenceStart);
        $workerId = $sequenceStart == 0 ? 0 : substr($snowflakeId, $workerStart, $sequenceStart - $workerStart);
        $dataCenterId = $workerStart == 0 ? 0 : substr($snowflakeId, $timeStart, $workerStart - $timeStart);
        $time = $timeStart == 0 ? 0 : substr($snowflakeId, 0, $timeStart);
        $items = collect(['snowflake_id' => gmp_strval('0b' . $snowflakeId)]);
        $items->put('sequence', gmp_intval(gmp_init('0b' . $sequence)));
        $items->put('worker_id', gmp_intval(gmp_init('0b' . $workerId)));
        $items->put('data_center_id', gmp_intval(gmp_init('0b' . $dataCenterId)));
        $items->put('datetime', $this->twEpoch->clone()->addMilliseconds(gmp_intval(gmp_init('0b' . $time))));
        return $items;
    }

    private string $lastTimestampKey = 'MCTS:SF:LT';

    /**
     * 上次生成ID的时间截
     */
    private function getLastTimestamp(): ?Carbon
    {
        Cache::forget($this->lastTimestampKey);
        return Cache::rememberForever($this->lastTimestampKey, function () {
            return null;
        });
    }

    private function setLastTimestamp(Carbon $timestamp): self
    {
        Cache::forever($this->lastTimestampKey, $timestamp);
        return $this;
    }

    private string $sequenceKey = 'MCTS:SF:SQ:';

    /**
     * 毫秒内序列(0~4095)
     * @param Carbon $tts
     * @return int
     */
    private function getSequence(Carbon $tts): int
    {
        $key = $this->sequenceKey . $tts->millisecond;
        $sequence = Cache::remember($key, 1, function () {
            return -1;
        });
        $sequence = ($sequence + 1) & $this->sequenceMask;
        Cache::put($key, $sequence, 1);
        return $sequence;
    }

    /**
     * 阻塞到下一个毫秒，直到获得新的时间戳
     * @param Carbon $lastTimestamp 上次生成ID的时间截
     * @return Carbon 当前时间戳
     */
    protected function tilNextMillis(Carbon $lastTimestamp): Carbon
    {
        $timestamp = $this->timeGen();
        while ($timestamp->lte($lastTimestamp)) {
            $timestamp = $this->timeGen();
        }
        return $timestamp;
    }

    /**
     * 返回以毫秒为单位的当前时间
     * @return Carbon 当前时间(毫秒)
     */
    protected function timeGen(): Carbon
    {
        return Carbon::now();
    }

    protected function leftShift(int $a, int $b): string
    {
        return bcmul($a, bcpow(2, $b));
    }
}