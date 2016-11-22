<?php

namespace syar;

use swoole_http_request as Base;

/**
 * Class Request
 * @package syar
 */
class Request{
    /**
     * @var Yar
     */
    public $yar;

    /**
     * @var Base
     */
    protected $request;

    /**
     * @param $request
     */
    function __construct($request) {
        $this->request = $request;
    }

    function __call($name, $arguments) {
        return call_user_func_array(array($this->request, $name), $arguments);
    }

    function getHeader($key = null, $def = null){
        if(!$key){
            return $this->request->header;
        }
        return isset($this->request->header[$key]) ? $this->request->header[$key] : $def;
    }

    function getUri(){
        return $this->request->server['request_uri'];
    }

    function getPath(){
        return $this->request->server['path_info'];
    }

    function getIp() {
        return $this->request->server['remote_addr'];
    }

    function getMethod(){
        return $this->request->server['request_method'];
    }

    public function isPost(){
        return $this->request->server['request_method'] == 'POST';
    }

    function getPost(){
        return $this->request->post;
    }

    function getGet(){
        return isset($this->request->get) ? $this->request->get : [];
    }

    function get($key){
        return isset($this->request->get[$key]) ? $this->request->get[$key] : null;
    }

    function getYarMethod() {
	    return $this->yar->getRequestMethod();
    }

    public function getYarParams(){
	    return $this->yar->getRequestParams();
    }
}