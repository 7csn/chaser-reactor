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
     * 定时器备用 ID
     *
     * @var int
     */
    private int $timerId = 1;

    /**
     * 生成写事件侦听器数据
     *
     * @param int $intFd
     * @param resource $fd
     * @param callable $callback
     * @return mixed
     */
    abstract protected function makeReadData(int $intFd, $fd, callable $callback);

    /**
     * 生成读事件侦听器数据
     *
     * @param int $intFd
     * @param resource $fd
     * @param callable $callback
     * @return mixed
     */
    abstract protected function makeWriteData(int $intFd, $fd, callable $callback);

    /**
     * 生成信号事件侦听器数据
     *
     * @param int $signal
     * @param callable $callback
     * @return mixed
     */
    abstract protected function makeSignalData(int $signal, callable $callback);

    /**
     * 生成周期性定时事件侦听器数据
     *
     * @param int $timerId
     * @param int $seconds
     * @param callable $callback
     * @return mixed
     */
    abstract protected function makeIntervalData(int $timerId, int $seconds, callable $callback);

    /**
     * 生成一次性定时事件侦听加数据
     *
     * @param int $timerId
     * @param int $seconds
     * @param callable $callback
     * @return mixed
     */
    abstract protected function makeTimeoutData(int $timerId, int $seconds, callable $callback);

    /**
     * 移事件侦听器的数据
     *
     * @param int $flag
     * @param int $key
     * @return bool
     */
    abstract protected function delDataModel(int $flag, int $key): bool;

    /**
     * @inheritDoc
     */
    public function set(mixed $fd, int $flag, callable $callback): bool|int
    {
        switch ($flag) {
            case self::EV_READ:
                return $this->setRead($fd, $callback);
            case self::EV_WRITE:
                return $this->setWrite($fd, $callback);
            case self::EV_SIGNAL:
                return $this->setSignal($fd, $callback);
            case self::EV_INTERVAL:
                return $this->setInterval($fd, $callback);
            case self::EV_TIMEOUT:
                return $this->setTimeout($fd, $callback);
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function del(mixed $fd, int $flag): bool
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
     * 清空定时器事件侦听
     */
    public function clearTimer(): void
    {
        $this->clearModel(self::EV_INTERVAL);
        $this->clearModel(self::EV_TIMEOUT);
        $this->timerId = 1;
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
                $this->clearTimer();
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
     * 设置读事件侦听器
     *
     * @param resource $fd
     * @param callable $callback
     * @return bool
     */
    public function setRead($fd, callable $callback): bool
    {
        return $this->setModel(self::EV_READ, fn($key) => $this->makeReadData($key, $fd, $callback), (int)$fd);
    }

    /**
     * 设置写事件侦听器
     *
     * @param resource $fd
     * @param callable $callback
     * @return bool
     */
    public function setWrite($fd, callable $callback): bool
    {
        return $this->setModel(self::EV_WRITE, fn($key) => $this->makeWriteData($key, $fd, $callback), (int)$fd);
    }

    /**
     * 设置信号事件侦听器
     *
     * @param int $signal
     * @param callable $callback
     * @return bool
     */
    public function setSignal(int $signal, callable $callback): bool
    {
        return $this->setModel(self::EV_SIGNAL, fn() => $this->makeSignalData($signal, $callback));
    }

    /**
     * 添加周期性定时器事件侦听器
     *
     * @param int $seconds
     * @param callable $callback
     * @return int
     */
    public function setInterval(int $seconds, callable $callback): int
    {
        $add = $this->setModel(self::EV_INTERVAL, fn($key) => $this->makeIntervalData($key, $seconds, $callback), $this->timerId);
        return $add ? $this->timerId++ : 0;
    }

    /**
     * 添加一次性定时器事件侦听器
     *
     * @param int $seconds
     * @param callable $callback
     * @return int
     */
    public function setTimeout(int $seconds, callable $callback): int
    {
        $add = $this->setModel(self::EV_TIMEOUT, fn($key) => $this->makeTimeoutData($key, $seconds, $callback), $this->timerId);
        return $add ? $this->timerId++ : 0;
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
     * 设置事件侦听器
     *
     * @param int $flag
     * @param callable $addData
     * @param int|null $key
     * @return bool
     */
    protected function setModel(int $flag, callable $addData, int $key = null): bool
    {
        if (isset($this->events[$flag][$key]) && !$this->delModel($flag, $key)) {
            return false;
        }

        $data = $key === null ? $addData() : $addData($key);
        if ($data === false) {
            return false;
        }

        $this->events[$flag][$key] = $data;
        return true;
    }

    /**
     * 移除事件侦听器
     *
     * @param int $flag
     * @param int $key
     * @return bool
     */
    protected function delModel(int $flag, int $key): bool
    {
        $del = true;

        if (isset($this->events[$flag][$key])) {
            if ($this->delDataModel($flag, $key)) {
                unset($this->events[$flag][$key]);
            } else {
                $del = false;
            }
        }

        return $del;
    }

    /**
     * 清除指定类型事件侦听
     *
     * @param int $flag
     */
    protected function clearModel(int $flag): void
    {
        foreach ($this->events[$flag] as $key => $data) {
            $this->delModel($flag, $key);
        }
    }
}
