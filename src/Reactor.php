<?php

declare(strict_types=1);

namespace chaser\reactor;

/**
 * 事件反应驱动
 *
 * @package chaser\reactor
 */
class Reactor extends ReactorAbstract
{
    /**
     * 事件反应类库
     */
    public const CLASSES = [
        Event::class,
        Libevent::class,
        Select::class
    ];

    /**
     * 事件反应器
     *
     * @var ReactorInterface
     */
    protected ReactorInterface $app;

    /**
     * 初始化应用
     */
    public function __construct()
    {
        foreach (self::CLASSES as $class) {
            if (class_exists($class)) {
                $this->app = new $class;
                break;
            }
        }
    }

    /**
     * 添加事件侦听器
     *
     * @param resource|int $fd 流|信号|定时
     * @param int $flag 事件类型
     * @param callable $callback 回调方法
     * @param array $args 回调参数
     * @return bool|int
     */
    public function add($fd, int $flag, callable $callback, array $args = [])
    {
        return $this->app->add($fd, $flag, $callback, $args);
    }

    /**
     * 移除事件侦听器
     *
     * @param resource|int $fd 流|信号|定时器ID
     * @param int $flag 事件类型
     * @return bool
     */
    public function del($fd, int $flag)
    {
        return $this->app->del($fd, $flag);
    }

    /**
     * 主回路
     */
    public function loop()
    {
        $this->app->loop();
    }

    /**
     * 破坏回路
     */
    public function destroy()
    {
        $this->app->destroy();
    }

    /**
     * 清空定时器事件侦听
     */
    public function clearAllTimer()
    {
        $this->app->clearAllTimer();
    }

    /**
     * 获取定时器数量
     *
     * @return integer
     */
    public function getTimerCount()
    {
        return $this->app->getTimerCount();
    }
}
