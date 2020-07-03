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

use Cache\TagInterop\TaggableCacheItemInterface;

interface PimcoreCacheItemInterface extends TaggableCacheItemInterface
{
    /**
     * Merge tags into currently set tags
     *
     * @param array $tags
     *
     * @return TaggableCacheItemInterface
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
