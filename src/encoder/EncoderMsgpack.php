<?php

namespace syar\encoder;

/**
 * Class EncoderMsgpack
 * @package syar\base
 */
class EncoderMsgpack implements EncoderInterface {
	function encode($message) {
		return msgpack_pack($message);
	}

	function decode($message) {
		return msgpack_unpack($message);
	}
}