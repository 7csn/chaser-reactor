<?php

namespace chaser\reactor;

use SplPriorityQueue;
use Throwable;

/**
 * 备用事件反应类
 *
 * @package chaser\reactor
 */
class Select extends ReactorAbstract
{
    /**
     * 流事件默认等待时间（微秒）
     */
    public const SELECT_TIMEOUT = 100000000;

    /**
     * 定时器优先级队列
     *
     * @var SplPriorityQueue
     */
    protected SplPriorityQueue $scheduler;

    /**
     * 流事件等待时间（微秒）
     *
     * @var int
     */
    protected int $selectTimeout = self::SELECT_TIMEOUT;

    /**
     * 侦听事件流集合
     *
     * @var resource[][]
     */
    protected array $fds = [
        self::EV_READ => [],
        self::EV_WRITE => []
    ];

    /**
     * 初始化队列
     */
    public function __construct()
    {
        $this->initScheduler();
    }

    /**
     * 初始化定时器优先级队列
     */
    protected function initScheduler()
    {
        $this->scheduler = new SplPriorityQueue();
        $this->scheduler->setExtractFlags(SplPriorityQueue::EXTR_BOTH);
    }

    /**
     * @inheritDoc
     */
    public function addFd($fd, int $flag, callable $callback): bool
    {
        if (count($this->fds[$flag]) >= 1024) {
            echo("Upper limit 1024 connections" . PHP_EOL);
        }
        $intFd = (int)$fd;
        $this->fdEvents[$flag][$intFd] = $callback;
        $this->fds[$flag][$intFd] = $fd;

        return true;
    }

    /**
     * @inheritDoc
     */
    public function delFd($fd, int $flag): bool
    {
        $intFd = (int)$fd;
        unset($this->fdEvents[$flag][$intFd], $this->fds[$flag][$intFd]);
        return true;
    }

    /**
     * @inheritDoc
     */
    public function addSignal(int $signal, callable $callback): bool
    {
        $add = pcntl_signal($signal, fn($signal) => $this->signalHandle($signal));

        if ($add) {
            $this->signalEvents[$signal] = $callback;
        }

        return $add;
    }

    /**
     * @inheritDoc
     */
    public function delSignal(int $signal): bool
    {
        return $this->delSignalEventModel($signal, fn() => pcntl_signal($signal, SIG_IGN));
    }

    /**
     * @inheritDoc
     */
    public function addTimer(int $seconds, callable $callback, bool $once = false)
    {
        // 加入队列：定时器ID、优先级（负的运行时间）
        $timeId = $this->makeTimerId();
        $runtime = microtime(true) + $seconds;
        $this->scheduler->insert($timeId, -$runtime);

        // 记录定时器信息：间隔秒数、回调程序、是否一次性
        $this->timerEvents[$timeId] = [$seconds, $callback, $once];

        // 设置流事件等待时间，防止闹铃超时
        $this->selectTimeout = min($this->selectTimeout, $seconds * 1000000);

        // 返回定时器ID
        return $timeId;
    }

    /**
     * @inheritDoc
     */
    public function delTimer(int $timerId): bool
    {
        return $this->delTimerEventModel($timerId, fn() => true);
    }

    /**
     * 信号事件处理程序
     *
     * @param int $signal
     */
    protected function signalHandle(int $signal)
    {
        $this->signalEvents[$signal]($signal);
    }

    /**
     * 流事件处理程序
     *
     * @param resource $fd
     * @param int $flag
     */
    protected function fdHandle($fd, int $flag)
    {
        $intFd = (int)$fd;
        if (isset($this->fdEvents[$flag][$intFd])) {
            $this->fdEvents[$flag][$intFd]($fd);
        }
    }

    /**
     * 闹钟走时：从前往后检测闹铃
     */
    protected function tick()
    {
        while (!$this->scheduler->isEmpty()) {

            // 查看最前闹铃
            $data = $this->scheduler->top();
            $timerId = $data['data'];
            $nextRuntime = -$data['priority'];

            // 计算剩余时间
            $now = microtime(true);
            $leftTime = $nextRuntime - $now;

            // 闹铃未响，则设置流事件等待时间
            if ($leftTime > 0) {
                $this->selectTimeout = min($leftTime * 1000000, self::SELECT_TIMEOUT);
                return;
            }

            // 闹铃生效，清出队列
            $this->scheduler->extract();

            // 闹铃触发
            $this->timerHandle($timerId, $now);
        }

        // 无定时器，重置流事件等待时间
        $this->selectTimeout = self::SELECT_TIMEOUT;
    }

    /**
     * 定时器事件处理程序
     *
     * @param int $timerId
     * @param float $now
     */
    protected function timerHandle(int $timerId, float $now)
    {
        if (isset($this->timerEvents[$timerId])) {

            // 定时器信息：间隔秒数、回调程序、是否一次性
            [$seconds, $callback, $once] = $this->timerEvents[$timerId];

            // 持续性判断：单次，清除任务事件；持续，追加队列
            if ($once) {
                $this->delTimer($timerId);
            } else {
                $nextRuntime = $now + $seconds;
                $this->scheduler->insert($timerId, -$nextRuntime);
            }

            // 执行闹铃任务
            try {
                $callback($timerId);
            } catch (Throwable $e) {
                exit(250);
            }
        }
    }

    /**
     * 主回路
     */
    public function loop()
    {
        while (1) {
            // 处理等待信号
            pcntl_signal_dispatch();

            // 等待时间流数组：过渡变量，防止原数组被修改
            [self::EV_READ => $reads, self::EV_WRITE => $writes] = $this->fds;
            $excepts = [];

            if ($reads || $writes) {
                try {
                    // 等待事件发生：可读、可写、带外数据、信号
                    @stream_select($reads, $writes, $excepts, 0, $this->selectTimeout);
                } catch (Throwable $e) {
                }
            } else {
                usleep($this->selectTimeout);
            }

            // 闹钟走时
            $this->tick();

            // 事件侦听回调
            foreach ($reads as $read) {
                $this->fdHandle($read, self::EV_READ);
            }

            foreach ($writes as $write) {
                $this->fdHandle($write, self::EV_WRITE);
            }
        }
    }

    /**
     * 破坏回路
     */
    public function destroy()
    {
    }

    /**
     * 清空定时器事件侦听
     */
    public function clearAllTimer()
    {
        $this->initScheduler();
        $this->timerEvents = [];
    }
}
