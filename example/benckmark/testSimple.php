<?php

# 依赖服务 ../server1.php
namespace syar\example\benckmark;

$vendorPath = realpath(__DIR__ . "/../../vendor/");
$loader = include($vendorPath . "/autoload.php");
require __DIR__ . '/lib.php';

// start test
$times['syar'] = test('syar');
$times['fpm'] = test('fpm');
var_dump($times);

