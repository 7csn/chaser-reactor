<?php

declare(strict_types=1);

namespace chaser\reactor;

/**
 * 事件反应接口
 *
 * @package chaser\reactor
 */
interface ReactorInterface
{
    /**
     * 读事件
     *
     * @var int
     */
    public const EV_READ = 1;

    /**
     * 写事件
     *
     * @var int
     */
    public const EV_WRITE = 2;

    /**
     * 信号事件
     *
     * @var int
     */
    public const EV_SIGNAL = 3;

    /**
     * 定时器事件
     *
     * @var int
     */
    public const EV_TIMER = 4;

    /**
     * 定时器（一次性）事件
     *
     * @var int
     */
    public const EV_TIMER_ONCE = 5;

    /**
     * 将事件侦听器添加到事件循环
     *
     * @param mixed $fd 流|信号|秒数
     * @param int $flag
     * @param callable $callback
     * @return bool|int
     */
    public function add($fd, int $flag, callable $callback);

    /**
     * 从事件循环中移除事件侦听器
     *
     * @param mixed $fd 流|信号|定时
     * @param int $flag
     * @return bool
     */
    public function del($fd, int $flag): bool;

    /**
     * 主回路
     */
    public function loop();

    /**
     * 破坏回路
     */
    public function destroy();

    /**
     * 清空定时器事件侦听
     */
    public function clearAllTimer();

    /**
     * 获取定时器数量
     *
     * @return int
     */
    public function getTimerCount();
}
