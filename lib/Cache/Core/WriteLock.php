<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Cache\Core;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;

/**
 * @internal
 */
class WriteLock implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected bool $enabled = true;

    protected TagAwareAdapterInterface $itemPool;

    protected string $cacheKey = 'system_cache_write_lock';

    protected int $lifetime = 30;

    /**
     * Contains the timestamp of the write lock time from the current process
     *
     * This is to recheck when removing the write lock (if the value is different -> higher) do not remove the lock
     * because then another process has acquired a lock.
     *
     */
    protected ?int $timestamp = null;

    protected bool $lockInitialized = false;

    public function __construct(TagAwareAdapterInterface $itemPool)
    {
        $this->itemPool = $itemPool;
    }

    public function enable(): void
    {
        $this->enabled = true;
    }

    public function disable(): void
    {
        $this->enabled = false;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Initialize lock value once from storage
     */
    protected function initializeLock(): void
    {
        if ($this->lockInitialized) {
            return;
        }

        $item = $this->itemPool->getItem($this->cacheKey);
        if ($item->isHit()) {
            $lock = $item->get();

            if ($this->isLockValid($lock)) {
                $this->timestamp = $lock;
            }
        }

        $this->lockInitialized = true;
    }

    /**
     * Set a write lock (prevents items being written to cache)
     *
     *
     */
    public function lock(bool $force = false): bool
    {
        if (!$this->enabled) {
            return true;
        }

        $this->initializeLock();

        if (!$this->timestamp || $force) {
            $this->timestamp = time();

            $this->logger->debug(
                'Setting write lock with timestamp {timestamp}',
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
     */
    public function hasLock(): bool
    {
        if (!$this->enabled || !$this->lockInitialized) {
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
        $this->timestamp = null;

        return false;
    }

    protected function isLockValid(int $lockTime): bool
    {
        return $lockTime > (time() - $this->lifetime);
    }

    /**
     * Remove write lock from instance and from cache
     *
     */
    public function removeLock(): bool
    {
        if (!$this->enabled || !$this->lockInitialized) {
            return true;
        }

        if ($this->timestamp) {
            $item = $this->itemPool->getItem($this->cacheKey);
            if ($item->isHit()) {
                $lock = $item->get();

                // only remove the lock if it was created by this process
                if ($lock <= $this->timestamp) {
                    $this->logger->debug(
                        'Removing write lock with timestamp {timestamp}',
                        ['timestamp' => $lock]
                    );

                    $this->itemPool->deleteItem($this->cacheKey);

                    $this->timestamp = null;

                    return true;
                } else {
                    $this->logger->debug(
                        'Not removing write lock as timestamp does not belong to this process (timestamp: {timestamp}, lock: {lock})',
                        ['timestamp' => $this->timestamp, 'lock' => $lock]
                    );

                    return false;
                }
            }
        }

        return false;
    }
}
