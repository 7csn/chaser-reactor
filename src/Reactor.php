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
     *
     * @throws AppNotFoundException
     */
    public function __construct()
    {
        if ($this->initApp() === null) {
            throw new AppNotFoundException("Please install one of the 7csn/reactor-select, 7csn/reactor-libevent, 7csn/reactor-event libraries");
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
            if (class_exists($class)) {
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
    public function __call($name, $args)
    {
        return $this->app->$name(...$args);
    }

    /**
     * @inheritDoc
     */
    public function clear(int $flag = null)
    {
        $this->clear($flag);
    }

    /**
     * @inheritDoc
     */
    public function getCount(int $flag = null): int
    {
        return $this->getCount($flag);
    }
}
