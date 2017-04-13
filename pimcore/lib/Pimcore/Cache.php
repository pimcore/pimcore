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

use Pimcore\Model\Element;
use Pimcore\Model\Document;

class Cache
{

    /**
     * Instance of the used cache-implementation
     * @var \Zend_Cache_Core|\Zend_Cache_Frontend
     */
    public static $instance;

    /**
     * @var bool
     */
    protected static $enabled = true;

    /**
     * @var null
     */
    public static $defaultLifetime = 2419200; // 28 days

    /**
     * Contains the items which should be written to the cache on shutdown. They are ordered respecting the priority
     * @var array
     */
    public static $saveStack = [];

    /**
     * Contains the Logger, this is necessary because otherwise logging doesn't work in shutdown (missing reference)
     * @var Logger
     */
    public static $logger;

    /**
     * Contains the tags which were already cleared
     * @var array
     */
    public static $clearedTagsStack = [];

    /**
     * Items having tags which are in this array are cleared on shutdown \Pimcore::shutdown(); This is especially for the output-cache
     * @var array
     */
    protected static $_clearTagsOnShutdown = [];

    /**
     * How many items should stored to the cache within one process
     * @var int
     */
    public static $maxWriteToCacheItems = 50;

    /**
     * prefix which will be added to every item-key
     * @var string
     */
    public static $cachePrefix = "pimcore_";

    /**
     * items having one of the tags in this store are not cleared when calling self::clearTags() or self::clearTag()
     * @var array
     */
    public static $ignoredTagsOnClear = [];

    /**
     * if set to truq items are directly written into the cache, and do not get into the queue
     * @var bool
     */
    protected static $forceImmediateWrite = false;

    /**
     * contains the timestamp of the writeLockTime from the current process
     * this is to recheck when removing the write lock (if the value is different -> higher) do not remove the lock
     * because then another process has acquired a lock
     * @var int
     */
    protected static $writeLockTimestamp;

    /**
     * @var \Zend_Cache_Core
     */
    protected static $blackHoleCache = null;

    /**
     * Returns a instance of the cache, if the instance isn't available it creates a new one
     *
     * @return \Zend_Cache_Core|\Zend_Cache_Frontend
     */
    public static function getInstance()
    {
        if (!self::$instance instanceof \Zend_Cache_Core) {
            self::init();
        }

        if (!empty($_REQUEST["nocache"]) && PIMCORE_DEBUG) {
            self::disable();
        }

        return self::$instance;
    }

    /**
     *
     */
    public static function init()
    {
        if (!self::$instance instanceof \Zend_Cache_Core) {
            // check for custom cache configuration
            $customConfigFile = \Pimcore\Config::locateConfigFile("cache.php");
            if (is_file($customConfigFile)) {
                $config = self::getDefaultConfig();

                $conf = include($customConfigFile);

                if (is_array($conf)) {
                    if (isset($conf["frontend"])) {
                        $config["frontendType"] = $conf["frontend"]["type"];
                        $config["customFrontendNaming"] = $conf["frontend"]["custom"];
                        if (isset($conf["frontend"]["options"])) {
                            $config["frontendConfig"] = $conf["frontend"]["options"];
                        }
                    }

                    if (isset($conf["backend"])) {
                        $config["backendType"] = $conf["backend"]["type"];
                        $config["customBackendNaming"] = $conf["backend"]["custom"];
                        if (isset($conf["backend"]["options"])) {
                            $config["backendConfig"] = $conf["backend"]["options"];
                        }
                    }

                    if (isset($config["frontendConfig"]["lifetime"])) {
                        self::$defaultLifetime = $config["frontendConfig"]["lifetime"];
                    }

                    $config = self::normalizeConfig($config);

                    // here you can use the cache backend you like
                    try {
                        self::$instance = self::initializeCache($config);
                    } catch (\Exception $e) {
                        Logger::crit("can't initialize cache with the given configuration " . $e->getMessage());
                    }
                } else {
                    Logger::crit("Error while reading cache configuration, using the default database backend");
                }
            }
        }

        // return default cache if cache cannot be initialized
        if (!self::$instance instanceof \Zend_Cache_Core) {
            self::$instance = self::getDefaultCache();
        }

        self::$instance->setLifetime(self::$defaultLifetime);
        self::$instance->setOption("automatic_serialization", false);
        self::$instance->setOption("automatic_cleaning_factor", 0);

        // init the write lock once (from other processes etc.)
        if (self::$writeLockTimestamp === null) {
            self::$writeLockTimestamp = 0; // set the write lock to 0, otherwise infinite loop (self::hasWriteLock() calls self::getInstance())
            self::hasWriteLock();
        }

        self::setZendFrameworkCaches(self::$instance);
    }

