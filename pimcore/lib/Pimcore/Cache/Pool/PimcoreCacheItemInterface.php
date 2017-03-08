<?php

namespace Pimcore\Cache\Pool;

use Cache\TagInterop\TaggableCacheItemInterface;

interface PimcoreCacheItemInterface extends TaggableCacheItemInterface
{
    /**
     * Merge tags into currently set tags
     *
     * @param array $tags
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
