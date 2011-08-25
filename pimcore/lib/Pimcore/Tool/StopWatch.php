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

class Pimcore_Tool_StopWatch {

    /**
     * @var float
     */
    protected static $startTime;

    /**
     * @var array
     */
    protected static $laps = array();

    /**
     * @static
     * @return void
     */
    public static function start () {
        self::$startTime = self::microtime_float();
        self::$laps = array();
    }

    /**
     * @static
     * @param $label
     * @return void
     */
    public static function lap ($label) {
        self::$laps[$label] = self::microtime_float();
    }

    /**
     * @static
     * @param bool $html
     * @return void
     */
    public static function display ($html = false) {

        $text = "";

        $lastLap = self::$startTime;
        foreach (self::$laps as $label => $time) {
            $text .= "Lap " . $label . "\tAccum: " . ($time-self::$startTime) . "\t Self: " . ($time-$lastLap) . "\n";

            $lastLap = $time;
        }

        $text .= "Total Time: " . (self::microtime_float() - self::$startTime) . "\n";

        if($html) {
            $text = "<pre>".$text."</pre>";
        }

        echo $text;
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
