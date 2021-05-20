<?php

declare(strict_types=1);

namespace chaser\reactor;

/**
 * 事件反应抽象基类
 *
 * @package chaser\reactor
 */
abstract class Reactor implements ReactorInterface
{
    /**
     * 事件集合
     *
     * @var array[]
     */
    protected array $events = [
        self::EV_READ => [],
        self::EV_WRITE => [],
        self::EV_SIGNAL => [],
        self::EV_INTERVAL => [],
        self::EV_TIMEOUT => []
    ];

    /**
     * 定时器事件ID
     *
     * @var int
     */
    protected int $timerId = 0;

    /**
     * @inheritDoc
     */
    public function add($fd, int $flag, callable $callback)
    {
        switch ($flag) {
            case self::EV_READ:
                return $this->addRead($fd, $callback);
            case self::EV_WRITE:
                return $this->addWrite($fd, $callback);
            case self::EV_SIGNAL:
                return $this->addSignal($fd, $callback);
            case self::EV_INTERVAL:
                return $this->addInterval($fd, $callback);
            case self::EV_TIMEOUT:
                return $this->addTimeout($fd, $callback);
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
                return $this->delRead($fd);
            case static::EV_WRITE:
                return $this->delWrite($fd);
            case self::EV_SIGNAL:
                return $this->delSignal($fd);
            case self::EV_INTERVAL:
                return $this->delInterval($fd);
            case self::EV_TIMEOUT;
                return $this->delTimeout($fd);
        }
        return false;
    }

    /**
     * 添加读事件侦听器
     *
     * @param resource $fd
     * @param callable $callback
     * @return bool
     */
    public function addRead($fd, callable $callback): bool
    {
        return $this->addModel(self::EV_READ, (int)$fd, $fd, $callback, [$this, 'addReadData']);
    }

    /**
     * 添加写事件侦听器
     *
     * @param resource $fd
     * @param callable $callback
     * @return bool
     */
    public function addWrite($fd, callable $callback): bool
    {
        return $this->addModel(self::EV_WRITE, (int)$fd, $fd, $callback, [$this, 'addWriteData']);
    }

    /**
     * 添加信号事件侦听器
     *
     * @param int $signal
     * @param callable $callback
     * @return bool
     */
    public function addSignal(int $signal, callable $callback): bool
    {
        return $this->addModel(self::EV_SIGNAL, $signal, $signal, $callback, [$this, 'addSignalData']);
    }

    /**
     * 添加周期性定时器事件侦听器
     *
     * @param int $seconds
     * @param callable $callback
     * @return int|false
     */
    public function addInterval(int $seconds, callable $callback)
    {
        $timerId = $this->makeTimerId();
        $add = $this->addModel(self::EV_INTERVAL, $timerId, $seconds, $callback, [$this, 'addIntervalData']);
        return $add ? $timerId : false;
    }

    /**
     * 添加一次性定时器事件侦听器
     *
     * @param int $seconds
     * @param callable $callback
     * @return int|false
     */
    public function addTimeout(int $seconds, callable $callback)
    {
        $timerId = $this->makeTimerId();
        $add = $this->addModel(self::EV_TIMEOUT, $timerId, $seconds, $callback, [$this, 'addTimeoutData']);
        return $add ? $timerId : false;
    }

    /**
     * 事件（读、写、信号）侦听器添加模型
     *
     * @param int $flag
     * @param int $key
     * @param mixed $fd
     * @param callable $callback
     * @param callable $addData
     * @return bool|int
     */
    protected function addModel(int $flag, int $key, $fd, callable $callback, callable $addData): bool
    {
        $add = true;

        if (!isset($this->events[$flag][$key])) {
            $data = $addData($key, $fd, $callback);
            if ($add = (bool)$data) {
                $this->events[$flag][$key] = $data;
            }
        }

        return $add;
    }

    /**
     * 获取写事件侦听器添加数据
     *
     * @param int $intFd
     * @param resource $fd
     * @param callable $callback
     * @return mixed
     */
    abstract protected function addReadData(int $intFd, $fd, callable $callback);

    /**
     * 获取读事件侦听器添加数据
     *
     * @param int $intFd
     * @param resource $fd
     * @param callable $callback
     * @return mixed
     */
    abstract protected function addWriteData(int $intFd, $fd, callable $callback);

