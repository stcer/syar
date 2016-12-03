<?php

# 依赖服务 ../server1.php
namespace syar\example\benckmark;

define('IS_OUTPUT', false);

$vendorPath = realpath(__DIR__ . "/../../vendor/");
$loader = include($vendorPath . "/autoload.php");
require __DIR__ . '/lib.php';

// 50 * 24 * 100 = 12w
//$times['syar'] = ab('syar', 50, 100);

// 20 * 24 * 20 = 9600
$times['fpm'] = ab('fpm', 20, 20);
var_dump($times);
//
