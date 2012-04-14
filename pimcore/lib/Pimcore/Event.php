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

class Pimcore_Event {

    /**
     * @var array
     */
    private static $events = array();

    function reset() {
        self::$events = array();
    }

    /**
     * @static
     * @param $name
     * @param $function
     * @param array $arguments
     * @param int $index
     */
    public static function register ($name, $function, $arguments = array(), $index = null) {

        if (!isset(self::$events[$name])) {
            self::$events[$name] = array();
        }

        if (!isset(self::$events[$name][$index])) {
            self::$events[$name][$index] = array();
        }

        self::$events[$name][$index][] = array(
            "function" => $function,
            "arguments" => $arguments
        );

        ksort(self::$events[$name]);
    }

    /**
     * @static
     * @param $name
     * @param $function
     */
    public static function unregister ($name, $function=null) {

        if(!isset(self::$events[$name]))
            return;

        if($function === null) {
            unset(self::$events[$name]);
            return;
        }

        foreach (self::$events[$name] as $k => $priorityEvents) {
            foreach($priorityEvents as $ik => $item) {
                if($item['function'] == $function)
                    unset(self::$events[$name][$k][$ik]);
            }
        }

        if(empty(self::$events[$name]))
            unset(self::$events[$name]);
    }

    /**
     * @static
     * @param $name
     * @param array $arguments
     * @return mixed
     */
    public static function fire ($name, $arguments = array()) {

        if(!isset(self::$events[$name])) {
            return;
        }

        foreach (self::$events[$name] as $priorityEvents) {
            foreach($priorityEvents as $item) {
                call_user_func_array($item["function"], array_merge($item["arguments"], $arguments));
            }
        }
    }
}