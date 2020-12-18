<?php

declare(strict_types=1);

namespace chaser\reactor;

/**
 * 事件反应抽象类
 *
 * @package chaser\reactor
 */
abstract class ReactorAbstract implements ReactorInterface
{
    /**
     * 侦听套接字事件响应集合
     *
     * @var array[]
     */
    protected array $fdEvents = [
        self::EV_READ => [],
        self::EV_WRITE => []
    ];

    /**
     * 侦听信号事件响应集合
     *
     * @var array
     */
    protected array $signalEvents = [];

    /**
     * 侦听定时器事件响应集合
     *
     * @var array
     */
    protected array $timerEvents = [];

    /**
     * 定时器ID
     *
     * @var int
     */
    protected int $timerId = 0;

    /**
     * 添加流事件侦听器
     *
     * @param resource $fd
     * @param int $flag
     * @param callable $callback
     * @return bool
     */
    abstract public function addFd($fd, int $flag, callable $callback): bool;

    /**
     * 移除流事件侦听器
     *
     * @param resource $fd
     * @param int $flag
     * @return bool
     */
    abstract public function delFd($fd, int $flag): bool;

    /**
     * 添加信号事件侦听器
     *
     * @param int $signal
     * @param callable $callback
     * @return bool
     */
    abstract public function addSignal(int $signal, callable $callback): bool;

    /**
     * 移除信号事件侦听器
     *
     * @param int $signal
     * @return bool
     */
    abstract public function delSignal(int $signal): bool;

    /**
     * 添加定时器事件侦听器
     *
     * @param int $seconds
     * @param callable $callback
     * @param bool $once
     * @return int|false
     */
    abstract public function addTimer(int $seconds, callable $callback, bool $once = false);

    /**
     * 移除定时器事件侦听器
     *
     * @param int $timerId
     * @return bool
     */
    abstract public function delTimer(int $timerId): bool;

    /**
     * @inheritDoc
     */
    public function add($fd, int $flag, callable $callback)
    {
        switch ($flag) {
            case self::EV_READ:
            case self::EV_WRITE:
                return $this->addFd($fd, $flag, $callback);
            case self::EV_SIGNAL:
                return $this->addSignal($fd, $callback);
            case self::EV_TIMER:
                return $this->addTimer($fd, $callback, false);
            case self::EV_TIMER_ONCE:
                return $this->addTimer($fd, $callback, true);
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function del($fd, int $flag): bool
    {
        switch ($flag) {
            case static::EV_READ:
            case static::EV_WRITE:
                return $this->delFd($fd, $flag);
            case self::EV_SIGNAL:
                return $this->delSignal($fd);
            case self::EV_TIMER:
            case self::EV_TIMER_ONCE;
                return $this->delTimer($fd);
        }
        return false;
    }

    /**
     * 生成定时器ID
     *
     * @return int
     */
    protected function makeTimerId(): int
    {
        return ++$this->timerId;
    }

    /**
     * 流事件侦听器移除模型
     *
     * @param int $flag
     * @param int $key
     * @param callable $callback
     * @return bool
     */
    protected function delFdEventModel(int $flag, int $key, callable $callback): bool
    {
        $del = true;

        if (isset($this->fdEvents[$flag][$key])) {
            if ($callback($this->fdEvents[$flag][$key])) {
                unset($this->fdEvents[$flag][$key]);
            } else {
                $del = false;
            }
        }

        return $del;
    }

    /**
     * 信号事件侦听器移除模型
     *
     * @param int $key
     * @param callable $callback
     * @return bool
     */
    protected function delSignalEventModel(int $key, callable $callback): bool
    {
        $del = true;

        if (isset($this->timerEvents[$key])) {
            if ($callback($this->timerEvents[$key])) {
                unset($this->timerEvents[$key]);
            } else {
                $del = false;
            }
        }

        return $del;
    }

    /**
     * 定时器事件侦听器移除模型
     *
     * @param int $key
     * @param callable $callback
     * @return bool
     */
    protected function delTimerEventModel(int $key, callable $callback): bool
    {
        $del = true;

        if (isset($this->signalEvents[$key])) {
            if ($callback($this->signalEvents[$key][0])) {
                unset($this->signalEvents[$key]);
            } else {
                $del = false;
            }
        }

        return $del;
    }

    /**
     * @inheritDoc
     */
    public function getTimerCount()
    {
        return count($this->timerEvents);
    }
}
