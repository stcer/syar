<?php

namespace syar;

/**
 * Class PackerTest
 * @package syar
 */
class PackerTest extends \PHPUnit_Framework_TestCase{

	function testUnpack(){
		$packer = new Packer();
		$yar = $packer->unpack($this->getData());

		$this->assertEquals($yar->packer['packName'], 'MSGPACK');
		$this->assertEquals($yar->getRequestMethod(), 'add');
		$this->assertEquals($yar->getRequestParams(), [1, 2]);
	}

	function testPack(){
		$packer = new Packer();
		$requestData = $this->getData();
		$yar = $packer->unpack($requestData);

		$rs = ['test'];
		$yar->setReturnValue($rs);

		$responseData = $this->getData(2, $rs);
		$packData = $packer->pack($yar);

		$this->assertEquals($packData, $responseData);
	}

	protected function getData($type = 1, $rs = []){
		$data = [
			'i' => '3594717145',
			'm' => 'add',
			'p' => [1, 2],
			];
		if($type == 2){
			$data = [
				'r' => $rs,
				'i' => $data['i'],
				's' => 0
			];
		}

		$header = [
			'id' => 12903494,
			'Version' => 0,
			'MagicNum' => 0x80DFEC60,
			'Reserved' => 0,
			'Provider' => '',
			'Token' => '',
			'BodyLen' => 27,
		];
		$data = msgpack_pack($data);
		$header['BodyLen'] = strlen($data) + 8;
		$header = pack('NnNNa32a32N',
			$header['id'],
			$header['Version'],
			$header['MagicNum'],
			$header['Reserved'],
			$header['Provider'],
			$header['Token'],
			$header['BodyLen']
		);
		return $header . "MSGPACK " . $data;
	}
}