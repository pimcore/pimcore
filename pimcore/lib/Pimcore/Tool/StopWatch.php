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
