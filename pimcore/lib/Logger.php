<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

use Psr\Log\LogLevel;

class Logger {

    /**
     * @var array
     */
	private static $logger = [];

    /**
     * @var array
     */
	private static $priorities = [];

    /**
     * @var bool
     */
	private static $enabled = false;

    /**
     * @return array
     */
    public static function getAvailablePriorities() {
        return [
            LogLevel::EMERGENCY,
            LogLevel::ALERT,
            LogLevel::CRITICAL,
            LogLevel::ERROR,
            LogLevel::WARNING,
            LogLevel::NOTICE,
            LogLevel::INFO,
            LogLevel::DEBUG,
        ];
    }

    /**
     * @param $logger
     */
	public static function setLogger ($logger) {
        self::$logger = array();
		self::$logger[] = $logger;
        self::$enabled = true;
	}

    /**
     *
     */
    public static function resetLoggers() {
        self::$logger = array();
    }

    /**
     * @param $logger
     * @param bool|false $reset
     * @throws Exception
     */
    public static function addLogger ($logger,$reset = false) {

        if(!$logger instanceof \Zend_Log && !$logger instanceof \Psr\Log\LoggerInterface) {
            throw new \Exception("Logger must be either an instance of Zend_Log or needs to implement Psr\\Log\\LoggerInterface");
        }

        if($reset) {
            self::$logger = array();
        }
        self::$logger[] = $logger;
        self::$enabled = true;
    }

    /**
     * @return array
     */
    public static function getLogger () {
		return self::$logger;
	}

    /**
     * @param $prios
     */
	public static function setPriorities ($prios) {
		self::$priorities = $prios;
	}

    /**
     * return priorities, an array of log levels that will be logged by this logger
     *
     * @return array
     */
    public static function getPriorities () {
        return self::$priorities;
    }

    /**
     *
     */
	public static function initDummy() {
		self::$enabled = false;
	}

    /**
     *
     */
    public static function disable() {
        self::$enabled = false;
    }

    /**
     *
     */
    public static function enable() {
        self::$enabled = true;
    }

    /**
     *
     */
    public static function setVerbosePriorities() {
        self::setPriorities([
            "debug",
            "info",
            "notice",
            "warning",
            "error",
            "critical",
            "alert",
            "emergency"
        ]);
    }

    /**
     * @param $message
     * @param string $code
     * @param array $context
     */
	public static function log ($message, $level = "info", $context = []) {
		
		if(!self::$enabled) {
			return;
		}

        // backward compatibility of level definitions
        // Zend_Logger compatibility
        $zendLoggerPsr3Mapping = array(
            \Zend_Log::DEBUG => LogLevel::DEBUG,
            \Zend_Log::INFO => LogLevel::INFO,
            \Zend_Log::NOTICE => LogLevel::NOTICE,
            \Zend_Log::WARN => LogLevel::WARNING,
            \Zend_Log::ERR => LogLevel::ERROR,
            \Zend_Log::CRIT => LogLevel::CRITICAL,
            \Zend_Log::ALERT => LogLevel::ALERT,
            \Zend_Log::EMERG => LogLevel::EMERGENCY
        );

        if(array_key_exists($level, $zendLoggerPsr3Mapping)) {
            $level = $zendLoggerPsr3Mapping[$level];
        }

        if(!is_array($context)) {
            $context = [];
        }


		if(in_array($level,self::$priorities)) {

            $backtrace = debug_backtrace();

            if (!isset($backtrace[2])) {
                $call = array('class' => '', 'type' => '', 'function' => '');
            } else {
                $call = $backtrace[2];
            }

            $call["line"] = $backtrace[1]["line"];

            if(is_object($message) || is_array($message)) {
				if(!$message instanceof Exception) {
                    $message = print_r($message,true);
				}
			}

            $context["origin"] = $call["class"] . $call["type"] . $call["function"] . "() on line " . $call["line"];

            // add the memory consumption
            $memory = formatBytes(memory_get_usage(), 0);
            $memory = str_pad($memory, 6, " ", STR_PAD_LEFT);

            $context["memory"] = $memory;

            foreach (self::$logger as $logger) {
                if($logger instanceof \Psr\Log\LoggerInterface) {
                    $logger->log($level,$message,$context);
                } else {
                    // Zend_Log backward compatibility
                    $zendLoggerPsr3ReverseMapping = array_flip($zendLoggerPsr3Mapping);
                    $zfCode = $zendLoggerPsr3ReverseMapping[$level];
                    $logger->log($message,$zfCode);
                }
            }
		}
	}
    
    
    /**
     * $l is for backward compatibility
     **/
    
     public static function emergency ($m, $context = []) {
        self::log($m, "emergency", $context);
    }
    
    public static function emerg ($m, $context = []) {
        self::log($m, "emergency", $context);
    }

    public static function alert ($m, $context = []) {
        self::log($m, "alert", $context);
    }

    public static function critical ($m, $context = []) {
        self::log($m, "critical", $context);
    }
    
    public static function crit ($m, $context = []) {
        self::log($m, "critical", $context);
    }
    
    public static function error ($m, $context = []) {
        self::log($m, "error", $context);
    }
    
    public static function err ($m, $context = []) {
        self::log($m, "error", $context);
    }
    
    public static function warning ($m, $context = []) {
        self::log($m, "warning", $context);
    }
    
    public static function warn ($m, $context = []) {
        self::log($m, "warning", $context);
    }
    
    public static function notice ($m, $context = []) {
        self::log($m, "notice", $context);
    }
    
    public static function info ($m, $context = []) {
        self::log($m, "info", $context);
    }
    
    public static function debug ($m, $context = []) {
        self::log($m, "debug", $context);
    }
}
