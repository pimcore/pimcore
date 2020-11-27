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

use Pimcore\Cache\Pool\Exception\InvalidArgumentException;
use Psr\Log\LoggerAwareInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;

interface PimcoreCacheItemPoolInterface extends AdapterInterface, LoggerAwareInterface
{
    /**
     * Invalidates cached items using a tag.
     *
     * @param string $tag The tag to invalidate
     *
     * @throws InvalidArgumentException When $tags is not valid
     *
     * @return bool True on success
     */
    public function invalidateTag($tag);

    /**
     * Invalidates cached items using tags.
     *
     * @param string[] $tags An array of tags to invalidate
     *
     * @throws InvalidArgumentException When $tags is not valid
     *
     * @return bool True on success
     */
    public function invalidateTags(array $tags);

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
