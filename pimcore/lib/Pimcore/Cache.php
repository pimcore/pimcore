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

namespace Pimcore;

use Pimcore\Cache\Symfony\Handler\CoreHandler;

class Cache
{
    /**
     * @var CoreHandler
     */
    public static $handler;

    /**
     * Returns a instance of the cache, if the instance isn't available it creates a new one
     *
     * @return \Zend_Cache_Core|\Zend_Cache_Frontend
     */
    public static function getInstance()
    {
        return null;
    }

    public static function init()
    {
        /** @var CoreHandler $handler */
        $handler = \Pimcore::getDiContainer()->get('pimcore.cache.handler.core');

        static::$handler = $handler;
    }

    /**
     * Returns the content of the requested cache entry
     *
     * @param string $key
     * @param bool $doNotTestCacheValidity
     * @return mixed
     */
    public static function load($key, $doNotTestCacheValidity = false)
    {
        return static::$handler->load($key);
    }

    /**
     * Get the last modified time for the requested cache entry
     *
     * TODO what to do with this method?
     *
     * @param  string $key Cache key
     * @return int|bool Last modified time of cache entry if it is available, false otherwise
     */
    public static function test($key)
    {
        // TODO
        throw new \RuntimeException(__METHOD__ . ' is not implemented anymore');

        if (!self::$enabled) {
            Logger::debug("Key " . $key . " doesn't exist in cache (deactivated)");

            return;
        }

        $lastModified = false;

        if ($cache = self::getInstance()) {
            $key = self::$cachePrefix . $key;
            $data = $cache->test($key);

            if ($data !== false) {
                $lastModified = $data;
            } else {
                Logger::debug("Key " . $key . " doesn't exist in cache");
            }
        }

        return $lastModified;
    }

    /**
     * @param $data
     * @param $key
     * @param array $tags
     * @param null $lifetime
     * @param int $priority
     * @param bool $force
     * @return bool
     */
    public static function save($data, $key, $tags = [], $lifetime = null, $priority = 0, $force = false)
    {
        return static::$handler->save($key, $data, $tags, $lifetime, $force);
    }

    /**
     * Write the save queue to the cache
     *
     * @return bool
     */
    public static function write()
    {
        return static::$handler->writeSaveQueue();
    }

    /**
     * @param $key
     * @return bool
     */
    public static function remove($key)
    {
        return static::$handler->remove($key);
    }

    /**
     * Empty the cache
     *
     * @return bool
     */
    public static function clearAll()
    {
        return static::$handler->clearAll();
    }

    /**
     * Removes entries from the cache matching the given tag
     *
     * @param string $tag
     * @return bool
     */
    public static function clearTag($tag)
    {
        return static::$handler->clearTag($tag);
    }

    /**
     * Removes entries from the cache matching the given tags
     *
     * @param array $tags
     * @return bool
     */
    public static function clearTags($tags = [])
    {
        if (!empty($tags) && !is_array($tags)) {
            $tags = [$tags];
        }

        return static::$handler->clearTags($tags);
    }


    /**
     * Clears all tags stored in self::$_clearTagsOnShutdown, this function is executed in \Pimcore::shutdown()
     * @static
     * @return bool
     */
    public static function clearTagsOnShutdown()
    {
        return static::$handler->clearTagsOnShutdown();
    }

    /**
     * Adds a tag to the shutdown queue, see clearTagsOnShutdown
     * @static
     * @param $tag
     * @return void
     */
    public static function addClearTagOnShutdown($tag)
    {
        static::$handler->addTagClearedOnShutdown($tag);
    }

    /**
     * @static
     * @param $tag
     * @return void
     */
    public static function addIgnoredTagOnClear($tag)
    {
        static::$handler->addTagIgnoredOnClear($tag);
    }

    /**
     * @static
     * @param $tag
     * @return void
     */
    public static function removeIgnoredTagOnClear($tag)
    {
        static::$handler->removeTagIgnoredOnClear($tag);
    }

    /**
     * @static
     * @param $tag
     * @return void
     */
    public static function addClearedTag($tag)
    {
        // instead of messing with the internal cleared tags property, we expose a
        // dedicated property for tags which should be ignored on save
        static::$handler->addTagIgnoredOnSave($tag);
    }

    /**
     * Disables the complete pimcore cache
     * @static
     * @return void
     */
    public static function disable()
    {
        static::$handler->disable();
    }

    /**
     * @static
     * @return void
     */
    public static function enable()
    {
        static::$handler->enable();
    }

    /**
     * @param \Zend_Cache_Core|null $cache
     */
    public static function setZendFrameworkCaches($cache = null)
    {
        // TODO

        return;

        $zendCache = null;
        if ($cache) {
            $zendCache = clone $cache;
            $zendCache->setOption('automatic_serialization', true);
        }

        \Zend_Locale::setCache($zendCache);
        \Zend_Locale_Data::setCache($zendCache);
        \Zend_Db_Table_Abstract::setDefaultMetadataCache($zendCache);
    }

    /**
     * @param boolean $forceImmediateWrite
     */
    public static function setForceImmediateWrite($forceImmediateWrite)
    {
        // TODO

        return;

        self::$forceImmediateWrite = $forceImmediateWrite;
    }

    /**
     * @return boolean
     */
    public static function getForceImmediateWrite()
    {
        // TODO
        return false;

        return self::$forceImmediateWrite;
    }

    public static function maintenance()
    {
        return;

        // TODO


        $cache = self::getInstance();
        $cache->clean(\Zend_Cache::CLEANING_MODE_OLD);
    }
}
