<?php

namespace syar;

/**
 * Class Token
 * @package syar
 */
class Token {
	private $class;
	private $method;
	private $args;
	private $options = [];

	/**
	 * Token constructor.
	 * @param $class
	 * @param $method
	 * @param $args
	 * @param $options
	 */
	public function __construct($class, $method, $args, $options = []){
		$this->class = $class;
		$this->method = $method;
		$this->args = $args;
		$this->options = $options;
	}

	/**
	 * @return mixed
	 */
	public function getClass(){
		return trim($this->class, '\\/ ');
	}

	/**
	 * @return mixed
	 */
	public function getMethod(){
		return $this->method;
	}

	/**
	 * @return mixed
	 */
	public function getArgs(){
		return $this->args;
	}

	/**
	 * @param $key
	 * @param null $def
	 * @return mixed|null
	 */
	public function getOption($key, $def = null){
		return isset($this->options[$key]) ? $this->options[$key] : $def;
	}

	/**
	 * @return string
	 */
	public function getApi(){
		$api = $this->class ?: $this->getOption('api');
		$api = str_replace('/', ".", $api);
		if(!$api){
			return '';
		}

		if($this->method){
			$api .= "." . $this->method;
		}
		return $api;
	}
}