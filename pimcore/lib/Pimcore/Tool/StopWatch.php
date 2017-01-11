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

namespace Pimcore\Tool;

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
     * @param $component string
     * @static
     * @return void
     */
    public static function start($component = 'default')
    {
        self::$startTime[$component] = self::microtime_float();
        self::$laps[$component] = [];
    }

    /**
     * @static
     * @param $label
     * @param string $component
     * @return void
     */
    public static function lap($label, $component = 'default')
    {
        self::$laps[$component][$label] = self::microtime_float();
    }

    /**
     * @static
     * @param bool $html
     * @param string $component
     * @return string
     */
    public static function getTime($html = false, $component = 'default')
    {
        $text = "";

        $lastLap = self::$startTime[$component];
        foreach (self::$laps[$component] as $label => $time) {
            $text .= "Lap " . $label . "\tAccum: " . ($time - self::$startTime[$component]) . "\t Self: " . ($time - $lastLap)
                . "\n";

            $lastLap = $time;
        }

        $text .= "Total Time (" . $component . "): " . (self::microtime_float() - self::$startTime[$component]) . "\n";

        if ($html) {
            $text = "<pre>" . $text . "</pre>";
        }

        return $text;
    }

    /**
     * @static
     * @param string $component
     * @param bool $html
     * @return void
     */
    public static function display($html = false, $component = 'default')
    {
        echo self::getTime($html, $component);
    }

    /**
     * @static
     * @return float
     */
    public static function microtime_float()
    {
        return microtime(true);
    }
}
