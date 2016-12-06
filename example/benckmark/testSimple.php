<?php

# 依赖服务 ../server1.php
namespace syar\example\benckmark;

$vendorPath = realpath(__DIR__ . "/../../vendor/");
$loader = include($vendorPath . "/autoload.php");
require __DIR__ . '/lib.php';

// start test
// 24 api call
$times['syar'] = test('syar', false, 5); // 5 x 4api + 2 * 2
$times['syar_batch'] = test('syar', true, 5);
$times['fpm'] = test('fpm', false, 5);
var_dump($times);
