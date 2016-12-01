<?php

use syar\Server;
use syar\log\Log;

require __DIR__ . '/init.inc.php';

// main
$apiNs = 'syar\\example\\service\\';
$server = new Server('0.0.0.0', '5604');
$server->setLogger(new Log());
$server->getProtocol()->getProcessor()->setNs($apiNs);
$server->run();