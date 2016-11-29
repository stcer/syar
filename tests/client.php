<?php

$vendorPath = realpath(__DIR__ . "/../vendor/");
/** @var Composer\Autoload\ClassLoader  $loader */
$loader = include($vendorPath . 'autoload.php');

$url = "http://192.168.0.252:5602/";
$url = "http://jzf.9z.cn/tests/yar_server.php";

function post($url, $param = [], $header = []){
    $oCurl = curl_init();
    if(stripos($url,"https://")!==FALSE){
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
    }

    curl_setopt($oCurl, CURLOPT_HEADER, $header);
    curl_setopt($oCurl, CURLOPT_URL, $url);
    curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt($oCurl, CURLOPT_POST, true);
    curl_setopt($oCurl, CURLOPT_POSTFIELDS, $param);
    curl_setopt($oCurl, CURLOPT_TIMEOUT, 5);
    $sContent = curl_exec($oCurl);
    $aStatus = curl_getinfo($oCurl);
    curl_close($oCurl);
    //var_dump($aStatus);
    //var_dump($sContent);

//    $header = unpack("Nid/nVersion/NMagicNum/NReserved/a32Provider/a32Token/NBodyLen", $data);
//    print_r($header);
    //print_r(substr($sContent, 0, 100));
    //print_r(msgpack_unpack(substr($sContent, 90)));
    $data = explode("\r\n\r\n", $sContent);

    $header = substr($data[1], 0, 82);
    $header = unpack("Nid/nVersion/NMagicNum/NReserved/a32Provider/a32Token/NBodyLen", $header);

    print_r($header);
    print_r(strlen(substr($data[1], 90)));
    print_r(msgpack_unpack(substr($data[1], 90)));
}

$request = [
    'i' => '3594717145',
    'm' => 'add',
    'p' => [1, 2],
];

$header = [
    'id' => 12903494,
    'Version' => 0,
    'MagicNum' => 0x80DFEC60,
    'Reserved' => 0,
    'Provider' => '',
    'Token' => '',
    'BodyLen' => 27,
];

$data = msgpack_pack($request);
$header['BodyLen'] = strlen($data) + 8;

$header = pack('NnNNa32a32N',
    $header['id'],
    $header['Version'],
    $header['MagicNum'],
    $header['Reserved'],
    $header['Provider'],
    $header['Token'],
    $header['BodyLen']
);

$data = $header . "MSGPACK " . $data;

echo post($url, $data, [
    "User-Agent: PHP Yar Rpc-1.2.4",
    "Content-Type: application/x-www-form-urlencoded"
]);
echo "\n";