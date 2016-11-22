<?php

namespace syar\event;

/**
 * Class TraitEventManager
 * @package syar\event
 */
trait TraitEventManager {
    private $__ECallback = [];
    private $__EListeners = [];

    /**
     * @param InterfaceListen $listener
     */
    public function attaches($listener){
        $listener->bind($this);
    }

    public function on($name, $callback){
        $this->__ECallback[$name][] = $callback;
    }

    public function off($name = null) {
        if(!$name){
            $this->__ECallback = [];
        }else{
            unset($this->__ECallback[$name]);
        }
    }

    public function trigger($event) {
        $args = func_get_args();
        array_shift($args);

        foreach($this->__ECallback[$event] as $callback){
            if(true === call_user_func_array($callback, $args)){
                break;
            }
        }
    }

    public function hasListener($name){
        return isset($this->__ECallback[$name]);
    }
}