    /**
     * @param $config
     * @return mixed
     */
    protected static function normalizeConfig($config)
    {
        foreach ($config as $key => &$value) {
            if ($value === "true") {
                $value = true;
            }
            if ($value === "false") {
                $value = false;
            }

            if (is_array($value)) {
                $value = self::normalizeConfig($value);
            }
        }

        return $config;
    }

    /**
     * @param $config
     * @return \Zend_Cache_Core|\Zend_Cache_Frontend
     */
    public static function initializeCache($config)
    {
        $cache = \Zend_Cache::factory($config["frontendType"], $config["backendType"], $config["frontendConfig"], $config["backendConfig"], $config["customFrontendNaming"], $config["customBackendNaming"], true);

        return $cache;
    }

    /**
     * @param string|null $adapter
     * @return array
     */
    public static function getDefaultConfig($adapter = null)
    {
        $config =  [
            "frontendType" => "Core",
            "frontendConfig" => [
                "lifetime" => self::$defaultLifetime,
                "automatic_serialization" => false,
                "automatic_cleaning_factor" => 0
            ],
            "customFrontendNaming" => true,
            "backendType" => "\\Pimcore\\Cache\\Backend\\MysqlTable",
            "backendConfig" => [],
            "customBackendNaming" => true
        ];

        if ($adapter) {
            $config["backendType"] = $adapter;
        }

        return $config;
    }

    /**
     * @return \Zend_Cache_Core|\Zend_Cache_Frontend
     */
    public static function getDefaultCache()
    {
        if (\Pimcore\Config::getSystemConfig()) {
            // default mysql cache adapter
            $config = self::getDefaultConfig();
            $cache = self::initializeCache($config);
        } else {
            $cache = self::getBlackHoleCache();
        }

        return $cache;
    }

    /**
     * @return \Zend_Cache_Core|\Zend_Cache_Frontend
     */
    public static function getBlackHoleCache()
    {
        if (!self::$blackHoleCache) {
            $config = self::getDefaultConfig();
            $config["backendType"] = "\\Zend_Cache_Backend_BlackHole";
            self::$blackHoleCache = self::initializeCache($config);
        }

        return self::$blackHoleCache;
    }

    /**
     * Returns the content of the requested cache entry
     * @param string $key
     * @param boolean $doNotTestCacheValidity
     * @return mixed
     */
    public static function load($key, $doNotTestCacheValidity = false)
    {
        if (!self::$enabled) {
            Logger::debug("Key " . $key . " doesn't exist in cache (deactivated)");

            return;
        }

        if ($cache = self::getInstance()) {
            $key = self::$cachePrefix . $key;
            $data = $cache->load($key, $doNotTestCacheValidity);
            $data = unserialize($data);

            if (is_object($data)) {
                $data->____pimcore_cache_item__ = $key;
            }

            if ($data !== false) {
                Logger::debug("Successfully got data for key " . $key . " from cache");
            } else {
                Logger::debug("Key " . $key . " doesn't exist in cache");
            }

            return $data;
        }

        return false;
    }

