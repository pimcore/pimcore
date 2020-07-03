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

namespace Pimcore\Cache\Pool;

use Cache\TagInterop\TaggableCacheItemPoolInterface;
use Psr\Log\LoggerAwareInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;

interface PimcoreCacheItemPoolInterface extends AdapterInterface, TaggableCacheItemPoolInterface, LoggerAwareInterface
{
    /**
     * {@inheritdoc}
     *
     * @return PimcoreCacheItemInterface
     */
    public function getItem($key);

    /**
     * {@inheritdoc}
     *
     * @return array|\Traversable|PimcoreCacheItemInterface[]
     */
    public function getItems(array $keys = []);

    /**
     * Create a cache item
     *
     * @param string $key
     * @param mixed $value
     * @param array $tags
     * @param bool $isHit
     *
     * @return PimcoreCacheItemInterface
     */
    public function createCacheItem($key, $value = null, array $tags = [], $isHit = false);
}
