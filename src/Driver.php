<?php

declare(strict_types=1);

namespace chaser\reactor;

/**
 * 事件反应驱动类
 *
 * @package chaser\reactor
 *
 * @method      bool         addRead($fd, callable $callback)
 * @method      bool         addWrite($fd, callable $callback)
 * @method      bool         addSignal(int $signal, callable $callback)
 * @method      false|int    addInterval(int $seconds, callable $callback)
 * @method      false|int    addTimeout(int $seconds, callable $callback)
 * @method      bool         delRead($fd)
 * @method      bool         delWrite($fd)
 * @method      bool         delSignal($fd)
 * @method      bool         delInterval($fd)
 * @method      bool         delTimeout($fd)
 * @method      bool|int     add($fd, int $flag, callable $callback)
 * @method      bool         del($fd, int $flag)
 * @method      void         loop()
 * @method      void         destroy()
 * @method      void         clear(int $flag = null)
 * @method      int          getCount(int $flag = null)
 *
 * @see Reactor
 */
class Driver
{
    /**
     * 优先的事件反应类库
     *
     * @var string[]
     */
    private static array $classes = [
        Event::class,
        Libevent::class,
        Select::class
    ];

    /**
     * 事件反应器
     *
     * @var ReactorInterface
     */
    private ReactorInterface $reactor;

    /**
     * 创建事件反应器实例
     *
     * @return ReactorInterface|null
     */
    public static function createReactor(): ?ReactorInterface
    {
        foreach (self::$classes as $class) {
            if (class_exists($class)) {
                return new $class;
            }
        }
        return null;
    }

    /**
     * 初始化事件反应器应用
     *
     * @throws AppNotFoundException
     */
    public function __construct()
    {
        $reactor = self::createReactor();
        if ($reactor === null) {
            throw new AppNotFoundException("Please install one of the 7csn/reactor-select, 7csn/reactor-libevent, 7csn/reactor-event libraries");
        } else {
            $this->reactor = $reactor;
        }
    }

    /**
     * 同步反应器方法
     *
     * @param string $name
     * @param array $args
     * @return mixed
     */
    public function __call(string $name, array $args): mixed
    {
        return $this->reactor->$name(...$args);
    }
}
