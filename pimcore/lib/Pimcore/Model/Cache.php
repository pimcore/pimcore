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
     *
     * @var Zend_Cache_Core|Zend_Cache_Frontend
     */
    public static $instance;
    public static $writeInstance;
    public static $enabled = true;
    public static $defaultLifetime = null;
    public static $saveStack = array();
    public static $logger;
    public static $clearStack = array();
    public static $maxWriteToCacheItems = 150;
    public static $cachePrefix = "pimcore_";
    
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
                        if ($conf->frontend->options) {
                            $config["frontendConfig"] = $conf->frontend->options->toArray();
                        }
                    }

                    if ($conf->backend) {
                        $config["backendType"] = (string) $conf->backend->type;
                        $config["customBackendNaming"] = (bool) $conf->backend->custom;
                        if ($conf->backend->options) {
                            $config["backendConfig"] = $conf->backend->options->toArray();
                        }
                    }
                }
                catch (Exception $e) {
                    Logger::error($e);
                    Logger::error("Error while reading cache configuration");
                }
            }

            self::$defaultLifetime = $config["frontendConfig"]["lifetime"];

            // here you can use the cache backend you like
            try {
                self::$instance = self::initializeCache($config);
            }
            catch (Exception $e) {
                Logger::warning("can't initialize cache with the given configuration " . $e->getMessage());
            }
        }

        // return default cache if cache cannot be initialized
        if (!self::$instance instanceof Zend_Cache_Core) {
            self::$instance = self::getDefaultCache();
        }

        // reset default lifetime
        self::$instance->setLifetime(self::$defaultLifetime);

        return self::$instance;
    }

    public static function initializeCache ($config) {
        $cache = Zend_Cache::factory($config["frontendType"], $config["backendType"], $config["frontendConfig"], $config["backendConfig"], $config["customFrontendNaming"], $config["customBackendNaming"], true);
        return $cache;
    }

    public static function getDefaultCache () {
        $config = self::getDefaultConfig();
        $cache = self::initializeCache($config);
        return $cache;
    }

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
            "cache_file_umask" => 0755
        );
        $config["customBackendNaming"] = false;

        return $config;
    }
    
    /**
     * Returns the content of the requested cache entry
     *
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

            // unserialize data, use custom serializer
            $data = Pimcore_Tool_Serialize::unserialize($data);
    
            if ($data) {
                Logger::debug("Successfully get data for key " . $key . " from cache");
            }
            else {
                Logger::debug("Key " . $key . " doesn't exist in cache");
            }
    
            return $data;
        }
        return;
    }

    /**
     * Puts content into the cache
     *
     * @param mixed $data
     * @param string $key
     * @return void
     */
    public static function save($data, $key, $tags = array(), $lifetime = null, $priority = 0) {
        self::addToSaveStack(func_get_args());
    }
    
    /**
     * Write's an item to the cache // don't use the logger inside here
     *
     * @param array $config
     * @return void
     */
    public static function storeToCache ($data, $key, $tags = array(), $lifetime = null, $priority = null) {
        if (!self::$enabled) {
            return;
        }

        // don't put anything into the cache, when cache is cleared
        if(in_array("__CLEAR_ALL__",self::$clearStack)) {
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

            if ($lifetime !== null) {
                $cache->setLifetime($lifetime);
            }


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
                    if(in_array($t,self::$clearStack)) {
                        Logger::debug("Aborted caching for key: " . $key . " because it is in the clear stack");
                        return;
                    }
                }
            }

            // serialize data, use custom serializer
            $data = Pimcore_Tool_Serialize::serialize($data);

            $key = self::$cachePrefix . $key;
            $success = $cache->save($data, $key, $tags);
            if($success !== true) {
                Logger::error("Failed to add entry $key to the cache, item-size was " . formatBytes(strlen($data)));
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
        self::$clearStack = array();
    }
    
    /**
     * Empty the cache
     *
     * @return void
     */
    public static function clearAll() {
        if($cache = self::getInstance()) {
            $cache->clean(Zend_Cache::CLEANING_MODE_ALL);
        }
        
        // add tag to clear stack
        self::$clearStack[] = "__CLEAR_ALL__";
    }

    /**
     * Removes entries from the cache matching the given tag
     *
     * @param string $tag
     * @return void
     */
    public static function clearTag($tag) {
        Logger::info("clear cache tag: " . $tag);
        
        if($cache = self::getInstance()) {
            $cache->clean(
            Zend_Cache::CLEANING_MODE_MATCHING_TAG,
                array($tag)
            );
        }
        
        // add tag to clear stack
        self::$clearStack[] = $tag;
    }
    
    /**
     * Removes entries from the cache mathing the given tags
     *
     * @param string $tag
     * @return void
     */
    public static function clearTags($tags = array()) {
        Logger::info("clear cache tags: " . implode(",",$tags));
        
        if($cache = self::getInstance()) {
            $cache->clean(
                Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG,
                $tags
            );
        }
        
        // add tag to clear stack
        foreach ($tags as $tag) {
            self::$clearStack[] = $tag;
        }
    }

    /**
     *
     * disable Cache
     *
     */
    public static function disable() {
        self::$enabled = false;
    }

    /**
     *
     * enable Cache
     *
     */
    public static function enable() {
        self::$enabled = true;
    }
}
