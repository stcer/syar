<?php

use syar\Server;
use syar\log\File as FileLog;
use syar\log\Log;

require __DIR__ . '/init.inc.php';

$server = new Server('0.0.0.0', '5604');
$server->setLogger(new Log());
$service = new \syar\example\service\Test();
$server->setDispatcher(function(\syar\Token $token, $isDocument) use ($service){
    if(!$isDocument){
        $method = $token->getMethod();
        $params = $token->getArgs();
        $value = call_user_func_array(array($service, $method), $params);
    } else {
	    $value = "<h1>Yar api document</h1>";
    }
    return $value;
});

$server->run(['max_request' => 10000]);