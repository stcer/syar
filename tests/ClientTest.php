<?php

/**
 * ÒÀÀµ example/server1.php·şÎñÆô¶¯
 * Class PackerTest
 * @package syar
 */
class ClientTest extends \PHPUnit_Framework_TestCase{
	/**
	 * @param $api
	 * @return Yar_Client
	 */
	protected function getYarClient($api = 'test') {
		return new Yar_client($this->getUrl($api));
	}

	protected function getUrl($api = 'test'){
		return $url = "http://127.0.0.1:5604/{$api}";
	}

	function tesRpc1(){
		$client = $this->getYarClient();
		$name = $client->getName("tester");
		$age = $client->getAge();

		$this->assertEquals($name, 'tester Hello');
		$this->assertEquals($age, 20);
	}

	function tesConcurrentClient(){
		$url = $this->getUrl();

		$data = [];
		Yar_Concurrent_Client::call($url, "getName", ['tester'],
			function($rs) use (& $data){
				$data['name'] = $rs;
			}
		);
		Yar_Concurrent_Client::call($url, "getAge", [],
			function($rs) use (& $data){
				$data['age'] = $rs;
			}
		);
		Yar_Concurrent_Client::loop();

		$this->assertEquals($data['name'], 'tester Hello');
		$this->assertEquals($data['age'], 20);
	}

	function testBatch(){
		$client = $this->getYarClient('multiple');
		$requests = [
			'age' => [
				'api' => '/test',
				'method' => 'getAge',
				'params' => []
				],
			'name' => [
				'api' => '/test',
				'method' => 'getName',
				'params' => ['tester']
			]];
		$rs = $client->calls($requests);
		$this->assertEquals($rs['name'], 'tester Hello');
		$this->assertEquals($rs['age'], 20);
	}
}