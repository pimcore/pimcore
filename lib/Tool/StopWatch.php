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

namespace Pimcore\Tool;

/**
 * @deprecated
 */
class StopWatch
{
    /**
     * @var array
     */
    protected static $startTime = [];

    /**
     * @var array
     */
    protected static $laps = [];

    /**
     * @param string $component
     * @static
     */
    public static function start($component = 'default')
    {
        self::$startTime[$component] = microtime(true);
        self::$laps[$component] = [];
    }

    /**
     * @static
     *
     * @param string $label
     * @param string $component
     */
    public static function lap($label, $component = 'default')
    {
        self::$laps[$component][$label] = microtime(true);
    }

    /**
     * @static
     *
     * @param bool $html
     * @param string $component
     *
     * @return string
     */
    public static function getTime($html = false, $component = 'default')
    {
        $text = '';

        $lastLap = self::$startTime[$component];
        foreach (self::$laps[$component] as $label => $time) {
            $text .= 'Lap ' . $label . "\tAccum: " . ($time - self::$startTime[$component]) . "\t Self: " . ($time - $lastLap)
                . "\n";

            $lastLap = $time;
        }

        $text .= 'Total Time (' . $component . '): ' . (microtime(true) - self::$startTime[$component]) . "\n";

        if ($html) {
            $text = '<pre>' . $text . '</pre>';
        }

        return $text;
    }

    /**
     * @static
     *
     * @param string $component
     * @param bool $html
     */
    public static function display($html = false, $component = 'default')
    {
        echo self::getTime($html, $component);
    }

    /**
     * @deprecated
     * @static
     *
     * @return float
     */
    public static function microtime_float()
    {
        return microtime(true);
    }
}
