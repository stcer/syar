<?php

namespace syar;

use Exception;

/**
 * Class Dispatcher
 * @package syar
 */
class Dispatcher{
    use event\TraitEventManager;

    protected $ns = '';
    protected $classMap = [];

    const EVENT_REQUEST_BEFORE = 'Dispatcher:requestBefore';
    const EVENT_REQUEST_AFTER = 'Dispatcher:requestAfter';

    /**
     * @param $ns
     * @return $this
     */
    public function setNs($ns) {
        $this->ns = $ns;
        return $this;
    }

    /**
     * @param $api
     * @param $class
     */
    public function regClass($api, $class){
        $this->classMap[$api] = $class;
        return;
    }

    protected $instances = [];
    protected function getClass($api){
        $api = trim($api, '/');
        if(isset($this->instances[$api])){
            return $this->instances[$api];
        }

        if(isset($this->classMap[$api])){
            $class = $this->classMap[$api];
        } else {
            if(strpos($api, "/") !== false){
                $classPrefix = str_replace('/', '\\', dirname($api)) . "\\";
                $className = ucfirst(basename($api));
            } else {
                $classPrefix = '';
                $className = ucfirst($api);
            }
            $class = $this->ns . $classPrefix .  $className;
        }

        if(is_string($class)){
            if(!class_exists($class)){
                throw(new Exception("Invalid class({$class})"));
            }
            $this->instances[$api] = new $class();
        } else {
            $this->instances[$api] = $class;
        }

        return $this->instances[$api];
    }


    public $canCache = false;
    private $caches;

    /**
     * @param Token $request [path, call_method, method_params, $_GET]
     * @param $protocol
     * @return mixed
     * @throws Exception
     */
    protected function process($request, $protocol){
        if($this->canCache){
            $cacheId = serialize([$request->getApi(), $request->getArgs()]);
            $cacheId = md5($cacheId);
            if(isset($this->caches[$cacheId])){
                return $this->caches[$cacheId];
            }
        }

        $class = $this->getClass($request->getClass());
        $method = $request->getMethod();
        if(!method_exists($class, $method)){
            throw(new Exception("Invalid method"));
        }

        if(method_exists($class, 'setProtocol')){
            $class->setProtocol($protocol);
        }

	    $rs = call_user_func_array(array($class, $method), $request->getArgs());
        if(isset($cacheId)){
	        $this->caches[$cacheId] = $rs;
        }
        return $rs;
    }

    protected function getDocument($request){
        return "<h1>Swoole yar document</h1>";
    }

    /**
     * @param Token $token
     * @param $protocol
     * @param $isDocument
     * @return mixed|string
     * @throws Exception
     */
    public function __invoke($token, $isDocument, $protocol) {
        if(!$isDocument) {
            if($this->hasListener(self::EVENT_REQUEST_BEFORE)){
                $this->trigger(self::EVENT_REQUEST_BEFORE, $token, $protocol);
            }

            $rs = $this->process($token, $protocol);

            if($this->hasListener(self::EVENT_REQUEST_AFTER)){
                $this->trigger(self::EVENT_REQUEST_AFTER, $token, $protocol, $rs);
            }
            return $rs;
        } else {
            return $this->getDocument($token);
        }
    }
}