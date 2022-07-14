<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Cache;

/**
 * @deprecated
 */
final class Runtime extends RuntimeCache
{
    /**
     * Retrieves the default registry instance.
     *
     * @return RuntimeCache
     */
    public static function getInstance(): RuntimeCache
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '10.5.0',
            sprintf('%s is deprecated. Use %s instead!', __METHOD__, 'RuntimeCache::getInstance')
        );

        return parent::getInstance();
    }

    /**
     * getter method, basically same as offsetGet().
     *
     * This method can be called from an object of type \Pimcore\Cache\Runtime, or it
     * can be called statically.  In the latter case, it uses the default
     * static instance stored in the class.
     *
     * @param string $index - get the value associated with $index
     *
     * @return mixed
     *
     * @deprecated
     *
     * @throws \Exception if no entry is registered for $index.
     */
    public static function get($index)
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '10.5.0',
            sprintf('%s is deprecated. Use %s instead!', __METHOD__, 'RuntimeCache::get')
        );

        return parent::get($index);
    }

    /**
     * setter method, basically same as offsetSet().
     *
     * This method can be called from an object of type \Pimcore\Cache\Runtime, or it
     * can be called statically.  In the latter case, it uses the default
     * static instance stored in the class.
     *
     * @param string $index The location in the ArrayObject in which to store
     *   the value.
     * @param mixed $value The object to store in the ArrayObject.
     *
     * @deprecated
     *
     * @return void
     */
    public static function set($index, $value)
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '10.5.0',
            sprintf('%s is deprecated. Use %s instead!', __METHOD__, 'RuntimeCache::set')
        );
        parent::set($index, $value);
    }

    /**
     * Returns TRUE if the $index is a named value in the registry,
     * or FALSE if $index was not found in the registry.
     *
     * @param  string $index
     *
     * @deprecated
     *
     * @return bool
     */
    public static function isRegistered($index)
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '10.5.0',
            sprintf('%s is deprecated. Use %s instead!', __METHOD__, 'RuntimeCache::isRegistered')
        );

        return RuntimeCache::isRegistered($index);
    }

    /**
     * Constructs a parent ArrayObject with default
     * ARRAY_AS_PROPS to allow access as an object
     *
     * @param array $array data array
     * @param int $flags ArrayObject flags
     */
    public function __construct($array = [], $flags = parent::ARRAY_AS_PROPS)
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '10.5.0',
            sprintf('%s is deprecated. Use %s instead!', __METHOD__, 'new RuntimeCache($array, $flags)')
        );
        parent::__construct($array, $flags);
    }

    /**
     * @param int|string $index
     * @param mixed $value
     *
     * @deprecated
     */
    public function offsetSet($index, $value): void
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '10.5.0',
            sprintf('%s is deprecated. Use %s instead!', __METHOD__, 'RuntimeCache::offsetSet')
        );
        parent::offsetSet($index, $value);
    }

    /**
     * Alias of self::set() to be compatible with Pimcore\Cache
     *
     * @deprecated
     *
     * @param mixed $data
     * @param string $id
     */
    public static function save($data, $id)
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '10.5.0',
            sprintf('%s is deprecated. Use %s instead!', __METHOD__, 'RuntimeCache::save')
        );
        parent::save($data, $id);
    }

    /**
     * Alias of self::get() to be compatible with Pimcore\Cache
     *
     * @deprecated
     *
     * @param string $id
     *
     * @return mixed
     */
    public static function load($id)
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '10.5.0',
            sprintf('%s is deprecated. Use %s instead!', __METHOD__, 'RuntimeCache::load')
        );

        return parent::load($id);
    }

    /**
     * @param array $keepItems
     *
     * @deprecated
     */
    public static function clear($keepItems = [])
    {
        trigger_deprecation(
            'pimcore/pimcore',
            '10.5.0',
            sprintf('%s is deprecated. Use %s instead!', __METHOD__, 'RuntimeCache::clear')
        );
        parent::clear($keepItems);
    }
}
