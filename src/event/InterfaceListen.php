<?php

namespace syar\event;

/**
 * Interface InterfaceListen
 * @package syar\event
 */
interface InterfaceListen{
    /**
     * @param TraitEventManager $em
     */
    public function bind($em);
}
