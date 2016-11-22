<?php

namespace syar\encoder;

/**
 * Class EncoderJson
 * @package syar\base
 */
class EncoderJson implements EncoderInterface {
	function encode($message) {
		return json_encode($message);
	}

	function decode($message) {
		return json_decode($message, true);
	}
}