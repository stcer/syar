<?php

namespace syar\log;

/**
 * Interface LogInterface
 * @package j\log
 */
interface LogInterface{

    public function log($message, $type = 'info');

    public function logrotate();

}