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

    /**
     * @static
     * @param $name
     * @param $function
     * @param array $arguments
     * @param int $index
     */
    public static function register ($name, $function, $arguments = array(), $index = null) {

        if(!array_key_exists($name, self::$events)) {
            self::$events[$name] = array();
        }

        if(!$index) {
            for($i=0; $i<999; $i++) {
                if(!array_key_exists($i, self::$events[$name])) {
                    $index = $i;
                    break;
                }
            }
        }

        self::$events[$name][$index] = array(
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
    public static function unregister ($name, $function) {

        if(array_key_exists($name, self::$events) && is_array(self::$events[$name])) {
            foreach (self::$events[$name] as $index => $event) {
                if($event["function"] == $function) {
                    unset(self::$events[$name][$index]);
                }
            }
        }
    }

    /**
     * @static
     * @param $name
     * @param array $arguments
     * @return mixed
     */
    public static function fire ($name, $arguments = array()) {

        if(!array_key_exists($name, self::$events) || !is_array(self::$events[$name])) {
            return;
        }

        foreach (self::$events[$name] as $item) {

            $function = $item["function"];
            $arguments = array_merge($item["arguments"], $arguments);

            if(is_string($function) || is_array($function) || is_callable($function)) {
                call_user_func_array($function, $arguments);
            }
        }
    }
}