    /**
     * 获取信号事件侦听器添加数据
     *
     * @param int $key
     * @param int $signal
     * @param callable $callback
     * @return mixed
     */
    abstract protected function addSignalData(int $key, int $signal, callable $callback);

    /**
     * 获取定时事件侦听器添加数据
     *
     * @param int $timerId
     * @param int $seconds
     * @param callable $callback
     * @return mixed
     */
    abstract protected function addIntervalData(int $timerId, int $seconds, callable $callback);

    /**
     * 获取定时事件侦听器添加数据
     *
     * @param int $timerId
     * @param int $seconds
     * @param callable $callback
     * @return mixed
     */
    abstract protected function addTimeoutData(int $timerId, int $seconds, callable $callback);

    /**
     * 生成定时ID
     *
     * @return int
     */
    protected function makeTimerId(): int
    {
        return ++$this->timerId;
    }

    /**
     * 移除读事件侦听器
     *
     * @param resource $fd
     * @return bool
     */
    public function delRead($fd): bool
    {
        return $this->delModel(self::EV_READ, (int)$fd);
    }

    /**
     * 移除写事件侦听器
     *
     * @param resource $fd
     * @return bool
     */
    public function delWrite($fd): bool
    {
        return $this->delModel(self::EV_WRITE, (int)$fd);
    }

    /**
     * 移除信号事件侦听器
     *
     * @param int $signal
     * @return bool
     */
    public function delSignal(int $signal): bool
    {
        return $this->delModel(self::EV_SIGNAL, $signal);
    }

    /**
     * 移除定时器事件侦听器
     *
     * @param int $timerId
     * @return bool
     */
    public function delInterval(int $timerId): bool
    {
        return $this->delModel(self::EV_INTERVAL, $timerId);
    }

    /**
     * 移除定时器事件侦听器
     *
     * @param int $timerId
     * @return bool
     */
    public function delTimeout(int $timerId): bool
    {
        return $this->delModel(self::EV_TIMEOUT, $timerId);
    }

    /**
     * 事件侦听器移除模型
     *
     * @param int $flag
     * @param int $key
     * @return bool
     */
    protected function delModel(int $flag, int $key): bool
    {
        $del = true;

        if (isset($this->events[$flag][$key])) {
            if ($this->delCallback($flag, $key)) {
                unset($this->events[$flag][$key]);
            } else {
                $del = false;
            }
        }

        return $del;
    }

    /**
     * 移事件侦听
     *
     * @param int $flag
     * @param int $key
     * @return bool
     */
    abstract protected function delCallback(int $flag, int $key): bool;

    /**
     * 清空定时器事件侦听
     */
    protected function clearAllTimer()
    {
        $this->clearModel(self::EV_INTERVAL);
        $this->clearModel(self::EV_TIMEOUT);
        $this->timerId = 0;
    }

    /**
     * @inheritDoc
     */
    public function clear(int $flag = null): void
    {
        switch ($flag) {
            case self::EV_READ:
            case self::EV_WRITE:
            case self::EV_SIGNAL:
            case self::EV_INTERVAL:
            case self::EV_TIMEOUT:
                $this->clearModel($flag);
                break;
            case null:
                $this->clearModel(self::EV_READ);
                $this->clearModel(self::EV_WRITE);
                $this->clearModel(self::EV_SIGNAL);
                $this->clearAllTimer();
                break;
        }
    }

    /**
     * @inheritDoc
     */
    public function getCount(int $flag = null): int
    {
        switch ($flag) {
            case self::EV_READ:
            case self::EV_WRITE:
            case self::EV_SIGNAL:
            case self::EV_INTERVAL:
            case self::EV_TIMEOUT:
                return count($this->events[$flag]);
            case null:
                return count($this->events[self::EV_READ])
                    + count($this->events[self::EV_WRITE])
                    + count($this->events[self::EV_SIGNAL])
                    + count($this->events[self::EV_INTERVAL])
                    + count($this->events[self::EV_TIMEOUT]);
        }
        return 0;
    }

    /**
     * 清除指定类型事件侦听
     *
     * @param int $flag
     */
    protected function clearModel(int $flag)
    {
        foreach ($this->events[$flag] as $key => $data) {
            $this->delCallback($flag, $key);
        }
    }
}
