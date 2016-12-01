<?php

namespace syar\example;

# 依赖服务 ../server1.php
$vendorPath = realpath(__DIR__ . "/../vendor/");
$loader = include($vendorPath . "/autoload.php");


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

use Yar_Client;
use Yar_Concurrent_Client;

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
     * @return int
     */
    private function getInfoId(){
        return rand(105, 225);
    }

    /**
     * @param int $limit
     * @return int
     */
    private function getStart($limit = 5){
        $start = rand($limit, 72) - $limit;
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

    function dbTest($limit = 5){
        $client = $this->getClient('db');
        $info = $client->getInfo($this->getInfoId());
        $list = $client->getList($this->getStart(), $limit);
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
     * @param int $limit
     * @return array
     */
    function concurrentTest($times = 5, $limit = 5){
        $data = [];

        $url1 = $this->getClient('test', true);
        $url2 = $this->getClient('db', true);
        for($i = 0; $i < $times; $i++){
            $this->concurrentCall($data, $url1, 'getName', [rand(0, 245301)], 'name_' . $i);
            $this->concurrentCall($data, $url1, 'getAge', [], 'age_' . $i);
            $this->concurrentCall($data, $url2, 'getInfo', [$this->getInfoId()], 'info_' . $i);
            $this->concurrentCall($data, $url2, 'getList', [$this->getStart(), $limit], 'list_' . $i);
        }
        Yar_Concurrent_Client::loop();
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

    /**
     * @param int $times
     * @param int $limit
     * @return array
     */
    function batchTest($times = 5, $limit = 5) {
        if($this->isFpm()){
            return $this->concurrentTest($times, $limit);
        }
        $requests = [];
        for($i = 0; $i < $times; $i++){
            $requests["age_{$i}"] = ['api' => 'test', 'method' => 'getAge', 'params' => []];
            $requests["name_{$i}"] = ['api' => 'test', 'method' => 'getName', 'params' => ['test']];
            $requests["info_{$i}"] = ['api' => 'db', 'method' => 'getInfo', 'params' => [$this->getInfoId()]];
            $requests["list_{$i}"] = ['api' => 'db', 'method' => 'getList', 'params' => [$this->getStart(), $limit]];
        }
        $client = $this->getClient('multiple');
        return $client->calls($requests);
    }
}

function test($type, $times = 5, $limit = 5){
    $timer = new Timer();
    $benchmark = new Benchmark($type);
    $rs[] = $benchmark->simpleTest(); // 2
    $rs[] = $benchmark->dbTest($limit); // 2
    $rs[] = $benchmark->batchTest($times, $limit); // 20
    $rs[] = $benchmark->concurrentTest($times, $limit); // 20
    $stop = $timer->stop();

    // 44 calls, 22 db, 22 normal
    foreach($rs as $v){
        var_dump($v);
    }

    return $stop;
}

// start test
$times['syar'] = test('syar');
$times['fpm'] = test('fpm');
var_dump($times);
//

