<?php

namespace syar\example\benckmark;

use Yar_Client, Yar_Concurrent_Client;
use swoole_process;

defined('IS_OUTPUT') || define('IS_OUTPUT', true);

/**
 * @param $type
 * @param int $times
 * @param bool $batch
 * @return float
 */
function test($type, $batch = false, $times = 0){
    $timer = new Timer();
    $benchmark = new Benchmark($type);
    $rs[] = $benchmark->simpleTest(); // 2
    $rs[] = $benchmark->dbTest(); // 2

    $times = $times ?: ($batch ? 5 : 1);
    if($batch){
        $rs[] = $benchmark->batchTest($times); // 4 * $times
    } else {
        $rs[] = $benchmark->concurrentTest($times); // 4 * $times
    }

    $stop = $timer->stop();

    // 44 calls, 22 db, 22 normal
    if(IS_OUTPUT){
        echo "{$type} -----------------------------\n";
        foreach($rs as $v){
            var_dump($v);
        }
    }

    return $stop;
}

function ab($type = 'syar', $c = 1, $n = 1, $batch = false, $times = 1) {
    $pm = new SimpleProcessorManager();
    $timer = new Timer();

    $pm->run($c, function(swoole_process $worker) use($type, $n, $batch, $times){
        for($i = 0; $i < $n; $i++){
            echo "Worker {$worker->pid} for the {$i}th\n";
            test($type, $batch, $times);
        }
        $worker->exit(0);
    });
    return $timer->stop();
}

/**
 * Class Benchmark
 * @package syar\example\benckmark
 */
class Benchmark {

    protected $type = 'syar';
    protected $url;
    protected $urlFpm = "http://syar7.x1.cn/fpm_yar_%s.php";
    protected $urlSyar = "http://127.0.0.1:5604/%s";

    /**
     * Benchmark constructor.
     * @param string $type
     */
    public function __construct($type){
        $this->type = $type;
        $this->url = $this->isFpm() ? $this->urlFpm : $this->urlSyar;
    }

    private function isFpm(){
        return $this->type == 'fpm';
    }

    /**
     * 1万表记录测试数据
     * @var int
     * @see file : mkTestData.php
     */
    private $maxTableId = 10000;
    // 列表查询取前2w记录随机
    private $maxTableStart = 5000;

    /**
     * @return int
     */
    private function getInfoId(){
        return rand(1, $this->maxTableId);
    }

    /**
     * @param int $limit
     * @return int
     */
    private function getStart($limit = 5){
        $start = rand($limit, $this->maxTableStart) - $limit;
        if($start < 0){
            $start = 0;
        }
        return $start;
    }

    /**
     * @param $api
     * @param bool $returnUrl
     * @return string|Yar_Client
     */
    function getClient($api, $returnUrl = false){
        $url = sprintf($this->url, $api);
        if($returnUrl){
            return $url;
        }

        $client = new Yar_Client($url);
        $client->setOpt(YAR_OPT_PACKAGER, 'msgpack');
        return $client;
    }

    function dbTest(){
        $client = $this->getClient('db');
        $info = $client->getInfo($this->getInfoId());
        $list = $client->getList($this->getStart(), $this->listLimit);
        return [$info, $list];
    }

    function simpleTest(){
        $client = $this->getClient('test');
        $name = $client->getName("tester");
        $age = $client->getAge();
        return [$name, $age];
    }

    /**
     * @param int $times
     * @return array
     */
    function concurrentTest($times = 1){
        $data = [];

        $url1 = $this->getClient('test', true);
        $url2 = $this->getClient('db', true);
        for($i = 0; $i < $times; $i++){
            $this->concurrentCall($data, $url1, 'getName', [rand(0, 245301)], 'name_' . $i);
            $this->concurrentCall($data, $url1, 'getAge', [], 'age_' . $i);
            $this->concurrentCall($data, $url2, 'getInfo', [$this->getInfoId()], 'info_' . $i);
            $this->concurrentCall($data, $url2, 'getList', [$this->getStart(), $this->listLimit], 'list_' . $i);
        }
        Yar_Concurrent_Client::loop();
        Yar_Concurrent_Client::reset();
        return $data;
    }

    private function concurrentCall(&$data, $url, $method, $args, $key){
        Yar_Concurrent_Client::call(
            $url, $method, $args,
            function($rs) use ($key, &$data){
                $data[$key] = $rs;
            }
        );
    }

    protected $listLimit = 2;

    /**
     * @param int $times
     * @return array
     */
    function batchTest($times = 1) {
        if($this->isFpm()){
            return $this->concurrentTest($times);
        }
        $requests = [];
        for($i = 0; $i < $times; $i++){
            $requests["age_{$i}"] = ['api' => 'test', 'method' => 'getAge', 'params' => []];
            $requests["name_{$i}"] = ['api' => 'test', 'method' => 'getName', 'params' => ['test']];
            $requests["info_{$i}"] = ['api' => 'db', 'method' => 'getInfo', 'params' => [$this->getInfoId()]];
            $requests["list_{$i}"] = ['api' => 'db', 'method' => 'getList', 'params' => [$this->getStart(), $this->listLimit]];
        }
        $client = $this->getClient('multiple');
        return $client->calls($requests);
    }
}

/**
 * Class Timer
 */
class Timer{
    protected $startTime;

    function __construct($autoStart = true){
        if($autoStart){
            $this->start();
        }
    }

    function start(){
        $this->startTime = $this->_time();
    }

    function stop($echo = false, $str = ''){
        $time = $this->_time();
        $times = round($time - $this->startTime, 5);
        $this->startTime = $time;

        if($echo){
            echo $str . $times . "\n";
        }
        return $times;
    }

    protected function _time(){
        $mtime = microtime ();
        $mtime = explode (' ', $mtime);
        return $mtime[1] + $mtime[0];
    }
}


/**
 * Class SimpleProcessorManager
 * @package j\debug
 */
class SimpleProcessorManager{
    protected $works = [];
    protected $workNums;
    protected function start($callback){
        for($i = 0; $i < $this->workNums; $i++) {
            $process = new swoole_process($callback, false, false);
            $pid = $process->start();
            $workers[$pid] = $process;
        }
    }

    function run($workNums, $callback){
        $this->workNums = $workNums;
        $this->start($callback);
        $this->close();
    }

    protected function close(){
        for($i = 0; $i < $this->workNums; $i++) {
            $ret = swoole_process::wait();
            $pid = $ret['pid'];
            echo "Worker Exit, PID=" . $pid . PHP_EOL;
        }
    }
}

