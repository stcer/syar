<?php

namespace syar\example\service;

/**
 * Class Test
 * @package syar\example\service
 */
class Test {
	public function getName($userName){
		return $userName . " Hello";
	}

	public function getAge(){
		return 20;
	}
}