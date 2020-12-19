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
    public const EV_READ = 0b10010;

    /**
     * 写事件
     *
     * @var int
     */
    public const EV_WRITE = 0b10100;

    /**
     * 信号事件
     *
     * @var int
     */
    public const EV_SIGNAL = 0b11000;

    /**
     * 周期性定时器事件
     *
     * @var int
     */
    public const EV_INTERVAL = 0b10001;

    /**
     * 一次性定时器事件
     *
     * @var int
     */
    public const EV_TIMEOUT = 0b00001;

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
     * @param mixed $fd 流|信号|秒数
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
     * 清空事件侦听
     *
     * @param int|null $flag
     * @return mixed
     */
    public function clear(int $flag = null);

    /**
     * 获取事件侦听数量
     *
     * @param int|null $flag
     * @return int
     */
    public function getCount(int $flag = null): int;
}
