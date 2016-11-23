<?php

namespace syar\log;

/**
 * Class Log
 * @package j\log
 */
class Log implements LogInterface {
    const INFO = 1;
    const WARNING = 2;
    const ERROR = 4;
    const DEBUG = 8;
    const ALL = 15;

    protected $mask = self::ALL;

    /**
     * @var static
     */
    static $instance;

    /**
     * @param $message
     * @param string $type
     */
    static function add($message, $type = 'info'){
        if(!isset(static::$instance)){
            static::$instance = new self();
        }
        static::$instance->log($message, $type);
    }

    /**
     * @param mixed $mask
     */
    public function setMask($mask) {
        $this->mask = $mask;
    }

    protected function format($message, $type){
        if(!$message){
            return "\n";
        }
        return "[{$type} " . microtime(true) . '] - ' . var_export($message, true)  . PHP_EOL;
    }

    protected function getMask($type){
        $types = [
            'info' => self::INFO,
            'warning' => self::WARNING,
            'error' => self::ERROR,
            'debug' => self::DEBUG,
        ];
        return isset($types[$type]) ? $types[$type] : self::INFO;
    }

    public function log($message, $type = 'info'){
        if($this->mask & $this->getMask($type)){
            $this->dispose($message, $type);
        }
        return $this;
    }

    protected function dispose($message, $type = 'info'){
	    echo $this->format($message, $type);
    }

    public function logrotate() {

    }
}