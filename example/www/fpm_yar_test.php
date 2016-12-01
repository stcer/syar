<?php

require __DIR__ . '/../init.inc.php';

use Yar_Server as Server;
$service = new \syar\example\service\Test();

$server = new Server($service);
$server->handle();