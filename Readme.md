## 为何用swoole来实现 Yar server
*   提升Yar服务效率
*   提升Yar服务稳定性
*   学习swoole, yar(在此感谢laruence,rango及swoole开发团队)

## Requirements
1.   php5.4+
1.   ext-swoole 1.8.8+ 
1.   ext-msgpack 如果yar使用msgpack编码方式

## Installation

```
composer require stcer/syar
```

## Example
**服务端**
example\server.php

```
use syar\Server;
use syar\log\File as FileLog;
use syar\log\Log;

$vendorPath = __Your vendor path__;
/** @var \Composer\Autoload\ClassLoader $loader */
$loader = include($vendorPath . "/autoload.php");
$loader->addPsr4('syar\\example\\service\\', __DIR__ . '/service');

$server = new Server('0.0.0.0', '5604');
$server->setLogger(new Log());
$service = new \syar\example\service\Test();
$server->setDispatcher(function(\syar\Token $token, $isDocument) use ($service){
    if(!$isDocument){
        $method = $token->getMethod();
        $params = $token->getArgs();
        $value = call_user_func_array(array($service, $method), $params);
    } else {
        $value = "Yar api document";
    }
    return $value;
});

$server->run(['max_request' => 10000]);

```

example/service/Test.php

```
namespace syar\example\service;

/**
 * Class Test
 * @package syar\example\service
 */
class Test {
	public function getName($userName){
		return $userName . " Hello";
	}

	public function getAge(){
		return 20;
	}
}

```

命令行启动server.php 

```
#php server.php

```

**客户端**
```
$url = "http://127.0.0.1:5604/test";
$client = new Yar_client($url);
$name = $client->getName("tester");
$age = $client->getAge();

//
echo "<pre>\n";
var_dump($name);
var_dump($age);

```



## 简单性能测试(benchmark)
测试脚本 example/benchmark/testSimple.php, 
测试环境(虚拟机)

*   cpu: i5 - 4460
*   mem: 4G
*   os: centos6.5
*   php: php7(fpm: 20进程, swoole: 18进程(8 worker + 10 task)


脚本一共完成44次接口调用：

1.  简单接口调用 2次
1.  数据库查询接口调用2次
1.  并发简单接口调用 20次
1.  并发数据库查询接口调用 20次

```
function test($type, $times = 5, $limit = 5){
    $timer = new Timer();
    $benchmark = new Benchmark($type);
    $rs[] = $benchmark->simpleTest(); // 2
    $rs[] = $benchmark->dbTest($limit); // 2
    $rs[] = $benchmark->batchTest($times, $limit); // 20
    $rs[] = $benchmark->concurrentTest($times, $limit); // 20
    $stop = $timer->stop();

    // 44 calls, 22 db, 22 normal
    foreach($rs as $v){
        var_dump($v);
    }
    
    return $stop;
}

// start test
$times['syar'] = test('syar');
$times['fpm'] = test('fpm');
var_dump($times);

---------------------------
output: 

array(2) {
  ["syar"]=>
  float(0.01271)
  ["fpm"]=>
  float(0.08602)
}

```
在当前测试环境下，在使用syar批量接口请求，fpm环境下的执行时间大概是syar下的3 -- 6倍左右，

### 简单压力测试
测试脚本 example/benchmark/testConcurrent.php, 50%接口随机查询数据库(10000条数据, 主要为测试接口通信性能)

*   syar 20个并发进程2.4w次接口调用, 用时2.6s秒左右, QPS 9300左右, 可能存在个别调用错误
*   fpm 20个并发进程2.4w次接口调用, 用时15s秒左右, QPS 1600左右, 并产生大量Timeout was reached错误


## 扩展特性

### 接口批量请求
*   批量请求的接口,服务端使用多个任务进程并行执行
*   请求地址 http://{your_server_address}/multiple
*   调用方法名 function calls($requests);
    $requests参数格式 [请求1数组, 请求2数组, ...], 
    请求数据格式：['api' => ApiName, 'method' => MethodName, 'params' => []]
*   单个接口执行错误, 服务端记录错误日志, 返回['code' => CODE, 'error' => ERROR MESSAGE]格式数组, 客户端自行处理

客户端请求示例：
```
#example/client_mul.php
$vendorPath = ...;
$loader = include($vendorPath . "/autoload.php");

$url = "http://127.0.0.1:5604/multiple";
$client = new Yar_client($url);

$calls = [
	'age' => [
		'api' => '/test',
		'method' => 'getAge',
		'params' => []
	    ],
	'name' => [
		'api' => '/test',
		'method' => 'getName',
		'params' => [rand(1, 245301)]
	]
];
$rs = $client->calls($calls);

var_dump($rs);
```


### Protocol插件与Dispatcher插件

应用示例参考 example/server_plug.php, client_plug.php

Protocol触发事件：

1.  Protocol::EVENT_REQUEST_BEFORE, 请求开始触发, 可以提前响应客户端， 中断正常解析流程
1.  Protocol::EVENT_RESPONSE_AFTER, 请求结束触发, 可以适用请求结束之后的处理工作，比如写日志等

Dispatcher触发事件：

1.  Dispatcher::EVENT_REQUEST_BEFORE, Api接口执行前触发
1.  Dispatcher::EVENT_REQUEST_AFTER, Api接口执行后触发


### 投递任务到task进程异步执行

应用示例参考 example/taskManagerServer.php

*   TaskMananger->regTask()
*   TaskMananger->doTask()
*   TaskMananger->doTasks()
*   TaskMananger->doTasksAsync()

## 已知问题
1.  未完成文档解析， 可使用自带的yar server显示文档
1.  由于代码是从私有框架独立出来，可能存在未知bug