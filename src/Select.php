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
     * 中断回路
     *
     * @var bool
     */
    protected bool $break;

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
    protected function addReadData(int $intFd, $fd, callable $callback)
    {
        $this->fds[self::EV_READ][$intFd] = $fd;
        return $callback;
    }

    /**
     * @inheritDoc
     */
    protected function addWriteData(int $intFd, $fd, callable $callback)
    {
        $this->fds[self::EV_WRITE][$intFd] = $fd;
        return $callback;
    }

    /**
     * @inheritDoc
     */
    protected function addSignalData(int $key, int $signal, callable $callback)
    {
        return pcntl_signal($signal, [$this, 'signalCallback']) ? $callback : false;
    }

    /**
     * @inheritDoc
     */
    protected function addIntervalData(int $timerId, int $seconds, callable $callback)
    {
        return $this->addTimerData($timerId, self::EV_INTERVAL, $seconds, $callback);
    }

    /**
     * @inheritDoc
     */
    protected function addTimeoutData(int $timerId, int $seconds, callable $callback)
    {
        return $this->addTimerData($timerId, self::EV_TIMEOUT, $seconds, $callback);
    }

    /**
     * 获取定时器事件添加数据
     *
     * @param int $timerId
     * @param int $flag
     * @param int $seconds
     * @param callable $callback
     * @return array
     */
    private function addTimerData(int $timerId, int $flag, int $seconds, callable $callback)
    {
        // 加入队列：定时器ID、优先级（负的运行时间）
        $runtime = microtime(true) + $seconds;
        $this->scheduler->insert([$timerId, $flag], -$runtime);

        // 设置流事件等待时间，防止闹铃超时
        $this->selectTimeout = min($this->selectTimeout, $seconds * 1000000);

        // 返回定时器数据
        return [$seconds, $callback];
    }

    /**
     * @inheritDoc
     */
    protected function delCallback(int $flag, int $key): bool
    {
        switch ($flag) {
            case self::EV_READ:
            case self::EV_WRITE:
                unset($this->fds[$flag][$key]);
                return true;
            case self::EV_SIGNAL:
                return pcntl_signal($key, SIG_IGN);
            case self::EV_INTERVAL:
            case self::EV_TIMEOUT:
                return true;
        }
        return false;
    }

    /**
     * 主回路
     */
    public function loop()
    {
        $this->break = false;

        while (1) {
            // 处理等待信号
            pcntl_signal_dispatch();

            if ($this->break) {
                break;
            }

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
                $this->readCallback($read);
            }
            foreach ($writes as $write) {
                $this->writeCallback($write);
            }
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
            [$flag, $timerId] = $data['data'];
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
            $this->timerCallback($timerId, $flag, $now);
        }

        // 无定时器，重置流事件等待时间
        $this->selectTimeout = self::SELECT_TIMEOUT;
    }

    /**
     * 定时器事件处理程序
     *
     * @param int $timerId
     * @param int $flag
     * @param float $now
     */
    protected function timerCallback(int $timerId, int $flag, float $now)
    {
        if (isset($this->events[$flag][$timerId])) {

            // 定时器信息：间隔秒数、回调程序
            [$seconds, $callback] = $this->events[$flag][$timerId];

            // 持续性判断：单次，清除任务事件；持续，追加队列
            if ($flag === self::EV_TIMEOUT) {
                $this->delTimeout($timerId);
            } else {
                $nextRuntime = $now + $seconds;
                $this->scheduler->insert([$timerId, $flag], -$nextRuntime);
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
     * 流事件处理程序
     *
     * @param resource $fd
     */
    protected function readCallback($fd)
    {
        $this->events[self::EV_READ][(int)$fd]($fd);
    }

    /**
     * 流事件处理程序
     *
     * @param resource $fd
     */
    protected function writeCallback($fd)
    {
        $this->events[self::EV_WRITE][(int)$fd]($fd);
    }

    /**
     * 信号事件处理程序
     *
     * @param int $signal
     */
    public function signalCallback(int $signal)
    {
        $this->events[self::EV_SIGNAL][$signal]($signal);
    }

    /**
     * 破坏回路
     */
    public function destroy()
    {
        $this->break = true;
    }

    /**
     * @inheritDoc
     */
    protected function clearAllTimer()
    {
        $this->initScheduler();
        parent::clearAllTimer();
    }
}
