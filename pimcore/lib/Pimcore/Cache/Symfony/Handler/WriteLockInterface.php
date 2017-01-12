<?php
namespace Pimcore\Cache\Symfony\Handler;

interface WriteLockInterface
{
    /**
     * Set a write lock (prevents items being written to cache)
     *
     * @param bool $force
     * @return bool
     */
    public function lock($force = false);

    /**
     * Check if a write lock is active
     *
     * @return bool
     */
    public function hasLock();

    /**
     * Remove write lock from instance and from cache
     *
     * @return bool
     */
    public function removeLock();
}
