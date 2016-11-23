<?php

use syar\Server;
use syar\log\Log;

$vendorPath = realpath(__DIR__ . "/../vendor/");
/** @var \Composer\Autoload\ClassLoader $loader */
$loader = include($vendorPath . "/autoload.php");

$apiNs = 'syar\\example\\service\\';
$loader->addPsr4($apiNs, __DIR__ . '/service');

// main
$server = new Server('0.0.0.0', '5604');
$server->setLogger(new Log());
$server->getProtocol()->getProcessor()->setNs($apiNs);
$server->run();