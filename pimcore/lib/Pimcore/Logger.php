<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore;

use Psr\Log\LogLevel;

class Logger
{

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
    public static function getAvailablePriorities()
    {
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
    public static function setLogger($logger)
    {
        self::$logger = [];
        self::$logger[] = $logger;
        self::$enabled = true;
    }

    /**
     * @param $logger
     */
    public static function removeLogger($logger)
    {
        $pos = array_search($logger, self::$logger);
        array_splice(self::$logger, $pos, 1);
    }

    /**
     *
     */
    public static function resetLoggers()
    {
        self::$logger = [];
    }

    /**
     * @param $logger
     * @param bool|false $reset
     * @throws Exception
     */
    public static function addLogger($logger, $reset = false)
    {
        if (!$logger instanceof \Zend_Log && !$logger instanceof \Psr\Log\LoggerInterface) {
            throw new \Exception("Logger must be either an instance of Zend_Log or needs to implement Psr\\Log\\LoggerInterface");
        }

        if ($reset) {
            self::$logger = [];
        }
        self::$logger[] = $logger;
        self::$enabled = true;
    }

    /**
     * @return array
     */
    public static function getLogger()
    {
        return self::$logger;
    }

    /**
     * @param $prios
     */
    public static function setPriorities($prios)
    {
        self::$priorities = $prios;
    }

    /**
     * return priorities, an array of log levels that will be logged by this logger
     *
     * @return array
     */
    public static function getPriorities()
    {
        return self::$priorities;
    }

    /**
     *
     */
    public static function initDummy()
    {
        self::$enabled = false;
    }

    /**
     *
     */
    public static function disable()
    {
        self::$enabled = false;
    }

    /**
     *
     */
    public static function enable()
    {
        self::$enabled = true;
    }

    /**
     *
     */
    public static function setVerbosePriorities()
    {
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
     * @return array
     */
    public static function getZendLoggerPsr3Mapping()
    {
        return [
            \Zend_Log::DEBUG => LogLevel::DEBUG,
            \Zend_Log::INFO => LogLevel::INFO,
            \Zend_Log::NOTICE => LogLevel::NOTICE,
            \Zend_Log::WARN => LogLevel::WARNING,
            \Zend_Log::ERR => LogLevel::ERROR,
            \Zend_Log::CRIT => LogLevel::CRITICAL,
            \Zend_Log::ALERT => LogLevel::ALERT,
            \Zend_Log::EMERG => LogLevel::EMERGENCY
        ];
    }

    /**
     * @param $message
     * @param string $level
     * @param array $context
     * @internal param string $code
     */
    public static function log($message, $level = "info", $context = [])
    {
        if (!self::$enabled) {
            return;
        }

        // backward compatibility of level definitions
        // Zend_Logger compatibility
        $zendLoggerPsr3Mapping = self::getZendLoggerPsr3Mapping();

        if (array_key_exists($level, $zendLoggerPsr3Mapping)) {
            $level = $zendLoggerPsr3Mapping[$level];
        }

        if (!is_array($context)) {
            $context = [];
        }


        if (in_array($level, self::$priorities)) {
            $backtrace = debug_backtrace();

            if (!isset($backtrace[2])) {
                $call = ['class' => '', 'type' => '', 'function' => ''];
            } else {
                $call = $backtrace[2];
            }

            $call["line"] = $backtrace[1]["line"];

            if (is_object($message) || is_array($message)) {
                if (!$message instanceof \Exception) {
                    $message = print_r($message, true);
                }
            }

            $context["origin"] = $call["class"] . $call["type"] . $call["function"] . "() on line " . $call["line"];

            // add the memory consumption
            $memory = formatBytes(memory_get_usage(), 0);
            $memory = str_pad($memory, 6, " ", STR_PAD_LEFT);

            $context["memory"] = $memory;

            foreach (self::$logger as $logger) {
                if ($logger instanceof \Psr\Log\LoggerInterface) {
                    $logger->log($level, $message, $context);
                } else {
                    // Zend_Log backward compatibility
                    $zendLoggerPsr3ReverseMapping = array_flip($zendLoggerPsr3Mapping);
                    $zfCode = $zendLoggerPsr3ReverseMapping[$level];
                    $logger->log($message, $zfCode);
                }
            }
        }
    }


    /**
     * $l is for backward compatibility
     **/

    /**
     * @param $m
     * @param array $context
     */
    public static function emergency($m, $context = [])
    {
        self::log($m, "emergency", $context);
    }

    /**
     * @param $m
     * @param array $context
     */
    public static function emerg($m, $context = [])
    {
        self::log($m, "emergency", $context);
    }

    /**
     * @param $m
     * @param array $context
     */
    public static function alert($m, $context = [])
    {
        self::log($m, "alert", $context);
    }

    /**
     * @param $m
     * @param array $context
     */
    public static function critical($m, $context = [])
    {
        self::log($m, "critical", $context);
    }

    /**
     * @param $m
     * @param array $context
     */
    public static function crit($m, $context = [])
    {
        self::log($m, "critical", $context);
    }

    /**
     * @param $m
     * @param array $context
     */
    public static function error($m, $context = [])
    {
        self::log($m, "error", $context);
    }

    /**
     * @param $m
     * @param array $context
     */
    public static function err($m, $context = [])
    {
        self::log($m, "error", $context);
    }

    /**
     * @param $m
     * @param array $context
     */
    public static function warning($m, $context = [])
    {
        self::log($m, "warning", $context);
    }

    /**
     * @param $m
     * @param array $context
     */
    public static function warn($m, $context = [])
    {
        self::log($m, "warning", $context);
    }

    /**
     * @param $m
     * @param array $context
     */
    public static function notice($m, $context = [])
    {
        self::log($m, "notice", $context);
    }

    /**
     * @param $m
     * @param array $context
     */
    public static function info($m, $context = [])
    {
        self::log($m, "info", $context);
    }

    /**
     * @param $m
     * @param array $context
     */
    public static function debug($m, $context = [])
    {
        self::log($m, "debug", $context);
    }

    /**
     * Get the PSR logger with the name core (if defined)
     *
     * @return \Monolog\Logger|null
     */
    public static function getPsrCoreLogger()
    {
        foreach (static::$logger as $logger) {
            if ($logger instanceof \Monolog\Logger && $logger->getName() === 'core') {
                return $logger;
            }
        }
    }

    /**
     * Create a named logger from the core one (e.g. for cache, db, ...)
     *
     * @param $name
     * @return \Monolog\Logger|null
     */
    public static function createNamedPsrLogger($name)
    {
        $coreLogger = static::getPsrCoreLogger();

        if ($coreLogger) {
            return new \Monolog\Logger(
                $name,
                $coreLogger->getHandlers(),
                $coreLogger->getProcessors()
            );
        }
    }
}
