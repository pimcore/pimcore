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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore;

class Logger
{
    /**
     * @param $message
     * @param string $level
     * @param array $context
     *
     * @internal param string $code
     */
    public static function log($message, $level = 'info', $context = [])
    {
        if (\Pimcore::hasContainer()) {
            $logger = \Pimcore::getContainer()->get('monolog.logger.pimcore');
            $logger->$level($message, $context);
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
        self::log($m, 'emergency', $context);
    }

    /**
     * @param $m
     * @param array $context
     */
    public static function emerg($m, $context = [])
    {
        self::log($m, 'emergency', $context);
    }

    /**
     * @param $m
     * @param array $context
     */
    public static function alert($m, $context = [])
    {
        self::log($m, 'alert', $context);
    }

    /**
     * @param $m
     * @param array $context
     */
    public static function critical($m, $context = [])
    {
        self::log($m, 'critical', $context);
    }

    /**
     * @param $m
     * @param array $context
     */
    public static function crit($m, $context = [])
    {
        self::log($m, 'critical', $context);
    }

    /**
     * @param $m
     * @param array $context
     */
    public static function error($m, $context = [])
    {
        self::log($m, 'error', $context);
    }

    /**
     * @param $m
     * @param array $context
     */
    public static function err($m, $context = [])
    {
        self::log($m, 'error', $context);
    }

    /**
     * @param $m
     * @param array $context
     */
    public static function warning($m, $context = [])
    {
        self::log($m, 'warning', $context);
    }

    /**
     * @param $m
     * @param array $context
     */
    public static function warn($m, $context = [])
    {
        self::log($m, 'warning', $context);
    }

    /**
     * @param $m
     * @param array $context
     */
    public static function notice($m, $context = [])
    {
        self::log($m, 'notice', $context);
    }

    /**
     * @param $m
     * @param array $context
     */
    public static function info($m, $context = [])
    {
        self::log($m, 'info', $context);
    }

    /**
     * @param $m
     * @param array $context
     */
    public static function debug($m, $context = [])
    {
        self::log($m, 'debug', $context);
    }
}
