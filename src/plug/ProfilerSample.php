<?php

namespace syar\plug;

use syar\Request;
use syar\event\InterfaceListen;
use syar\Protocol;

/**
 * Class ProfilerSample
 * @package syar\plug
 */
class ProfilerSample implements InterfaceListen {
    protected static $counter = 0;
    protected $start = 0;

    protected $queue;
    protected $queueLen = 0;
    protected $queueItemMax = 5;

    protected function _time(){
        $mtime = microtime ();
        $mtime = explode (' ', $mtime);
        return $mtime[1] + $mtime[0];
    }

    /**
     * @param Request $request
     */
    function onRequest1($request){
        self::$counter++;
        $request->counter = self::$counter;
        $this->queue[self::$counter] = [
            'start' => $this->_time(),
            'api' => $request->getPath(),
            'method' => $request->yar ? $request->yar->getRequestMethod() : '',
        ];
        $this->queueLen++;
    }

    function onRequest2($request){
        $index = $request->counter;
        $this->queue[$index]['time'] = $this->_time() - $this->queue[$index]['start'];

        if($this->queueLen >= $this->queueItemMax){
            var_dump($this->queue);
            $this->queueLen = 0;
            $this->queue = [];
        }
    }

    /**
     * @param Protocol $em
     */
    function bind($em){
        $em->on($em::EVENT_REQUEST_BEFORE, array($this, 'onRequest1'));
        $em->on($em::EVENT_RESPONSE_AFTER, array($this, 'onRequest2'));
    }
}