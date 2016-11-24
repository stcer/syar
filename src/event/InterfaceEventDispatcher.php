<?php

namespace syar\event;

/**
 * Interface InterfaceEventDispatcher
 * @package syar\event
 */
interface InterfaceEventDispatcher {
    /**
     * @param InterfaceListen $listener
     */
    public function attaches($listener);

    public function on($name, $callback);

    public function trigger($event);
}