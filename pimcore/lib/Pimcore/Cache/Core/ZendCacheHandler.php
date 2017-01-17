<?php

namespace Pimcore\Cache\Core;

class ZendCacheHandler
{
    /**
     * @var \Zend_Cache_Core
     */
    protected $cache;

    /**
     * @param \Zend_Cache_Core $cache
     */
    public function __construct(\Zend_Cache_Core $cache)
    {
        if (!$cache->getOption('automatic_serialization')) {
            throw new \RuntimeException('The Zend Cache must enable automatic serialization for the DB metadata cache');
        }

        $this->cache = $cache;
    }

    /**
     * @return \Zend_Cache_Core
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * Enable caching on the ZF cache
     */
    public function enable()
    {
        $this->cache->setOption('caching', true);
    }

    /**
     * Disable caching on the ZF cache
     */
    public function disable()
    {
        $this->cache->setOption('caching', false);
    }

    /**
     * Setup ZF caches
     *
     * @return $this
     */
    public function setZendFrameworkCaches()
    {
        \Zend_Locale::setCache($this->cache);
        \Zend_Locale_Data::setCache($this->cache);
        \Zend_Db_Table_Abstract::setDefaultMetadataCache($this->cache);

        return $this;
    }
}
