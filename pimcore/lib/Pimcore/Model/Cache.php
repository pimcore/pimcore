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

class Pimcore_Model_Cache {

    /**
     * Instance of the used cache-implementation
     * @var Zend_Cache_Core|Zend_Cache_Frontend
     */
    public static $instance;

    /**
     * @var bool
     */
    public static $enabled = true;

    /**
     * @var null
     */
    public static $defaultLifetime = null;

    /**
     * Contains the items which should be written to the cache on shutdown. They are ordered respecting the priority
     * @var array
     */
    public static $saveStack = array();

    /**
     * Contains the Logger, this is necessary because otherwise logging doesn't work in shutdown (missing reference)
     * @var Logger
     */
    public static $logger;

    /**
     * Contains the tags which were already cleared
     * @var array
     */
    public static $clearedTagsStack = array();

    /**
     * Items having tags which are in this array are cleared on shutdown Pimcore::shutdown(); This is especially for the output-cache
     * @var array
     */
    protected static $_clearTagsOnShutdown = array();

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
    public static $ignoredTagsOnClear = array();

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
     * Returns a instance of the cache, if the instance isn't available it creates a new one
     *
     * @return Zend_Cache_Core|Zend_Cache_Frontend
     */
    public static function getInstance() {
        
        if (!empty($_REQUEST["nocache"])) {
            self::disable();
        }
        
        if (!self::$instance instanceof Zend_Cache_Core) {
            
            // default file based configuration
            $config = self::getDefaultConfig();
            
            // check for custom cache configuration
            $customCacheFile = PIMCORE_CONFIGURATION_DIRECTORY . "/cache.xml";
            if (is_file($customCacheFile)) {
                try {
                    $conf = new Zend_Config_Xml($customCacheFile);

                    if ($conf->frontend) {
                        $config["frontendType"] = (string) $conf->frontend->type;
                        $config["customFrontendNaming"] = (bool) $conf->frontend->custom;
                        if ($conf->frontend->options && method_exists($conf->backend->options,"toArray")) {
                            $config["frontendConfig"] = $conf->frontend->options->toArray();
                        }
                    }

                    if ($conf->backend) {
                        $config["backendType"] = (string) $conf->backend->type;
                        $config["customBackendNaming"] = (bool) $conf->backend->custom;
                        if ($conf->backend->options && method_exists($conf->backend->options,"toArray")) {
                            $config["backendConfig"] = $conf->backend->options->toArray();
                        }
                    }
                }
                catch (Exception $e) {
                    Logger::crit($e);
                    Logger::crit("Error while reading cache configuration, using the default file backend");
                }
            }

            self::$defaultLifetime = $config["frontendConfig"]["lifetime"];

            // here you can use the cache backend you like
            try {
                self::$instance = self::initializeCache($config);
            }
            catch (Exception $e) {
                Logger::crit("can't initialize cache with the given configuration " . $e->getMessage());
            }
        }

        // return default cache if cache cannot be initialized
        if (!self::$instance instanceof Zend_Cache_Core) {
            self::$instance = self::getDefaultCache();
        }

        // reset default lifetime
        self::$instance->setLifetime(self::$defaultLifetime);

        // always enable the automatic_serialization in this case Pimcore_Tool_Serialize is not used
        self::$instance->setOption("automatic_serialization", true);

        return self::$instance;
    }

    /**
     * @static
     * @param $config
     * @return Zend_Cache_Core|Zend_Cache_Frontend
     */
    public static function initializeCache ($config) {
        $cache = Zend_Cache::factory($config["frontendType"], $config["backendType"], $config["frontendConfig"], $config["backendConfig"], $config["customFrontendNaming"], $config["customBackendNaming"], true);
        return $cache;
    }

    /**
     * @static
     * @return Zend_Cache_Core|Zend_Cache_Frontend
     */
    public static function getDefaultCache () {
        $config = self::getDefaultConfig();
        $cache = self::initializeCache($config);
        return $cache;
    }

    /**
     * @static
     * @return array
     */
    public static function getDefaultConfig () {
        $config["frontendType"] = "Core";
        $config["frontendConfig"] = array(
            "lifetime" => null, // never expire
            "automatic_serialization" => true
        );
        $config["customFrontendNaming"] = false;

        $config["backendType"] = "File";
        $config["backendConfig"] = array(
            "cache_dir" => PIMCORE_CACHE_DIRECTORY,
            "cache_file_perm" => 0755
        );
        $config["customBackendNaming"] = false;

        return $config;
    }
    
