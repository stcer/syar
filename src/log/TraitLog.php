<?php

namespace syar\log;

use Monolog\Logger as Monolog;

/**
 * Class TraitLog
 * @package j\log
 */
trait TraitLog  {
    /**
     * @var LogInterface|Monolog
     */
    protected $logger;

    /**
     * @param LogInterface|Monolog $logger
     * @return $this
     */
    public function setLogger($logger) {
        $this->logger = $logger;
        return $this;
    }

    /**
     * @return LogInterface|Monolog
     */
    public function getLogger() {
        return isset($this->logger) ? $this->logger : null;
    }

    /**
     * @param $message
     * @param string $type
     * @param array $context
     */
    protected function log($message, $type = 'info', $context = []){
        if(!isset($this->logger)){
            return;
        }

        if($this->logger instanceof LogInterface) {
            $this->logger->log($message, $type);
        } else {
            $this->logger->log($type, $message, $context);
        }
    }
}