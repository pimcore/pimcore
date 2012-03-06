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

class Pimcore_Tool_ShutdownManager {

    /**
     * @var array
     */
    private static $stack = array();

    /**
     * @param mixed $function
     * @param string $key
     */
    public static function register ($function, $arguments = array(), $index = null) {

        if(!$index) {
            for($i=0; $i<999; $i++) {
                if(!array_key_exists($i, self::$stack)) {
                    $index = $i;
                    break;
                }
            }
        }

        self::$stack[$index] = array(
            "function" => $function,
            "arguments" => $arguments
        );
    }

    /**
     * @param mixed $key
     */
    public static function unregister ($key) {
        if(array_key_exists($key, self::$stack)) {
            unset(self::$stack[$key]);
        }
    }

    /**
     *
     */
    public static function run () {

        ksort(self::$stack);
        foreach (self::$stack as $item) {

            $function = $item["function"];
            $arguments = $item["arguments"];

            if(is_string($function) || is_array($function) || is_callable($function)) {
                call_user_func_array($function, $arguments);
            }
        }
    }
}