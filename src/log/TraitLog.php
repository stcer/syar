<?php

namespace syar\log;

/**
 * Class TraitLog
 * @package j\log
 */
trait TraitLog  {
    /**
     * @var LogInterface
     */
    protected $logger;

    /**
     * @param LogInterface $logger
     * @return $this
     */
    public function setLogger($logger) {
        $this->logger = $logger;
        return $this;
    }

    /**
     * @return LogInterface
     */
    public function getLogger() {
        return isset($this->logger) ? $this->logger : null;
    }

    protected function log($message, $type = 'info'){
        if(!isset($this->logger)){
            return null;
        }

        $this->logger->log($message, $type);
    }
}