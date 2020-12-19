# 事件反应器
事件反应驱动，用于事件的侦听与响应，可处理事件：信号、流读写、定时器。
## 运行环境
- Linux
- PHP >= 7.4.0
- pcntl 扩展
### 安装
```
composer require 7csn/reactor
```
### 性能提升
默认监听套接字流数量 <= 1024，为提升性能，可升级 reactor-libevent 或 reactor-event 库。
* 升级 reactor-libevent

    ```
    composer require 7csn/reactor-libevent
    ```
* 升级 reactor-event

    ```
    composer require 7csn/reactor-event
    ```
### 应用说明
* 创建驱动对象

    ```php
    <?php
  
    namespace chaser\Reactor;  
  
    // composer 自加载，路径视具体情况而定
    require __DIR__ . '/vendor/autoload.php';
  
    // 事件反应驱动对象
    $reactor = new Reactor();
* 事件侦听
    * 套接字读

        ```php
        # 增
        $reactor->addRead(resource $fd, callable $callback): bool;
          # 内部回调
          $callback($fd);
      
        # 删
        $reactor->delRead(resource $fd): bool;
        ```
    * 套接字写

        ```php
        # 增
        $reactor->addWrite(resource $fd, callable $callback): bool;
          # 内部回调
          $callback($fd);
      
        # 删
        $reactor->delWrite(resource $fd): bool;
        ```
    * 信号

        ```php
        # 增
        $reactor->addSignal(int $signal, callable $callback): bool;
          # 内部回调
          $callback($signal);
      
        # 删
        $reactor->delSignal(int $signal): bool;
        ```
    * 周期性定时器

        ```php
        # 增
        $reactor->addInterval(int $seconds, callable $callback): false|$timerId;
          # 内部回调
          $callback($timerId);
      
        # 删
        $reactor->delInterval(int $timerId): bool;
        ```
    * 一次性定时器

        ```php
        # 增
        $reactor->addTimeout(int $seconds, callable $callback): false|$timerId;
          # 内部回调
          $callback($timerId);
      
        # 删
        $reactor->delTimeout(int $timerId): bool;
        ```
    * 兼容
    
        ```php
        $reactor->add(resource $fd|int $signal|int $seconds, int $flag, callable $callback): bool|$timerId
        $reactor->del(resource $fd|int $signal|int $timerId, int $flag): bool
          # 事件类型
          $flag：
              Reactor::EV_READ        # 套接字读
              Reactor::EV_WRITE       # 套接字写
              Reactor::EV_SIGNAL      # 信号
              Reactor::EV_INTERVAL    # 周期性定时器
              Reactor::EV_TIMEout     # 一次性定时器
        ```
* 事件轮询

    ```php
    // 内部事件循环响应，处理阻塞进程
    $reactor->loop();
    ```
* 中断事件轮询

    ```php
    $reactor->destroy();
    ```
* 获取指定类型事件侦听数量

    ```php
    // 默认统计全部数量
    $reactor->getCount(int $flag = null);
    ```
* 清空指定类型事件侦听

    ```php
    // 默认清空所有事件侦听
    $reactor->clear(int $flag = null);
    ```
### 性能升级
