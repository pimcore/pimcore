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

namespace Pimcore\Cache;


class Runtime {
    protected static $_cache = array();

    public static function save($data, $id) {
        static::$_cache[$id] = $data;
        return $data;
    }

    public static function load($id) {
        return static::$_cache[$id];
    }

    public static function clear(){
        static::$_cache = array();
    }
}