<?php

namespace syar\encoder;

/**
 * Interface Encoder
 * @package syar\base
 */
interface EncoderInterface {
	function encode($message);
	function decode($message);
}
