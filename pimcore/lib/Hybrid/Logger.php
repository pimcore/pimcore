<?php

/**
 * HybridAuth
 * http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
 * (c) 2009-2015, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
 */

/**
 * Debugging and Logging manager
 */
class Hybrid_Logger {

	/**
	 * Constructor
	 */
	function __construct() {
		// if debug mode is set to true, then check for the writable log file
		if (Hybrid_Auth::$config["debug_mode"]) {
			if (!isset(Hybrid_Auth::$config["debug_file"])) {
				throw new Exception("'debug_mode' is set to 'true' but no log file path 'debug_file' is set.", 1);
			} elseif (!file_exists(Hybrid_Auth::$config["debug_file"]) && !is_writable(Hybrid_Auth::$config["debug_file"])) {
				if (!touch(Hybrid_Auth::$config["debug_file"])) {
					throw new Exception("'debug_mode' is set to 'true', but the file " . Hybrid_Auth::$config['debug_file'] . " in 'debug_file' can not be created.", 1);
				}
			} elseif (!is_writable(Hybrid_Auth::$config["debug_file"])) {
				throw new Exception("'debug_mode' is set to 'true', but the given log file path 'debug_file' is not a writable file.", 1);
			}
		}
	}

	/**
	 * Logs a debug message with an object dump
	 *
	 * @param string   $message Debug message
	 * @param stdClass $object  Object being debugged
	 * @return void
	 */
	public static function debug($message, $object = null) {
		if (Hybrid_Auth::$config["debug_mode"] === true) {
      $dt = new DateTime('now', new DateTimeZone( 'UTC' ));
			file_put_contents(Hybrid_Auth::$config["debug_file"], implode(' -- ', array(
				"DEBUG",
				$_SERVER['REMOTE_ADDR'],
				$dt->format(DATE_ATOM),
				$message,
				print_r($object, true) . PHP_EOL,
					)), FILE_APPEND
			);
		}
	}

	/**
	 * Logs an info message
	 *
	 * @param string $message Info message
	 * @return void
	 */
	public static function info($message) {
		if (in_array(Hybrid_Auth::$config["debug_mode"], array(true, 'info'), true)) {
      $dt = new DateTime('now', new DateTimeZone( 'UTC' ));
			file_put_contents(Hybrid_Auth::$config["debug_file"], implode(' -- ', array(
				"INFO",
				$_SERVER['REMOTE_ADDR'],
				$dt->format(DATE_ATOM),
				$message . PHP_EOL,
					)), FILE_APPEND);
		}
	}

	/**
	 * Logs an error message with an object dump
	 *
	 * @param string   $message Error message
	 * @param stdClass $object  Object being debugged
	 * @return void
	 */
	public static function error($message, $object = null) {
		if (isset(Hybrid_Auth::$config["debug_mode"]) && in_array(Hybrid_Auth::$config["debug_mode"], array(true, 'info', 'error'), true)) {
      $dt = new DateTime('now', new DateTimeZone( 'UTC' ));
			file_put_contents(Hybrid_Auth::$config["debug_file"], implode(' -- ', array(
				'ERROR',
				$_SERVER['REMOTE_ADDR'],
				$dt->format(DATE_ATOM),
				$message,
				print_r($object, true) . PHP_EOL
					)), FILE_APPEND);
		}
	}

}
