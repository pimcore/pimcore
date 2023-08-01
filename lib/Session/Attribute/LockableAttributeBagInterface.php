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

namespace Pimcore\Session\Attribute;

use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

interface LockableAttributeBagInterface extends AttributeBagInterface
{
    /**
     * Lock the attribute bag (disallow modifications)
     */
    public function lock(): void;

    /**
     * Unlock the attribute bag
     */
    public function unlock(): void;

    /**
     * Get lock status
     */
    public function isLocked(): bool;
}
