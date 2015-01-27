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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Tool;

class StopWatch {

    /**
     * @var array
     */
    protected static $startTime = array();

    /**
     * @var array
     */
    protected static $laps = array();

    /**
     * @param $component string
     * @static
     * @return void
     */
    public static function start ($component = 'default') {
        self::$startTime[$component] = self::microtime_float();
        self::$laps[$component] = array();
    }

    /**
     * @static
     * @param $label
     * @param string $component
     * @return void
     */
    public static function lap ($label,$component = 'default') {
        self::$laps[$component][$label] = self::microtime_float();
    }

    /**
     * @static
     * @param bool $html
     * @param string $component
     * @return string
     */
    public static function getTime($html = false, $component = 'default') {
        $text = "";

        $lastLap = self::$startTime[$component];
        foreach (self::$laps[$component] as $label => $time) {
            $text .= "Lap " . $label . "\tAccum: " . ($time - self::$startTime[$component]) . "\t Self: " . ($time - $lastLap)
                . "\n";

            $lastLap = $time;
        }

        $text .= "Total Time: " . (self::microtime_float() - self::$startTime[$component]) . "\n";

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
    public static function display($html = false, $component = 'default') {
        echo self::getTime($html,$component);
    }

    /**
     * @static
     * @return float
     */
    public static function microtime_float() {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }
}
