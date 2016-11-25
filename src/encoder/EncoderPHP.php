<?php

namespace syar\encoder;

/**
 * Class EncoderPHP
 * @package syar\base
 */
class EncoderPHP implements EncoderInterface {
	function encode($message) {
		return serialize($message);
	}

	function decode($message) {
		return unserialize($message);
	}
}
