<?php 
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Cache;

class Runtime
{

    /**
     * @var array
     */
    protected static $_cache = array();

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
     *
     */
    public static function clear()
    {
        static::$_cache = array();
    }
}
