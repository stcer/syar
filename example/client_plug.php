<?php

$vendorPath = realpath(__DIR__ . "/../vendor/");
$loader = include($vendorPath . "/autoload.php");

$url = "http://127.0.0.1:5604/multiple";
$client = new Yar_client($url);
$requests = [
	'age' => [
		'api' => '/test',
		'method' => 'getAge',
		'params' => []
	],
	'name' => [
		'api' => '/test',
		'method' => 'getName',
		'params' => ['tester']
	]];
$rs = $client->calls($requests);

// status
$status = file_get_contents('http://127.0.0.1:5604/admin/status');
//$closed = file_get_contents('http://127.0.0.1:5604/admin/stop');
//
echo "<pre>";
var_dump($rs);
var_dump($status);
