<?php

# 依赖服务 ../server1.php
namespace syar\example\benckmark;

define('IS_OUTPUT', false);

$vendorPath = realpath(__DIR__ . "/../../vendor/");
$loader = include($vendorPath . "/autoload.php");
require __DIR__ . '/lib.php';

$type = 'syar';
if(PHP_SAPI == 'cli'){
    if(isset($argv[1])){
        $type = $argv[1];
    }
}

if($type == 'syar'){
    // 2.4w - 2.8s
    // Qps 8500
    $times['syar'] = ab('syar', 20, 50, false, 5);
} elseif($type == 'syar_batch'){
    // 2.4w - 2.6s
    // Qps 9300
    $times['syar_batch'] = ab('syar', 20, 50, true, 5); // QPS 3500
} else{
    // 2.4w - 15s
    // Qps 1600
    $times['fpm'] = ab('fpm', 20, 50, false, 5);
}

var_dump($times);