    /**
     * Returns the content of the requested cache entry
     * @param string $key
     * @return mixed
     */
    public static function load($key, $doNotTestCacheValidity = false) {
        
        if (!self::$enabled) {
            Logger::debug("Key " . $key . " doesn't exist in cache (deactivated)");
            return;
        }

        if($cache = self::getInstance()) {

            $key = self::$cachePrefix . $key;
            $data = $cache->load($key, $doNotTestCacheValidity);

            if(is_object($data)) {
                $data->____pimcore_cache_item__ = $key;
            }
    
            if ($data !== false) {
                Logger::debug("Successfully got data for key " . $key . " from cache");
            } else {
                Logger::debug("Key " . $key . " doesn't exist in cache");
            }
    
            return $data;
        }
        return;
    }

    /**
     * Puts content into the cache
     * @param mixed $data
     * @param string $key
     * @return void
     */
    public static function save($data, $key, $tags = array(), $lifetime = null, $priority = 0, $force = false) {
        if(self::getForceImmediateWrite() || $force) {
            self::storeToCache($data, $key, $tags, $lifetime, $priority, $force);
        } else {
            self::addToSaveStack(array($data, $key, $tags, $lifetime, $priority, $force));
        }
    }
    
    /**
     * Write's an item to the cache // don't use the logger inside here
     *
     * @param array $config
     * @return void
     */
    public static function storeToCache ($data, $key, $tags = array(), $lifetime = null, $priority = null, $force = false) {
        if (!self::$enabled) {
            return;
        }

        // don't put anything into the cache, when cache is cleared
        if(in_array("__CLEAR_ALL__",self::$clearedTagsStack) && !$force) {
            return;
        }

        // do not cache hardlink-wrappers
        if($data instanceof Document_Hardlink_Wrapper_Interface) {
            return;
        }

        // $priority is currently just for sorting the items in self::addToSaveStack()
        // maybe it will be added to prioritize items for backends with volatile memories

        // get cache instance
        if($cache = self::getInstance()) {

            //if ($lifetime !== null) {
            //    $cache->setLifetime($lifetime);
            //}

            if ($data instanceof Element_Interface) {
                // check for currupt data
                if ($data->getId() < 1) {
                    return;
                }

                if(isset($data->_fulldump)) {
                    unset($data->_fulldump);
                }

                // get dependencies for this element, array_values() because the tags from Element_Interface came with keys eg. array("object_123" => "object_123")
                $tags = array_values($data->getCacheTags($tags));
                $type = get_class($data);

                Logger::debug("prepared " . $type . " " . $data->getFullPath() . " for data cache with tags: " . implode(",", $tags));
            }


            // check for cleared tags, only item which are not cleared within the same session are stored to the cache
            if(is_array($tags)){
                foreach ($tags as $t) {
                    if(in_array($t,self::$clearedTagsStack)) {
                        Logger::debug("Aborted caching for key: " . $key . " because it is in the clear stack");
                        return;
                    }
                }
            } else {
                $tags = array();
            }

            // always add the key as tag
            $tags[] = $key;

            if(is_object($data) && isset($data->____pimcore_cache_item__)) {
                unset($data->____pimcore_cache_item__);
            }

            $key = self::$cachePrefix . $key;
            $success = $cache->save($data, $key, $tags, $lifetime);
            if($success !== true) {
                Logger::error("Failed to add entry $key to the cache, item-size was " . formatBytes(strlen(serialize($data))));
            }

            Logger::debug("Added " . $key . " to cache");
        }
    }

    /**
     * Put the cache item info into the stack
     * array_unshift because the output cache has priority so the 1st item added to the stack will be for sure in the cache
     *
     * @param array $config
     * @return void
     */
    public static function addToSaveStack ($config) {
        $priority = $config[4];
        $i=0;

        //saveStack is sorted - just find the correct position for the new item
        foreach(self::$saveStack as $entry) {
            if($entry[4] <= $priority) {
                //we got the position!
                break;
            } else {
                $i++;
            }
        }
        //add new item at the correct position
        array_splice(self::$saveStack, $i, 0, array($config));

        // remove items which are too much, and cannot be added to the cache anymore
        array_splice(self::$saveStack,self::$maxWriteToCacheItems);
    }
    
