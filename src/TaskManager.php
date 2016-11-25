<?php

namespace syar;

use swoole_server;
use Exception;

/**
 * Class TaskManager
 * @package j\network
 */
class TaskManager {

    protected $callbacks;

    /**
     * @var swoole_server
     */
    protected $server;

    /**
     * @var array
     */
    protected $runMap = [];
	public $maxRunTimes = 10;
	protected static $mulIndex = 0;
	protected static $mulRunMap = [];

    /**
     * TaskManager constructor.
     * @param swoole_server $server
     */
    public function __construct(swoole_server $server) {
        $this->server = $server;
	    $this->server->on("task", array($this, 'onTask'));
	    $this->server->on("finish", array($this, 'onFinish'));
    }

    /**
     * @param string $id
     * @param callback $taskCallback
     * @return $this
     */
    function regTask($id, $taskCallback) {
        $this->callbacks[$id] = $taskCallback;
        return $this;
    }

    /**
     * @param $id
     * @return bool
     */
    function has($id){
        return isset($this->callbacks[$id]);
    }

	/**
	 * 执行任务
	 * @param $data
	 * @return array|mixed
	 */
	private function processTask($data) {
	    if(!is_array($data)
		    || !isset($data['id'])
		    || !($id = $data['id'])
		    || !isset($this->callbacks[$id])
	    ){
		    return $this->getErrorInfo("Invalid task id");
	    }

	    try{
		    return call_user_func_array($this->callbacks[$id], $data['params']);
	    } catch(Exception $e){
		    return $this->getErrorInfo($e);
	    }
    }

	/**
	 * @param Exception|string $e
	 * @return array
	 */
	private function getErrorInfo($e){
		if(is_string($e)){
			$e = new Exception($e);
		}
		return [
			'error' => $e->getMessage(),
			'code' => $e->getCode(),
			'trace' => $e->getTraceAsString()
		];
	}

	/**
	 * @param $id
	 * @param array $params
	 * @param callback $finishCallback
	 * @param array $bindArgs
	 * @return void
	 * @throws Exception
	 */
	function doTask($id, $params = [], $finishCallback = null, $bindArgs = []){
		if(!isset($this->callbacks[$id])){
			throw(new Exception("Invalid task id"));
		}

        $request = $this->getTaskArgs([$id, $params]);
		if($this->server->taskworker){
			// 在task进程, 任务同步执行
			$rs = $this->processTask($request);
			if($finishCallback){
				call_user_func($finishCallback, $rs, $bindArgs);
			}
		} else {
			$this->server->task($request, -1, function($serv, $task_id, $data) use($finishCallback, $bindArgs) {
				if($finishCallback){
					call_user_func($finishCallback, $data, $bindArgs);
				}
			});
		}
	}

    /**
     * @param $requests [ [id, [params]], ...]
     * @param $callback
     * @param array $bindParams
     * @param float $timeout
     * @return mixed
     */
    public function doTasks($requests, $callback = null, $bindParams = [], $timeout = 10.0){
        if($this->server->taskworker){
            // 在task进程, 任务同步执行
	        $results = [];
            foreach($requests as $index => $request){
	            $results[$index] = $this->processTask($this->getTaskArgs($request));
            }
        } else {
	        $tasks = [];
	        foreach($requests as $index => $request){
		        $tasks[$index] = $this->getTaskArgs($request);
	        }
	        $results = $this->server->taskWaitMulti($tasks, $timeout);
        }
	    if($callback){
		    call_user_func($callback, $results, $bindParams);
	    }
        return $results;
    }

    private function getTaskArgs($request){
	    $params = isset($request[1])
		    ? (is_array($request[1]) ? $request[1] : array($request[1]))
		    : [];
	    return [
		    'id' => $request[0],
		    'params' => $params
	    ];
    }

	function onTask($serv, $task_id, $from_id, $data){
		return $this->processTask($data);
	}

	/**
	 * @param $requests
	 * @param $callback
	 * @param array $bindParams
	 */
	public function doTasksAsync($requests, $callback, $bindParams = []){
		// init request status
		static::$mulIndex++;
		static::$mulRunMap[static::$mulIndex] = [
			'total' => count($requests),
			'finish' => 0,
			'start' => time(),
			'callback' => $callback,
			'bindParams' => $bindParams,
			'rs' => [],
		];

		foreach($requests as $index => $request){
			// to task
			$taskId = $this->server->task($this->getTaskArgs($request));
			$this->runMap[$taskId] = [
				'isMul' => true,
				'order' => $index,
				'start' => time(),
				'mulIndex' => static::$mulIndex,
			];
		}
	}

	/**
     * @param $serv
     * @param $task_id
     * @param $data
     */
    function onFinish($serv, $task_id, $data){
        if(!isset($this->runMap[$task_id])){
            return;
        }

        $taskInfo = $this->runMap[$task_id];
        unset($this->runMap[$task_id]);
        if($taskInfo['isMul']){
            $status =& self::$mulRunMap[$taskInfo['mulIndex']];
            $status['finish']++;

            $rsOrder = $taskInfo['order'];
            $status['rs'][$rsOrder] = $data;
            //$isExpire = (time() - $taskInfo['start']) > $this->maxRunTimes;
            if($status['finish'] < $status['total']){
                return;
            }

            call_user_func(
                $status['callback'],
                $status['rs'],
                $status['bindParams']
            );

            unset($status);
            unset(self::$mulRunMap[$taskInfo['mulIndex']]);
        } else {
            if(isset($taskInfo['callback'])){
                call_user_func($taskInfo['callback'], $data, $taskInfo['bindParams']);
            }
        }
    }
}