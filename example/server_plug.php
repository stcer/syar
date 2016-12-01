<?php

use syar\Server;
use syar\log\Log;

require __DIR__ . '/init.inc.php';
$apiNs = 'syar\\example\\service\\';

// main
$server = new Server('0.0.0.0', '5604');
$server->setLogger(new Log());
$server->getProtocol()->getProcessor()->setNs($apiNs);

// add plug
$server->addPlug(new \syar\plug\Admin());
$server->addPlug(new \syar\plug\LogSample(), false);

// reg task for log
$server->getTaskManager()->regTask('log', function($log){
    echo $log;
});

$server->run();