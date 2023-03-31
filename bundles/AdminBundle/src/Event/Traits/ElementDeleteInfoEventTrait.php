<?php

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

namespace Pimcore\Bundle\AdminBundle\Event\Traits;

/**
 * Trait ElementDeleteInfoEventTrait
 *
 * @package Pimcore\Event\Traits
 */
trait ElementDeleteInfoEventTrait
{
    protected bool $deletionAllowed = true;

    protected string $reason;

    public function getDeletionAllowed(): bool
    {
        return $this->deletionAllowed;
    }

    public function setDeletionAllowed(bool $deletionAllowed): void
    {
        $this->deletionAllowed = $deletionAllowed;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function setReason(string $reason): void
    {
        $this->reason = $reason;
    }
}