    /**
     * Get the last modified time for the requested cache entry
     *
     * @param  string $key Cache key
     * @return int|bool Last modified time of cache entry if it is available, false otherwise
     */
    public static function test($key)
    {
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
        if (!$force && php_sapi_name() == "cli") {
            return false;
        }

        if (self::getForceImmediateWrite() || $force) {
            if (self::hasWriteLock()) {
                return false;
            }

            $serializedData = static::prepareCacheData($data);
            if ($serializedData) {
                return self::storeToCache($serializedData, $data, $key, $tags, $lifetime, $force);
            }
        } else {
            self::$saveStack[$key] = [null, $data, $key, $tags, $lifetime, $force, $priority];

            // order by priority
            uasort(self::$saveStack, function ($a, $b) {
                // 6 => priority
                // 0 => serialized data
                if ($a[6] == $b[6]) {
                    // records with serialized data have priority, to save cpu cycles
                    return ($a[0]) ? -1 : 1;
                }

                return ($a[6] < $b[6]) ? 1 : -1;
            });

            // remove overrun
            array_splice(self::$saveStack, self::$maxWriteToCacheItems);

            if (isset(self::$saveStack[$key])) {
                $serializedData = static::prepareCacheData($data);
                if (!$serializedData) {
                    unset(self::$saveStack[$key]);

                    return false;
                } else {
                    self::$saveStack[$key][0] = $serializedData;

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param $data
     * @return array|bool
     */
    protected static function prepareCacheData($data)
    {
        // do not cache hardlink-wrappers
        if ($data instanceof Document\Hardlink\Wrapper\WrapperInterface) {
            return false;
        }

        if ($data instanceof Element\ElementInterface) {
            // check for currupt data
            if (!$data->getId()) {
                return false;
            }

            if (isset($data->_fulldump)) {
                unset($data->_fulldump);
            }
        }

        if (is_object($data) && isset($data->____pimcore_cache_item__)) {
            unset($data->____pimcore_cache_item__);
        }

        return serialize($data);
    }

    /**
     * @param mixed $data
     * @param array $tags
     * @return array|bool
     */
    protected static function prepareCacheTags($data, $tags)
    {
        if ($data instanceof Element\ElementInterface) {
            $tags = $data->getCacheTags($tags);
        }

        if (!is_array($tags)) {
            if ($tags) {
                $tags = [$tags];
            } else {
                $tags = [];
            }
        }

        $tags = array_values($tags);
        $tags = array_unique($tags);

        return $tags;
    }

    /**
     * Write's an item to the cache // don't use the logger inside here
     * @param string $dataSerialized
     * @param mixed $data
     * @param string $key
     * @param array $tags
     * @param null|int $lifetime
     * @param bool $force
     * @return bool
     */
    protected static function storeToCache($dataSerialized, $data, $key, $tags = [], $lifetime = null, $force = false)
    {
        if (!self::$enabled) {
            return false;
        }

        // don't put anything into the cache, when cache is cleared
        if (in_array("__CLEAR_ALL__", self::$clearedTagsStack) && !$force) {
            return false;
        }

        // get cache instance
        if ($cache = self::getInstance()) {
            // check for cleared tags, only item which are not cleared within the same session are stored to the cache
            if (is_array($tags)) {
                foreach ($tags as $t) {
                    if (in_array($t, self::$clearedTagsStack)) {
                        Logger::debug("Aborted caching for key: " . $key . " because it is in the clear stack");

                        return false;
                    }
                }
            } else {
                $tags = [];
            }

            // always add the key as tag
            $tags[] = $key;

            $tags = self::prepareCacheTags($data, $tags);

            $key = self::$cachePrefix . $key;

            if ($lifetime === null) {
                $lifetime = false; // set to false otherwise the lifetime stays at null (\Zend_Cache_Backend::getLifetime())
            }

            if ($data instanceof Element\ElementInterface) {
                if (!$data->__isBasedOnLatestData()) {
                    //@TODO: this check needs to be done recursive, especially for Objects (like cache tags)
                    // all other entities shouldn't have references at all in the cache so it shouldn't matter
                    return false;
                }
            }

            $success = $cache->save($dataSerialized, $key, $tags, $lifetime);
            if ($success !== true) {
                Logger::error("Failed to add entry $key to the cache, item-size was " . formatBytes(strlen(serialize($dataSerialized))));
            }

            Logger::debug("Added " . $key . " to cache");

            return $success;
        }

        return false;
    }

    /**
     *
     */
    public function clearSaveStack()
    {
        self::$saveStack = [];
    }

    /**
     * Write the stack to the cache
     *
     * @return void
     */
    public static function write()
    {
        if (self::hasWriteLock()) {
            return;
        }

        foreach (self::$saveStack as $conf) {
            try {
                forward_static_call_array([__CLASS__, "storeToCache"], $conf);
            } catch (\Exception $e) {
                Logger::error("Unable to put element " . $conf[2] . " to cache because of the following reason: ");
                Logger::error($e);
            }
        }

        // reset
        self::$saveStack = [];
    }


    /**
     *
     */
    public static function setWriteLock($force = false)
    {
        if (!self::$writeLockTimestamp || $force) {
            self::$writeLockTimestamp = time();
            if ($cache = self::getInstance()) {
                $cache->save((string) self::$writeLockTimestamp, "system_cache_write_lock", [], 30);
            }
        }
    }

    /**
     *
     */
    public static function removeWriteLock()
    {
        if (self::$writeLockTimestamp) {
            if ($cache = self::getInstance()) {
                $lock = $cache->load("system_cache_write_lock");

                // only remove the lock if it was created by this process
                if ($lock <= self::$writeLockTimestamp) {
                    $cache->remove("system_cache_write_lock");
                    self::$writeLockTimestamp = null;
                }
            }
        }
    }

    /**
     * @return bool
     */
    public static function hasWriteLock()
    {
        if (self::$writeLockTimestamp) {
            return true;
        }

        if ($cache = self::getInstance()) {
            $lock = $cache->load("system_cache_write_lock");

            // lock is valid for 30 secs
            if ($lock && $lock > (time()-30)) {
                self::$writeLockTimestamp = $lock;

                return true;
            } else {
                self::$writeLockTimestamp = 0;
            }
        }

        return false;
    }

    /**
     * @param $key
     */
    public static function remove($key)
    {

        // do not disable clearing, it's better purging items here than having inconsistent data because of wrong usage
        /*if (!self::$enabled) {
            Logger::debug("Cache is not cleared because it is disabled");
            return;
        }*/

        self::setWriteLock();

        $key = self::$cachePrefix . $key;
        if ($cache = self::getInstance()) {
            $cache->remove($key);
        }
    }

    /**
     * Empty the cache
     *
     * @return void
     */
    public static function clearAll()
    {

        // do not disable clearing, it's better purging items here than having inconsistent data because of wrong usage
        /*if (!self::$enabled) {
            Logger::debug("Cache is not cleared because it is disabled");
            return;
        }*/

        self::setWriteLock();

        if ($cache = self::getInstance()) {
            $cache->clean(\Zend_Cache::CLEANING_MODE_ALL);
        }

        // add tag to clear stack
        self::$clearedTagsStack[] = "__CLEAR_ALL__";

        // immediately acquire the write lock again (force), because the lock is in the cache too
        self::setWriteLock(true);
    }

    /**
     * Removes entries from the cache matching the given tag
     *
     * @param string $tag
     * @return void
     */
    public static function clearTag($tag)
    {
        self::clearTags([$tag]);
    }

    /**
     * Removes entries from the cache matching the given tags
     *
     * @param array $tags
     * @return void
     */
    public static function clearTags($tags = [])
    {

        // do not disable clearing, it's better purging items here than having inconsistent data because of wrong usage
        /*if (!self::$enabled) {
            Logger::debug("Cache is not cleared because it is disabled");
            return;
        }*/

        self::setWriteLock();

        Logger::info("clear cache tags: " . implode(",", $tags));

        // ensure that every tag is unique
        $tags = array_unique($tags);

        // check for ignored tags
        foreach (self::$ignoredTagsOnClear as $t) {
            $tagPosition = array_search($t, $tags);
            if ($tagPosition !== false) {
                array_splice($tags, $tagPosition, 1);
            }
        }

        // check for the tag output, because items with this tags are only cleared after the process is finished
        // the reason is that eg. long running importers will clean the output-cache on every save/update, that's not necessary,
        // only cleaning the output-cache on shutdown should be enough
        $outputTagPosition = array_search("output", $tags);
        if ($outputTagPosition !== false) {
            array_splice($tags, $outputTagPosition, 1);
            self::addClearTagOnShutdown("output");
        }

        // add tag to clear stack
        foreach ($tags as $tag) {
            self::$clearedTagsStack[] = $tag;
        }

        // clean tags, except output
        if ($cache = self::getInstance()) {
            $cache->clean(
                \Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG,
                $tags
            );
        }
    }

    /**
     * Adds a tag to the shutdown queue, see clearTagsOnShutdown
     * @static
     * @param $tag
     * @return void
     */
    public static function addClearTagOnShutdown($tag)
    {
        self::setWriteLock();

        self::$_clearTagsOnShutdown[] = $tag;
    }


    /**
     * Clears all tags stored in self::$_clearTagsOnShutdown, this function is executed in \Pimcore::shutdown()
     * @static
     * @return void
     */
    public static function clearTagsOnShutdown()
    {

        // do not disable clearing, it's better purging items here than having inconsistent data because of wrong usage
        /*if (!self::$enabled) {
            Logger::debug("Cache is not cleared because it is disabled");
            return;
        }*/

        if (!empty(self::$_clearTagsOnShutdown)) {
            $tags = array_unique(self::$_clearTagsOnShutdown);
            if ($cache = self::getInstance()) {
                $cache->clean(
                    \Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG,
                    $tags
                );
            }
        }
    }

    /**
     * @static
     * @param $tag
     * @return void
     */
    public static function addIgnoredTagOnClear($tag)
    {
        if (!in_array($tag, self::$ignoredTagsOnClear)) {
            self::$ignoredTagsOnClear[] = $tag;
        }
    }

    /**
     * @static
     * @param $tag
     * @return void
     */
    public static function removeIgnoredTagOnClear($tag)
    {
        $tagPosition = array_search($tag, self::$ignoredTagsOnClear);
        if ($tagPosition !== false) {
            array_splice(self::$ignoredTagsOnClear, $tagPosition, 1);
        }
    }

    /**
     * @param string $tag
     */
    public static function addClearedTag($tag)
    {
        self::$clearedTagsStack[] = $tag;
    }

    /**
     * Disables the complete pimcore cache
     * @static
     * @return void
     */
    public static function disable()
    {
        if (self::$enabled) {
            self::setZendFrameworkCaches(self::getBlackHoleCache());
        }
        self::$enabled = false;
    }

    /**
     * @static
     * @return void
     */
    public static function enable()
    {
        self::$enabled = true;
        self::setZendFrameworkCaches(self::getInstance());
    }


    /**
     * @param \Zend_Cache_Core|null $cache
     */
    public static function setZendFrameworkCaches($cache = null)
    {
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
        self::$forceImmediateWrite = $forceImmediateWrite;
    }

    /**
     * @return boolean
     */
    public static function getForceImmediateWrite()
    {
        return self::$forceImmediateWrite;
    }

    public static function maintenance()
    {
        $cache = self::getInstance();
        $cache->clean(\Zend_Cache::CLEANING_MODE_OLD);
    }
}
