<?php

$vendorPath = realpath(__DIR__ . "/../vendor/");
/** @var \Composer\Autoload\ClassLoader $loader */
$loader = include($vendorPath . "/autoload.php");
$loader->addPsr4('syar\\example\\service\\', __DIR__ . '/service');