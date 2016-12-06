<?php

namespace syar;

use swoole_http_request,
    swoole_http_response,
    swoole_http_server;
use syar\event\InterfaceEventDispatcher;

/**
 * Class Protocol
 * @package syar
 */
class Protocol implements InterfaceEventDispatcher{

    use event\TraitEventManager;
	use log\TraitLog;

    const EVENT_REQUEST_BEFORE = 'Protocol:requestBefore';
    const EVENT_RESPONSE_AFTER = 'Protocol:responseAfter';

    /**
     * @var Server
     */
    public $server;

    /**
     * @var callback
     */
    protected $processor;

    /**
     * @var Packer
     */
    protected $packer;


    public $multipleApiPath = '/multiple';
    public $multipleApiMethod = 'calls';
    public $gzip = false;
    public $gzip_level = 1;


    function __construct() {
        $this->packer = new Packer();
    }

    /**
     * @throws RuntimeException
     */
    public function chkConfig(){
        if(!isset($this->server)){
            throw new RuntimeException("Set protocol's server first");
        }

        if(!is_callable($this->getProcessor())){
            throw new RuntimeException("Set protocol's processor first");
        }

        $taskManager = $this->server->getTaskManager();
        $taskManager->regTask('process', array($this, 'process'));
    }

    /**
     * @param $callback
     */
    function setProcess($callback){
        $this->processor = $callback;
    }

    /**
     * @return callable|Dispatcher
     */
    public function getProcessor(){
        if(!isset($this->processor)){
            $this->processor = new Dispatcher();
        }
        return $this->processor;
    }

    static $i = 1;
    /**
     * @param swoole_http_request $req
     * @param swoole_http_response $res
     */
    function onRequest(swoole_http_request $req, swoole_http_response $res) {
        $request = new Request($req);
        $response = new Response($res);
        $this->server->setCurrentRequest($request, $response);

        if($request->isPost()) {
            $request->yar = $this->packer->unpack($req->rawContent());
        }

        if($this->hasListener(self::EVENT_REQUEST_BEFORE)){
            $this->trigger(self::EVENT_REQUEST_BEFORE, $request, $response, $this);
            if($response->isSend()){
                return;
            }
        }

        if(isset($request->yar)) {
            if($request->yar->isError()){
                // 解包错误
                $this->response([], $request, $response);
                return;
            }

            if($this->isMulApi($request)){
                // 批量请求
                $this->mulRequest($request, $response);
                return;
            }

            $isDocument = false;
            $method = $request->getYarMethod();
            $params = $request->getYarParams();
        } else {
            $isDocument = true;
            $method = $params = '';
        }

        // process request
        $get = isset($req->get) ? $req->get : [];
        $token = new Token($request->getPath(),  $method,  $params, $get);

        $rs = $this->process($token, $isDocument);
        $this->response($rs, $request, $response);
    }

	/**
	 * @param $request Request
	 * @return bool
	 */
	protected function isMulApi($request) {
		return
			$this->multipleApiPath == $request->getPath() &&
			$this->multipleApiMethod == $request->yar->getRequestMethod();
	}

    /**
     * @param $request Request
     * @param $response Response
     */
    protected function mulRequest($request, $response){
        $params = $request->yar->getRequestParams();

	    // maybe a bug
	    // $client->calls($calls);
	    // $params = $calls
        $params = $params[0];

        if(!is_array($params) || count($params) == 0){
            $this->response([
                'code' => 500,
                "error" => "Invalid request params for multiple request"
            ], $request, $response);
            return;
        }

	    $requests = [];
        foreach($params as $key => $param){
	        $token = new Token(
		        $param['api'], $param['method'], $param['params'],
		        isset($param['options']) ? $param['options'] : []
	            );
	        $requests[$key] = ['process', [$token]] ;
        }

        // init
        $this->server->getTaskManager()->doTasksAsync($requests, function($results) use($request, $response){
	        $this->response(['code' => 200, 'rs' => $this->formatMulResults($results)], $request, $response);
        });

//        $results = $this->server->getTaskManager()->doTasks($requests);
//        $this->response(['code' => 200, 'rs' => $this->formatMulResults($results)], $request, $response);
    }

    protected function formatMulResults($results){
        $data = [];
        foreach($results as $key => $rs) {
            if($rs['code'] == 200){
                $data[$key] = $rs['rs'];
            } else {
                unset($rs['debug']);
                $data[$key] = $rs;
            }
        }
        return $data;
    }

    /**
     * @param $requestToken
     * @param $isDocument
     * @return array
     */
    public function process($requestToken, $isDocument = false) {
        try{
            return  [
                'code' => 200,
                'rs' => call_user_func($this->processor, $requestToken, $isDocument, $this)
            ];
        } catch (\Exception $e) {
	        $error = [
		        'code' => $e->getCode(),
		        'error' => $e->getMessage(),
		        'debug' => $e->getTraceAsString()
	        ];
	        $this->log(var_export($error), 'error');
	        return $error;
        }
    }

    /**
     * @param string $data
     * @param Request $request
     * @param Response $response
     */
    protected function response($data, $request, $response){
        if($response->isSend()){
            return;
        }

        if($yar = $request->yar){
            if(!$yar->isError()){
                if($data['code'] == 200){
                    $yar->setReturnValue($data['rs']);
                } else {
                    $yar->setError($data['error']);
                }
            }
            $response->setHeader('Content-Type', 'application/octet-stream');
            $response->body = $this->packer->pack($request->yar);
        } else {
            $response->setHttpStatus(500);
            $response->body = $data['code'] == 200 ? $data['rs'] : $data['error'];
        }

        //压缩
        if ($this->gzip) {
            $response->gzip($this->gzip_level);
        }

        // 输出返回
        $response->send();

        if($this->hasListener(self::EVENT_RESPONSE_AFTER)){
            $this->trigger(self::EVENT_RESPONSE_AFTER, $request, $response, $data, $this);
        }
    }
}