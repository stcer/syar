<?php

$vendorPath = realpath(__DIR__ . "/../vendor/");
$loader = include($vendorPath . "/autoload.php");

$url = "http://127.0.0.1:5604/multiple";
$client = new Yar_client($url);

$calls = [
	[
		'api' => '/test',
		'method' => 'getAge',
		'params' => []
	],[
		'api' => '/test',
		'method' => 'getName',
		'params' => [rand(1, 245301)]
	]
];
$rs = $client->calls($calls);

//
echo "<pre>";
var_dump($rs);
