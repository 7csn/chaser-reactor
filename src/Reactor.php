<?php

declare(strict_types=1);

namespace chaser\reactor;

/**
 * 事件反应驱动
 *
 * @package chaser\reactor
 */
class Reactor implements ReactorInterface
{
    /**
     * 优先的事件反应类库
     *
     * @var string[]
     */
    protected static array $classes = [
        EventLoop::class,
        Libevent::class
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
        if ($this->initApp() === null) {
            $this->app = new Select();
        }
    }

    /**
     * 初始化事件反应应用
     *
     * @return ReactorInterface|null
     */
    protected function initApp(): ?ReactorInterface
    {
        foreach (self::$classes as $class) {
            if ($class instanceof ReactorInterface) {
                $this->app = new $class;
                break;
            }
        }
        return $this->app;
    }

    /**
     * @inheritDoc
     */
    public function add($fd, int $flag, callable $callback)
    {
        return $this->app->add($fd, $flag, $callback);
    }

    /**
     * @inheritDoc
     */
    public function del($fd, int $flag): bool
    {
        return $this->app->del($fd, $flag);
    }

    /**
     * @inheritDoc
     */
    public function loop()
    {
        $this->app->loop();
    }

    /**
     * @inheritDoc
     */
    public function destroy()
    {
        $this->app->destroy();
    }

    /**
     * @inheritDoc
     */
    public function clearAllTimer()
    {
        $this->app->clearAllTimer();
    }

    /**
     * @inheritDoc
     */
    public function getTimerCount()
    {
        return $this->app->getTimerCount();
    }

    /**
     * @inheritDoc
     */
    public function __call($name, $args)
    {
        return $this->app->$name(...$args);
    }
}
