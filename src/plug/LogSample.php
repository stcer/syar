<?php

namespace syar\plug;

use syar\event\InterfaceListen;
use syar\Dispatcher;
use syar\Protocol;
use syar\Token;

/**
 * Class LogSample
 * @package syar\plug
 */
class LogSample implements InterfaceListen {
    protected function _time(){
        $time = microtime ();
        $time = explode (' ', $time);
        return $time[1] + $time[0];
    }


    protected static $counter = 0;
    public function onRequest1(){
        self::$counter++;
        $this->start = $this->_time();
    }

    protected $start;

    /**
     * @param mixed $rs
     * @param Token $token
     * @param Protocol $protocol
     */
    public function onRequest2($rs, $token, $protocol){
        $message = $token->getClass()
            . "({$token->getMethod()})" . "\t"
            . round($this->_time() - $this->start, 5)  . "\t"
            . json_encode($token->getArgs());
        $log = date("Y-m-d H:i:s") . " " .  $message . "\n";

        $taskManager = $protocol->server->getTaskManager();
        if($taskManager->has('log')){
            $taskManager->doTask('log', [$log]);
        } else {
            echo $log;
        }
    }

    /**
     * @param Dispatcher $em
     */
    function bind($em){
        $em->on($em::EVENT_REQUEST_BEFORE, array($this, 'onRequest1'));
        $em->on($em::EVENT_REQUEST_AFTER, array($this, 'onRequest2'));
    }
}