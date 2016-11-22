<?php

namespace syar\plug;

use syar\event\InterfaceListen;
use syar\Dispatcher;

/**
 * Class LogSample
 * @package syar\plug
 */
class LogSample implements InterfaceListen {
    protected function _time(){
        $mtime = microtime ();
        $mtime = explode (' ', $mtime);
        return $mtime[1] + $mtime[0];
    }


    protected static $counter = 0;
    public function onRequest1(){
        self::$counter++;
        $this->start = $this->_time();
    }

    protected $start;


    public function onRequest2($request){
        $message =   $request[0] . "({$request[1]})" . "\t"
            . round($this->_time() - $this->start, 5)  . "\t"
            . json_encode($request[2]);
        echo $message . "\n";
    }

    /**
     * @param Dispatcher $em
     */
    function bind($em){
        $em->on($em::EVENT_REQUEST_BEFORE, array($this, 'onRequest1'));
        $em->on($em::EVENT_REQUEST_AFTER, array($this, 'onRequest2'));
    }
}