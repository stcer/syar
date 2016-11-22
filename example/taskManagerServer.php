<?php
use syar\TaskManager;

$vendorPath = realpath(__DIR__ . "/../vendor/");
$loader = include($vendorPath . "/autoload.php");

$http = new swoole_http_server("0.0.0.0", 5602);
$http->set([
	'task_worker_num' => 2,
	'http_parse_post' => false,
]);

$taskManager = new TaskManager($http);

$taskManager->regTask('test', function($hello = 'hello world'){
	echo "run task test:\n";
	echo $hello . "\n";
	throw new Exception("has error");
});

$taskManager->regTask('send_mail', function($address = 'email@address', $content = "mail body"){
	echo "run task send_mail:\n";
	echo $address . "\n";
	echo $content . "\n";
	return true;
});

$http->on('request', function ($request, $response) use ($taskManager) {
	echo "\n";
	$taskManager->doTask('test', ['Hello world']);
	echo "---------\n";
	$taskManager->doTasks([
		['test', "Hello world 1"],
		['send_mail', ['test@address', 'mail for test']]
		]);
	echo "---------\n";
	$taskManager->doTasksAsync([
		['test', "Hello world 1"],
		['send_mail', ['test@address', 'mail for test']]
	], function($rs){
		var_dump($rs);
	});
	$response->end("<h1>Hello Swoole. #".rand(1000, 9999)."</h1>");
});

$http->start();