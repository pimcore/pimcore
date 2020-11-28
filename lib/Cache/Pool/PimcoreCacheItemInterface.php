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
use Psr\Cache\CacheItemInterface;

interface PimcoreCacheItemInterface extends CacheItemInterface
{
    /**
     * Get all existing tags. These are the tags the item has when the item is
     * returned from the pool.
     *
     * @return array
     */
    public function getPreviousTags();

    /**
     * Overwrite all tags with a new set of tags.
     *
     * @param string[] $tags An array of tags
     *
     * @throws InvalidArgumentException When a tag is not valid.
     *
     * @return PimcoreCacheItemInterface
     */
    public function setTags(array $tags);

    /**
     * Merge tags into currently set tags
     *
     * @param array $tags
     *
     * @return PimcoreCacheItemInterface
     */
    public function mergeTags(array $tags);

    /**
     * Get currently set tags
     *
     * @return array
     */
    public function getTags();

    /**
     * @return int
     */
    public function getExpiry();

    /**
     * @return int|null
     */
    public function getDefaultLifetime();
}
