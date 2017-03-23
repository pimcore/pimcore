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

interface WriteLockInterface
{
    /**
     * Enables the write lock
     */
    public function enable();

    /**
     * Disables the write lock
     */
    public function disable();

    /**
     * Determines if the write lock is enabled
     *
     * @return bool
     */
    public function isEnabled();

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
