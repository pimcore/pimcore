<?php

namespace Pimcore\Cache\Backend;

use Pimcore\Cache\Backend\Exception\NotImplementedException;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Cache\CacheItem;
use Zend_Cache;

class SymfonyCache extends \Zend_Cache_Backend implements \Zend_Cache_Backend_ExtendedInterface
{
    /**
     * @var AdapterInterface|TagAwareAdapterInterface
     */
    protected $adapter;

    /**
     * @param AdapterInterface $adapter
     * @param array $options Associative array of options
     */
    public function __construct(AdapterInterface $adapter, array $options = [])
    {
        $this->adapter = $adapter;

        parent::__construct($options);
    }

    /**
     * @inheritDoc
     */
    public function getCapabilities()
    {
        // TODO what to set for automatic_cleaning, expired_read, infinite_lifetime?
        return [
            'automatic_cleaning' => false,
            'tags'               => $this->adapter instanceof TagAwareAdapterInterface,
            'expired_read'       => false,
            'priority'           => false,
            'infinite_lifetime'  => false,
            'get_list'           => false,
        ];
    }

    /**
     * Test if a cache is available for the given id and (if yes) return it (false else)
     *
     * Note : return value is always "string" (unserialization is done by the core not by the backend)
     *
     * @param  string $id Cache id
     * @param  boolean $doNotTestCacheValidity If set to true, the cache validity won't be tested
     * @return string|false cached datas
     */
    public function load($id, $doNotTestCacheValidity = false)
    {
        $item = $this->adapter->getItem($id);

        if ($item->isHit()) {
            return $item->get();
        }

        return false;
    }

    /**
     * Save some string datas into a cache record
     *
     * Note : $data is always "string" (serialization is done by the
     * core not by the backend)
     *
     * @param  string $data Datas to cache
     * @param  string $id Cache id
     * @param  array $tags Array of strings, the cache record will be tagged by each string entry
     * @param  int $specificLifetime If != false, set a specific lifetime for this cache record (null => infinite lifetime)
     * @return boolean true if no problem
     */
    public function save($data, $id, $tags = array(), $specificLifetime = false)
    {
        /** @var CacheItem|CacheItemPoolInterface $item */
        $item = $this->adapter->getItem($id);

        $item->set($data);
        $item->expiresAfter($this->getLifetime($specificLifetime));
        $item->tag($tags);

        return $this->adapter->save($item);
    }

    /**
     * Remove a cache record
     *
     * @param  string $id Cache id
     * @return boolean True if no problem
     */
    public function remove($id)
    {
        return $this->adapter->deleteItem($id);
    }

    /**
     * Clean some cache records
     *
     * Available modes are :
     * Zend_Cache::CLEANING_MODE_ALL (default)    => remove all cache entries ($tags is not used)
     * Zend_Cache::CLEANING_MODE_OLD              => remove too old cache entries ($tags is not used)
     * Zend_Cache::CLEANING_MODE_MATCHING_TAG     => remove cache entries matching all given tags
     *                                               ($tags can be an array of strings or a single string)
     * Zend_Cache::CLEANING_MODE_NOT_MATCHING_TAG => remove cache entries not {matching one of the given tags}
     *                                               ($tags can be an array of strings or a single string)
     * Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG => remove cache entries matching any given tags
     *                                               ($tags can be an array of strings or a single string)
     *
     * @param  string $mode Clean mode
     * @param  array $tags Array of tags
     * @return boolean true if no problem
     */
    public function clean($mode = Zend_Cache::CLEANING_MODE_ALL, $tags = array())
    {
        if ($mode === Zend_Cache::CLEANING_MODE_ALL) {
            return $this->adapter->clear();
        } else if ($mode === Zend_Cache::CLEANING_MODE_MATCHING_TAG) {
            if ($this->adapter instanceof TagAwareAdapterInterface) {
                return $this->adapter->invalidateTags($tags);
            } else {
                throw new NotImplementedException(sprintf(
                    'Backend does not support tagging and therefore can\'t clear with "%s" mode',
                    $mode
                ));
            }
        }

        throw new NotImplementedException(sprintf(
            'Backend does not support clearing with "%s" mode',
            $mode
        ));
    }

    /**
     * @inheritDoc
     */
    public function test($id)
    {
        throw new NotImplementedException(sprintf('%s is not implemented', __METHOD__));
    }

    /**
     * @inheritDoc
     */
    public function getIds()
    {
        throw new NotImplementedException(sprintf('%s is not implemented', __METHOD__));
    }

    /**
     * @inheritDoc
     */
    public function getTags()
    {
        throw new NotImplementedException(sprintf('%s is not implemented', __METHOD__));
    }

    /**
     * @inheritDoc
     */
    public function getIdsMatchingTags($tags = array())
    {
        throw new NotImplementedException(sprintf('%s is not implemented', __METHOD__));
    }

    /**
     * @inheritDoc
     */
    public function getIdsNotMatchingTags($tags = array())
    {
        throw new NotImplementedException(sprintf('%s is not implemented', __METHOD__));
    }

    /**
     * @inheritDoc
     */
    public function getIdsMatchingAnyTags($tags = array())
    {
        throw new NotImplementedException(sprintf('%s is not implemented', __METHOD__));
    }

    /**
     * @inheritDoc
     */
    public function getFillingPercentage()
    {
        throw new NotImplementedException(sprintf('%s is not implemented', __METHOD__));
    }

    /**
     * @inheritDoc
     */
    public function getMetadatas($id)
    {
        throw new NotImplementedException(sprintf('%s is not implemented', __METHOD__));
    }

    /**
     * @inheritDoc
     */
    public function touch($id, $extraLifetime)
    {
        throw new NotImplementedException(sprintf('%s is not implemented', __METHOD__));
    }
}
