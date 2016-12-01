<?php

$vendorPath = realpath(__DIR__ . "/../vendor/");
$loader = include($vendorPath . "/autoload.php");



/** @var \syar\example\service\Test $news */
$url = 'http://127.0.0.1:5604/test/';
$data = [];
for($i = 0; $i < 10; $i++){
    Yar_Concurrent_Client::call($url, "getName", [rand(0, 245301)],
        function($rs, $callinfo) use ($i, & $data){
            $data['name_' . $i] = $rs;
        }
    );

    Yar_Concurrent_Client::call($url, "getAge", [],
        function($rs, $callinfo) use ($i, & $data){
            $data['age_' . $i] = $rs;
        }
    );
}
Yar_Concurrent_Client::loop();


echo "<pre>";
var_dump($data);