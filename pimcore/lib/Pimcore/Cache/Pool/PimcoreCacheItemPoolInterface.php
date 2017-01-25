<?php

namespace Pimcore\Cache\Pool;

use Cache\TagInterop\TaggableCacheItemPoolInterface;

interface PimcoreCacheItemPoolInterface extends TaggableCacheItemPoolInterface
{
    /**
     * Runs maintenance tasks which could take a long time. Should only be called from maintenance scripts.
     *
     * @return bool True on success
     */
    public function purge();
}
