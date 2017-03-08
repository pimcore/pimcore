<?php

namespace Pimcore\Cache\Pool;

interface PurgeableCacheItemPoolInterface
{
    /**
     * Runs maintenance tasks which could take a long time. Should only be called from maintenance scripts.
     *
     * @return bool True on success
     */
    public function purge();
}
