<?php

namespace syar\log;

/**
 * Class File
 * @package j\log
 */
class File extends  Log {
    protected $mask = 7;
    protected $file;
    protected $maxSize = 1000000;

    function __construct($file = '/tmp/php-server-log.txt') {
        $this->file = $file;
    }

    /**
     * @param string $file
     */
    public function setFile($file) {
        $this->file = $file;
    }

	protected function getFile($type){
		return $this->file;
	}

	protected function dispose($message, $type = 'info'){
		file_put_contents(
			$this->getFile($type),
			$this->format($message, $type),
			FILE_APPEND
		);
	}

    /**
     * @return bool|int
     */
    public function logrotate(){
        if(file_exists($this->file)){
            $size = filesize($this->file);
            if($size > $this->maxSize){
                return file_put_contents($this->file, '');
            }
        }
        return true;
    }
}