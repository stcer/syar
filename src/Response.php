<?php

namespace syar;

use swoole_http_response as Base;

/**
 * Class Response
 * @package syar
 */
class Response  {

    /**
     * @var Base
     */
    protected $response;

    function __construct($response) {
        $this->response = $response;
    }

    function __call($name, $arguments) {
        return call_user_func_array(array($this->response, $name), $arguments);
    }

    /**
     * 设置Http状态
     * @param $code
     */
    function setHttpStatus($code) {
        $this->response->status($code);
    }

    /**
     * 设置Http头信息
     * @param $key
     * @param $value
     */
    function setHeader($key, $value) {
        $this->response->header($key, $value);
    }

    function gzip($level = 1){
        $this->response->gzip($level);
    }

    public $body;

    /**
     * 添加http header
     * @param $header
     */
    function addHeaders(array $header) {
        foreach($header as $key => $value)
            $this->response->header($key, $value);
    }

    function noCache() {
        $this->response->header('CacheListener-Control',
            'no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->response->header('Pragma','no-cache');
    }

    protected $isSend = false;

    function send(){
        $this->isSend = true;
        $this->response->end($this->body);
    }

    public function isSend(){
        return $this->isSend;
    }
}
