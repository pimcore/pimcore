<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */
 
class Logger {
	
	private static $logger = array();
	private static $priorities = array();
	private static $enabled = false;
	
	public static function setLogger ($logger) {
        self::$logger = array();
		self::$logger[] = $logger;
        self::$enabled = true;
	}

    public static function resetLoggers() {
        self::$logger = array();
    }
    
    public static function addLogger ($logger,$reset = false) {
        if($reset) {
            self::$logger = array();
        }
        self::$logger[] = $logger;
        self::$enabled = true;
    }
    
    public static function getLogger () {
		return self::$logger;
	}
	
	public static function setPriorities ($prios) {
		self::$priorities = $prios;
	}
	
	public static function initDummy() {
		self::$enabled = false;
	}

    public static function disable() {
        self::$enabled = false;
    }

    public static function enable() {
        self::$enabled = true;
    }
	
	public static function log ($message,$code=Zend_Log::INFO) {
		
		if(!self::$enabled) {
			return;
		}
		
		if(in_array($code,self::$priorities)) {

            $backtrace = debug_backtrace();
            $call = $backtrace[2];
            $call["line"] = $backtrace[1]["line"];

            if(is_object($message) || is_array($message)) {
                // special formatting for exception
				if($message instanceof Exception) {
					$message = $call["class"] . $call["type"] . $call["function"] . "() [" . $call["line"] . "]: [Exception] with message: ".$message->getMessage()
                        ."\n"
                        ."In file: "
                        .$message->getFile()
                        . " on line "
                        .$message->getLine()
                        ."\n"
                        .$message->getTraceAsString();
				}
				else {
					$message = print_r($message,true);
				}
			} else {
                $message = $call["class"] . $call["type"] . $call["function"] . "() [" . $call["line"] . "]: " . $message;
            }

            // add the memory consumption
            $memory = formatBytes(memory_get_usage(), 0);
            $memory = str_pad($memory, 6, " ", STR_PAD_LEFT);

            $message = $memory . " | " . $message;

            foreach (self::$logger as $logger) {
                $logger->log($message,$code);
            }
		}
	}
    
    
    /**
     * $l is for backward compatibility
     **/
    
     public static function emergency ($m, $l = null) {
        self::log($m,Zend_Log::EMERG);
    }
    
    public static function emerg ($m, $l = null) {
        self::log($m,Zend_Log::EMERG);
    }
    
    public static function critical ($m, $l = null) {
        self::log($m,Zend_Log::CRIT);
    }
    
    public static function crit ($m, $l = null) {
        self::log($m,Zend_Log::CRIT);
    }
    
    public static function error ($m, $l = null) {
        self::log($m,Zend_Log::ERR);
    }
    
    public static function err ($m, $l = null) {
        self::log($m,Zend_Log::ERR);
    }
    
    public static function alert ($m, $l = null) {
        self::log($m,Zend_Log::ALERT);
    }
    
    public static function warning ($m, $l = null) {
        self::log($m,Zend_Log::WARN);
    }
    
    public static function warn ($m, $l = null) {
        self::log($m,Zend_Log::WARN);
    }
    
    public static function notice ($m, $l = null) {
        self::log($m,Zend_Log::NOTICE);
    }
    
    public static function info ($m, $l = null) {
        self::log($m,Zend_Log::INFO);
    }
    
    public static function debug ($m, $l = null) {
        self::log($m,Zend_Log::DEBUG);
    }
}
