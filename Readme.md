## 为何用swoole来实现 Yar server
*   历史代码使用了yar, 不想过多修改客户端代码
*   提升Yar服务端执行效率
*   学习swoole, yar(在此感谢laruence,rango及swoole开发团队)

## Require
*   php5.4+
*   ext-swoole 1.8.8+ 
*   ext-msgpack 如果yar使用msgpack编码方式


## Example
**服务端**
example\server.php
~~~
use syar\Server;
use j\log\File as FileLog;
use j\log\Log;

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
~~~

example/service/Test.php

~~~
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
~~~

命令行启动server.php 
~~~
#php server.php
~~~

**客户端**
~~~
$url = "http://127.0.0.1:5604/test";
$client = new Yar_client($url);
$name = $client->getName("tester");
$age = $client->getAge();

//
echo "<pre>\n";
var_dump($name);
var_dump($age);
~~~

## 扩展特性

### 接口批量请求
*   批量请求的接口,服务端使用多个任务进程并行执行
*   请求地址 http://{your_server_address}/multiple
*   调用方法名 function calls($requests);
    $requests参数格式 [请求1数组, 请求2数组, ...], 
    请求数据格式：['api' => ApiName, 'method' => MethodName, 'params' => []]
*   单个接口执行错误, 服务端记录错误日志, 返回['code' => CODE, 'error' => ERROR MESSAGE]格式数组, 客户端自行处理

客户端请求示例：
~~~
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
~~~

### 投递任务到task进程异步执行

参考 
*   TaskMananger->regTask()
*   TaskMananger->doTask()
*   TaskMananger->doTasks()
*   TaskMananger->doTasksAsync()

## 已知问题
1.  未完成文档解析， 可使用自带的yar server显示文档
1.  由于代码是从私有框架独立出来，可能存在未知bug