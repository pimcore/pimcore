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

namespace Pimcore\Cache;

class Runtime
{

    /**
     * @var array
     */
    protected static $_cache = [];

    /**
     * @param $data
     * @param $id
     * @return mixed
     */
    public static function save($data, $id)
    {
        static::$_cache[$id] = $data;

        return $data;
    }

    /**
     * @param $id
     * @return mixed
     */
    public static function load($id)
    {
        return static::$_cache[$id];
    }

    /**
     * @param array $keepItems
     */
    public static function clear($keepItems = [])
    {
        $newStore = [];

        foreach($keepItems as $key) {
            if(isset(static::$_cache[$key])) {
                $newStore = static::$_cache[$key];
            }
        }

        static::$_cache = $newStore;
    }
}