    /**
     * Write the stack to the cache
     *
     * @return void
     */
    public static function write () {

        if(self::hasWriteLock()) {
            return;
        }

        $processedKeys = array();
        $count = 0;
        foreach (self::$saveStack as $conf) {

            if(in_array($conf[1],$processedKeys)) {
                continue;
            }

            try {
                forward_static_call_array(array(__CLASS__, "storeToCache"),$conf);
            } catch (Exception $e) {
                Logger::error("Unable to put element " . $conf[1] . " to cache because of the following reason: ");
                Logger::error($e);
            }

            $processedKeys[] = $conf[1]; // index 1 is the key for the cache item

            // only add $maxWriteToCacheItems items att once to the cache for performance issues
            $count++;
            if($count > self::$maxWriteToCacheItems) {
                break;
            }
        }

        // reset
        self::$saveStack = array();
        self::$clearedTagsStack = array();
    }


    /**
     *
     */
    public static function setWriteLock ($force = false) {
        if(!self::$writeLockTimestamp || $force) {
            self::$writeLockTimestamp = time();
            self::save(self::$writeLockTimestamp, "system_cache_write_lock", array(), 30, 0, true);
        }
    }

    /**
     *
     */
    public static function removeWriteLock () {
        if(self::$writeLockTimestamp) {
            $lock = self::load("system_cache_write_lock");

            // only remove the lock if it was created by this process
            if($lock <= self::$writeLockTimestamp) {
                self::remove("system_cache_write_lock");
            }
        }
    }

    /**
     * @return bool
     */
    public static function hasWriteLock() {
        $lock = self::load("system_cache_write_lock");

        // lock is valid for 30 secs
        if($lock && $lock > (time()-30)) {
            return true;
        }
        return false;
    }

    /**
     * @param $key
     */
    public static function remove($key) {
        self::setWriteLock();

        $key = self::$cachePrefix . $key;
        if($cache = self::getInstance()) {
            $cache->remove($key);
        }
    }

    /**
     * Empty the cache
     *
     * @return void
     */
    public static function clearAll() {
        self::setWriteLock();

        if($cache = self::getInstance()) {
            $cache->clean(Zend_Cache::CLEANING_MODE_ALL);
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
    public static function clearTag($tag) {
        self::clearTags(array($tag));
    }
    
    /**
     * Removes entries from the cache matching the given tags
     *
     * @param array $tags
     * @return void
     */
    public static function clearTags($tags = array()) {
        self::setWriteLock();

        Logger::info("clear cache tags: " . implode(",",$tags));

        // ensure that every tag is unique
        $tags = array_unique($tags);

        // check for ignored tags
        foreach (self::$ignoredTagsOnClear as $t) {
            $tagPosition = array_search($t, $tags);
            if($tagPosition !== false) {
                array_splice($tags, $tagPosition, 1);
            }
        }

        // check for the tag output, because items with this tags are only cleared after the process is finished
        // the reason is that eg. long running importers will clean the output-cache on every save/update, that's not necessary,
        // only cleaning the output-cache on shutdown should be enough 
        $outputTagPosition = array_search("output", $tags);
        if($outputTagPosition !== false) {
            array_splice($tags, $outputTagPosition, 1);
             self::addClearTagOnShutdown("output");
        }

        // clean tags, except output
        if($cache = self::getInstance()) {
            $cache->clean(
                Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG,
                $tags
            );
        }
        
        // add tag to clear stack
        foreach ($tags as $tag) {
            self::$clearedTagsStack[] = $tag;
        }
    }

    /**
     * Adds a tag to the shutdown queue, see clearTagsOnShutdown
     * @static
     * @param $tag
     * @return void
     */
    public static function addClearTagOnShutdown($tag) {
        self::setWriteLock();

        self::$_clearTagsOnShutdown[] = $tag;
    }


    /**
     * Clears all tags stored in self::$_clearTagsOnShutdown, this function is executed in Pimcore::shutdown()
     * @static
     * @return void
     */
    public static function clearTagsOnShutdown() {
        if(!empty(self::$_clearTagsOnShutdown)) {
            $tags = array_unique(self::$_clearTagsOnShutdown);
            if($cache = self::getInstance()) {
                $cache->clean(
                    Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG,
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
    public static function addIgnoredTagOnClear($tag) {
        if(!in_array($tag, self::$ignoredTagsOnClear)) {
            self::$ignoredTagsOnClear[] = $tag;
        }
    }

    /**
     * @static
     * @param $tag
     * @return void
     */
    public static function removeIgnoredTagOnClear($tag) {
        $tagPosition = array_search($tag, self::$ignoredTagsOnClear);
        if($tagPosition !== false) {
            array_splice(self::$ignoredTagsOnClear, $tagPosition, 1);
        }
    }

    /**
     * Disables the complete pimcore cache
     * @static
     * @return void
     */
    public static function disable() {
        self::$enabled = false;
    }

    /**
     * @static
     * @return void
     */
    public static function enable() {
        self::$enabled = true;
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
}
