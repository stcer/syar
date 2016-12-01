<?php

namespace syar;

use syar\encoder\EncoderInterface;

/**
 * Class Yar
 * @package syar\yar
 */
class Yar{
    public $headerRaw;
    /**
     * @var array
     * type Header struct {
        Id       uint32
        Version  uint16
        MagicNum uint32
        Reserved uint32
        Provider [32]byte
        Token    [32]byte
        BodyLen  uint32
       }
     */
    public $header;

    /**
     * @var array
     */
    public $packer;

    /**
     * @var array
     * array(
        "i" => '', //transaction id
        "m" => '', //the method which being called
        "p" => array(), //parameters
    )
     */
    public $request;

    /**
     * @var array
     * array(
        "i" => '', 
        "s" => '', //status 0 == success
        "r" => '', //return value
        "o" => '', //output
        "e" => '', //error or exception
    )
     */
    public $response;

    function isError(){
        return isset($this->response['e']);
    }

    function getResponse(){
        if(isset($this->request)){
            $this->response['i'] = $this->request['i'];
        }

        if(!isset($this->response['s'])){
            $this->response['s'] = 0;
        }

        return $this->response;
    }

    function setReturnValue($value){
        $this->response['r'] = $value;
    }

    function getRequestMethod(){
        return $this->request['m'];
    }

    function getRequestParams(){
        return $this->request['p'];
    }

    /**
     * @param string $message
     * @param int $status
     * @param string $output
     */
    function setError($message, $status = 1, $output = ''){
        $this->response['s'] = $status;
        $this->response['e'] = $message;
        $this->response['o'] = $output;
    }

    function getError(){
        return $this->response['e'];
    }
}
