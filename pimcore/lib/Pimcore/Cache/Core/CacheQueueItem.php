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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Cache\Core;

use Pimcore\Cache\Pool\PimcoreCacheItemInterface;

class CacheQueueItem
{
    /**
     * @var string
     */
    protected $key;

    /**
     * @var mixed
     */
    protected $data;

    /**
     * @var PimcoreCacheItemInterface
     */
    protected $cacheItem;

    /**
     * @var array
     */
    protected $tags = [];

    /**
     * @param int|\DateInterval|null $lifetime
     */
    protected $lifetime = null;

    /**
     * @var int
     */
    protected $priority = 0;

    /**
     * @var bool
     */
    protected $force = false;

    /**
     * @param $key
     * @param mixed $data
     * @param array $tags
     * @param int|\DateInterval|null $lifetime
     * @param int|null $priority
     * @param bool $force
     */
    public function __construct($key, $data, array $tags = [], $lifetime = null, $priority = 0, $force = false)
    {
        $this->key      = $key;
        $this->data     = $data;
        $this->tags     = $tags;
        $this->lifetime = $lifetime;
        $this->priority = (int)$priority;
        $this->force    = (bool)$force;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return array
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @return mixed
     */
    public function getLifetime()
    {
        return $this->lifetime;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @return bool
     */
    public function isForce()
    {
        return $this->force;
    }

    /**
     * @param PimcoreCacheItemInterface $cacheItem
     *
     * @return $this
     */
    public function setCacheItem(PimcoreCacheItemInterface $cacheItem)
    {
        $this->cacheItem = $cacheItem;

        return $this;
    }

    /**
     * @return PimcoreCacheItemInterface
     */
    public function getCacheItem()
    {
        return $this->cacheItem;
    }
}
