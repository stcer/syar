<?php

namespace syar;

/**
 * Class Debug
 * @package j\debug
 */
class Debug {
    static $lastError;
	/**
	 * @return string
	 */
	public static function traceError(){
		$error = self::$lastError = error_get_last();
		if (!isset($error['type'])){
			return '';
		}

		if(!in_array($error['type'], [
			E_ERROR,
			E_PARSE,
			E_DEPRECATED,
			E_CORE_ERROR,
			E_COMPILE_ERROR,
		])){
			return '';
		}

		$message = $error['message'];
		$file = $error['file'];
		$file = self::getFilePath($file);
		$line = $error['line'];
		$log = "$message ($file:$line)\nStack trace:\n";
		$log .= self::trace();

		if (isset($_SERVER['REQUEST_URI'])) {
			$log .= '[QUERY] ' . $_SERVER['REQUEST_URI'];
		}

		return $log;
	}

	public static function trace($n = 0, $start = 0){
		$log = '';
		$trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, $n);
		foreach ($trace as $i => $t)  {
			if (!isset($t['file'])) {
				$t['file'] = 'unknown';
			} else {
				$t['file'] = self::getFilePath($t['file']);
			}
			if (!isset($t['line'])) {
				$t['line'] = 0;
			}
			if (!isset($t['function'])) {
				$t['function'] = 'unknown';
			}
			$log .= "#$i {$t['file']}({$t['line']}): ";
			if (isset($t['object']) && is_object($t['object'])){
				$log .= get_class($t['object']) . '->';
			}
			$log .= "{$t['function']}()\n";
		}

		if($start && count($log) > $start){
			$log = array_slice($log, $start);
		}

		return $log;
	}

	protected static function getFilePath($file){
		if(defined('PATH_ROOT')){
			return str_replace(PATH_ROOT, '', $file);
		} else {
			$root = preg_replace('/^(.+?)\/vender/', '$0', __FILE__);
			return str_replace($root, '', $file);
		}
	}
}