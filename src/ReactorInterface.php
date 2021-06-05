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
     * 事件：资源读
     *
     * @var int
     */
    public const EV_READ = 0b10010;

    /**
     * 事件：资源写
     *
     * @var int
     */
    public const EV_WRITE = 0b10100;

    /**
     * 事件：信号
     *
     * @var int
     */
    public const EV_SIGNAL = 0b11000;

    /**
     * 事件：周期性定时器
     *
     * @var int
     */
    public const EV_INTERVAL = 0b10001;

    /**
     * 事件：一次性定时器
     *
     * @var int
     */
    public const EV_TIMEOUT = 0b00001;

    /**
     * 添加或修改事件侦听器
     *
     * @param mixed $fd 流|信号|秒数
     * @param int $flag
     * @param callable $callback
     * @return bool|int
     */
    public function set(mixed $fd, int $flag, callable $callback): bool|int;

    /**
     * 移除事件侦听器
     *
     * @param mixed $fd 流|信号|秒数
     * @param int $flag
     * @return bool
     */
    public function del(mixed $fd, int $flag): bool;

    /**
     * 主回路
     */
    public function loop(): void;

    /**
     * 破坏回路
     */
    public function destroy(): void;

    /**
     * 清空定时器事件侦听
     */
    public function clearTimer(): void;

    /**
     * 清空事件侦听器
     *
     * @param int|null $flag
     */
    public function clear(int $flag = null): void;

    /**
     * 获取事件侦听数量
     *
     * @param int|null $flag
     * @return int
     */
    public function getCount(int $flag = null): int;
}
