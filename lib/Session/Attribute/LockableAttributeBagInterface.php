<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
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
