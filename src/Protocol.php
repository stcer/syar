<?php

namespace syar;

use swoole_http_request,
    swoole_http_response,
    swoole_http_server;

/**
 * Class Protocol
 * @package syar
 */
class Protocol {

    use event\TraitEventManager;
	use log\TraitLog;

    const EVENT_REQUEST_BEFORE = 'Protocol:requestBefore';
    const EVENT_RESPONSE_AFTER = 'Protocol:responseAfter';

    /**
     * @var Server
     */
    public $server;
    protected $taskMap = [];

    /**
     * @var Packer
     */
    protected $packer;
    function __construct() {
        $this->packer = new Packer();
    }

    /**
     * @var callback
     */
    public $processor;
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

    protected static $taskStatus = [];
    protected static $mulIndex = 0;

    /**
     * @param swoole_http_request $req
     * @param swoole_http_response $res
     */
    function onRequest(swoole_http_request $req, swoole_http_response $res) {
        $request = new Request($req);
        $response = new Response($res);

        if($request->isPost()) {
            // parse post request
            $body = $req->rawContent();
            $request->yar = $this->packer->unpack($body);
        }

        if($this->hasListener(self::EVENT_REQUEST_BEFORE)){
            $this->trigger(self::EVENT_REQUEST_BEFORE, $request, $response, $this);
            if($response->isSend()){
                return;
            }
        }

        if($request->yar) {
            // multiple api
            if($this->isMulApi($request)){
                $this->mulRequest($request, $response);
                return;
            }

	        $token = new Token(
	        	$request->getPath(),
		        $request->yar->getRequestMethod(),
		        $request->yar->getRequestParams(),
		        isset($req->get) ? $req->get : []
	            );
	        $isDocument = false;
        } else {
	        $token = new Token(
		        $request->getPath(),  '',  '',
		        isset($req->get) ? $req->get : []
	            );
	        $isDocument = true;
        }

        // process request
        $rs = $this->process($token, $isDocument);

        // response result
        $this->response($rs, $request, $response);
    }

    public $multipleApiPath = '/multiple';
    public $multipleApiMethod = 'calls';

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
                "rs" => "Invalid request params"
            ], $request, $response);
            return;
        }

	    $requests = [];
        foreach($params as $key => $param){
	        $token = new Token(
		        $param['api'],
		        $param['method'],
		        $param['params'],
		        isset($param['options']) ? $param['options'] : []
	            );
	        $requests[$key] = ['process', [$token]] ;
        }

        // init
        $this->server->getTaskManager()->doTasksAsync($requests, function($results) use($request, $response){
	        $data = [];
			foreach($results as $key => $rs) {
				if($rs['code'] == 200){
					$data[$key] = $rs['rs'];
				} else {
					unset($rs['debug']);
					$data[$key] = $rs;
				}
			}
	        $this->response(['code' => 200, 'rs' => $data], $request, $response);
        });
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

    public $gzip = false;
    public $gzip_level = 1;

    /**
     * @param string $data
     * @param Request $request
     * @param Response $response
     */
    protected function response($data, $request, $response){
        if($response->isSend()){
            return;
        }

        $yar = $request->yar;
        if($data['code'] == 200){
            if($yar){
                // set success result
                $yar->setReturnValue($data['rs']);
            } else {
                $response->body = $data['rs'];
            }
        } else {
            if($yar){
                // set error
                $yar->setError($data['error']);
            } else {
                $response->setHttpStatus(500);
                $response->body = $data['error'];
            }
        }

        if($yar){
            $response->setHeader('Content-Type', 'application/octet-stream');
            $response->body = $this->packer->pack($request->yar); // set data
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