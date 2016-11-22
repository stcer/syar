<?php

namespace syar\plug;

use syar\Request;
use syar\Response;
use syar\event\InterfaceListen;
use syar\Protocol;
use swoole_http_server;

class Admin implements InterfaceListen {

    protected $pathPrefix = "/admin/";

    /**
     * @param Request $request
     * @param Response $response
     * @param Protocol $protocol
     * @return bool
     */
    function onRequest($request, $response, $protocol){
        /** @var swoole_http_server  $server */

        $path = $request->getPath();
        if(strpos($path, $this->pathPrefix) !== 0){
            return false;
        }

        $server = $protocol->server;
        $cmd = str_replace($this->pathPrefix, '', $path);
        switch($cmd){
            case "status" :
                $rs = $server->stats();
                break;
            case "reload" :
                $rs = $server->reload();
                break;
            case "stop" :
                $rs = $server->shutdown();
                break;
            default :
                $rs = "invalid command";
        }

        $rs = var_export($rs, true);
        if($request->get('pretty')){
            $rs = "<pre>\n{$rs}</pre>";
        }
        $response->body = $rs;
        $response->send();

        return true;
    }

    /**
     * @param Protocol $em
     */
    function bind($em){
        $em->on($em::EVENT_REQUEST_BEFORE, array($this, 'onRequest'));
    }
}