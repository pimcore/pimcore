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

use Pimcore\Cache\Pool\PimcoreCacheItemPoolInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class WriteLock implements WriteLockInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * @var PimcoreCacheItemPoolInterface
     */
    protected $itemPool;

    /**
     * @var string
     */
    protected $cacheKey = 'system_cache_write_lock';

    /**
     * @var int
     */
    protected $lifetime = 30;

    /**
     * Contains the timestamp of the write lock time from the current process
     *
     * This is to recheck when removing the write lock (if the value is different -> higher) do not remove the lock
     * because then another process has acquired a lock.
     *
     * @var int|null
     */
    protected $timestamp = 0;

    /**
     * @param PimcoreCacheItemPoolInterface $itemPool
     */
    public function __construct(PimcoreCacheItemPoolInterface $itemPool)
    {
        $this->itemPool = $itemPool;

        $this->initializeLock();
    }

    /**
     * Initialize lock value once from storage
     */
    protected function initializeLock()
    {
        $item = $this->itemPool->getItem($this->cacheKey);
        if ($item->isHit()) {
            $lock = $item->get();

            if ($this->isLockValid($lock)) {
                $this->timestamp = $lock;
            }
        }
    }

    /**
     * Set a write lock (prevents items being written to cache)
     *
     * @param bool $force
     * @return bool
     */
    public function lock($force = false)
    {
        if (!$this->enabled) {
            return true;
        }

        if (!$this->timestamp || $force) {
            $this->timestamp = time();

            $this->logger->debug(
                sprintf('Setting write lock with timestamp %d', $this->timestamp),
                ['timestamp' => $this->timestamp]
            );

            $item = $this->itemPool->getItem($this->cacheKey);
            $item->set($this->timestamp);
            $item->expiresAfter($this->lifetime);

            return $this->itemPool->save($item);
        }

        return false;
    }

    /**
     * Check if a write lock is active
     *
     * @return bool
     */
    public function hasLock()
    {
        if (!$this->enabled) {
            return false;
        }

        if ($this->timestamp && $this->timestamp > 0) {
            return true;
        }

        $item = $this->itemPool->getItem($this->cacheKey);
        if ($item->isHit()) {
            $lock = $item->get();

            if ($this->isLockValid($lock)) {
                $this->timestamp = $lock;
                return true;
            }
        }

        // normalize timestamp
        $this->timestamp = 0;

        return false;
    }

    /**
     * @param int $lockTime
     * @return bool
     */
    protected function isLockValid($lockTime)
    {
        return $lockTime > (time() - $this->lifetime);
    }

    /**
     * Remove write lock from instance and from cache
     *
     * @return bool
     */
    public function removeLock()
    {
        if (!$this->enabled) {
            return true;
        }

        if ($this->timestamp) {
            $item = $this->itemPool->getItem($this->cacheKey);
            if ($item->isHit()) {
                $lock = $item->get();

                // only remove the lock if it was created by this process
                if ($lock <= $this->timestamp) {
                    $this->logger->debug(
                        sprintf('Removing write lock with timestamp %d', $lock),
                        ['timestamp' => $lock]
                    );

                    $this->itemPool->deleteItem($this->cacheKey);

                    $this->timestamp = 0;

                    return true;
                } else {
                    $this->logger->debug(
                        sprintf('Not removing write lock as timestamp does not belong to this process (timestamp: %d, lock: %d)', $this->timestamp, $lock),
                        ['timestamp' => $this->timestamp, 'lock' => $lock]
                    );

                    return false;
                }
            }
        }

        return false;
    }
}
