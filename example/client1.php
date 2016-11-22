<?php

$vendorPath = realpath(__DIR__ . "/../vendor/");
$loader = include($vendorPath . "/autoload.php");

$url = "http://127.0.0.1:5604/test";
//$url = "http://192.168.0.183:5604/multiple";
$client = new Yar_client($url);
$name = $client->getName("tester");
$age = $client->getAge("tester");

//
echo "<pre>";
var_dump($name);
var_dump($age);
