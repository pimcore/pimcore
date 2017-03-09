<?php

namespace Pimcore\Cache\Core;

use Pimcore\Event\Cache\Core\ResultEvent;
use Pimcore\Event\CoreCacheEvents;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ZendCacheHandler implements EventSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

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
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            CoreCacheEvents::INIT    => 'init',
            CoreCacheEvents::ENABLE  => 'enable',
            CoreCacheEvents::DISABLE => 'disable',
            CoreCacheEvents::PURGE   => 'onPurge'
        ];
    }

    /**
     * @return \Zend_Cache_Core
     */
    public function getCache()
    {
        return $this->cache;
    }

    public function init()
    {
        $this->logger->debug('Initializing Zend legacy cache');

        $this->setZendFrameworkCaches();
    }

    /**
     * Enable caching on the ZF cache
     */
    public function enable()
    {
        $this->setEnabled(true);
    }

    /**
     * Disable caching on the ZF cache
     */
    public function disable()
    {
        $this->setEnabled(false);
    }

    /**
     * @param bool $enabled
     */
    protected function setEnabled($enabled)
    {
        $enabled = (bool)$enabled;

        $this->logger->debug('Setting Zend legacy cache to {state}', [
            'state' => $enabled ? 'enabled' : 'disabled'
        ]);

        $this->cache->setOption('caching', $enabled);

        if ($enabled) {
            $this->setZendFrameworkCaches();
        }
    }

    public function onPurge(ResultEvent $event)
    {
        $event->setResult($event->getResult() && $this->purge());
    }

    public function purge()
    {
        $this->logger->debug('Purging Zend legacy cache');

        // TODO if the ZF backend and the handler itemPool are the same, purge will be called twice. However, not
        // calling clean would result in ZF cache never being cleaned up if the backend differs from the core item pool.
        return $this->cache->clean(\Zend_Cache::CLEANING_MODE_OLD);
    }

    /**
     * Setup ZF caches
     *
     * @return $this
     */
    protected function setZendFrameworkCaches()
    {
        \Zend_Locale::setCache($this->cache);
        \Zend_Locale_Data::setCache($this->cache);
        \Zend_Db_Table_Abstract::setDefaultMetadataCache($this->cache);
        \Zend_Paginator::setCache($this->cache);
    }
}
