<?php

use syar\Server;
use syar\log\Log;

require __DIR__ . '/init.inc.php';

// main
$apiNs = 'syar\\example\\service\\';
$server = new Server('0.0.0.0', '5604');
$server->setLogger(new Log());
$server->getProtocol()->getProcessor()->setNs($apiNs);
$server->run([
    'max_connection' => 20480,
    'worker_num' => 48,
    'task_worker_num' => 16,
]);

/**
 * @see http://wiki.swoole.com/wiki/page/p-server/sysctl.html

内核参数调整
ulimit -n 655350

# time_wait
sysctl net.ipv4.tcp_tw_recycle=1
sysctl net.ipv4.tcp_tw_reuse=1

# 进程间通信
sysctl net.unix.max_dgram_qlen=100

# socket buffer
sysctl net.core.wmem_default=8388608
sysctl net.core.rmem_default=8388608
sysctl net.core.rmem_default=16777216
sysctl net.core.wmem_default=16777216

# 消息队列
sysctl kernel.msgmnb=4203520
sysctl kernel.msgmni=64
sysctl kernel.msgmax=8192
 */