<?php


$vendorPath = realpath(__DIR__ . "/../vendor/");
$loader = include($vendorPath . "/autoload.php");

/** @var  $loader */
$loader->addPsr4("sar\\", __DIR__ . '/syar');