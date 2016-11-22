<?php

$http = new swoole_http_server("0.0.0.0", 5602);
$http->set([
    'http_parse_post' => false
    ]);

$http->on('request', function ($request, $response) {
    echo $request->rawContent();
    $response->end("<h1>Hello Swoole. #".rand(1000, 9999)."</h1>");
    });

$http->start();