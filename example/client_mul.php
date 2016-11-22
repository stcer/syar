<?php

$vendorPath = realpath(__DIR__ . "/../vendor/");
$loader = include($vendorPath . "/autoload.php");

$url = "http://127.0.0.1:5604/multiple";
//$url = "http://192.168.0.183:5604/multiple";
$client = new Yar_client($url);

$calls = array();
for($i = 0; $i < 5; $i++){
    $calls["age_{$i}"] = [
        'api' => '/test',
        'method' => 'getAge',
        'params' => []
        ];
    $calls["name_{$i}"] = [
        'api' => '/test',
        'method' => 'getName',
        'params' => [rand(1, 245301)]
        ];
}

$rs = $client->calls($calls);

//
echo "<pre>";
var_dump($rs);
