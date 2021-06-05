## 事件反应器
事件反应驱动，用于事件的侦听与响应，可处理事件：信号、流读写、定时器。
### 运行环境
- Linux
- PHP >= 7.4.0
### 安装（可省）
```
composer require 7csn/reactor
```
### 安装应用类库（选一）
* 安装 reactor-select
    ```
    composer require 7csn/reactor-select
    ```
* 安装 reactor-libevent（推荐）

    ```
    composer require 7csn/reactor-libevent
    ```
* 安装 reactor-event（推荐）

    ```
    composer require 7csn/reactor-event
    ```
### 应用说明
* 创建驱动对象

    ```php
    <?php
  
    use chaser\reactor\Driver;  
    use chaser\reactor\Reactor;  
  
    // composer 自加载，路径视具体情况而定
    require __DIR__ . '/vendor/autoload.php';
  
    // 事件反应驱动对象
    $driver = new Driver();
* 事件侦听
    * 套接字读

        ```php
        # 增/改
        $driver->setRead(resource $fd, callable $callback): bool;
          # 内部回调
          $callback($fd);
      
        # 删
        $driver->delRead(resource $fd): bool;
        ```
    * 套接字写

        ```php
        # 增/改
        $driver->setWrite(resource $fd, callable $callback): bool;
          # 内部回调
          $callback($fd);
      
        # 删
        $driver->delWrite(resource $fd): bool;
        ```
    * 信号

        ```php
        # 增/改
        $driver->setSignal(int $signal, callable $callback): bool;
          # 内部回调
          $callback($signal);
      
        # 删
        $driver->delSignal(int $signal): bool;
        ```
    * 周期性定时器

        ```php
        # 增（失败返回 0）
        $driver->setInterval(int $seconds, callable $callback): $timerId;
          # 内部回调
          $callback($timerId);
      
        # 删
        $driver->delInterval(int $timerId): bool;
        ```
    * 一次性定时器

        ```php
        # 增（失败返回 0）
        $driver->setTimeout(int $seconds, callable $callback): $timerId;
          # 内部回调
          $callback($timerId);
      
        # 删
        $driver->delTimeout(int $timerId): bool;
        ```
    * 兼容
    
        ```php
        $driver->set(resource $fd|int $signal|int $seconds, int $flag, callable $callback): bool|$timerId
        $driver->del(resource $fd|int $signal|int $timerId, int $flag): bool
          # 事件类型
          $flag：
              Reactor::EV_READ        # 套接字读
              Reactor::EV_WRITE       # 套接字写
              Reactor::EV_SIGNAL      # 信号
              Reactor::EV_INTERVAL    # 周期性定时器
              Reactor::EV_TIMEOUT     # 一次性定时器
        ```
* 事件轮询

    ```php
    $driver->loop(): void;
    ```
* 退出事件轮询

    ```php
    $driver->destroy(): void;
    ```
* 获取指定类型事件侦听数量

    ```php
    // 默认统计全部数量
    $driver->getCount(int $flag = null): void;
    ```
* 清空指定类型事件侦听

    ```php
    // 默认清空所有事件侦听
    $driver->clear(int $flag = null): void;
    ```
* 清空定时器事件侦听

    ```php
    // 默认清空所有定时器事件侦听
    $driver->clearTimer(): void;
    ```
