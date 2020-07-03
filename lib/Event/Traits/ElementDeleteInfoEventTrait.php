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

namespace Pimcore\Event\Traits;

/**
 * Trait ElementDeleteInfoEventTrait
 *
 * @package Pimcore\Event\Traits
 */
trait ElementDeleteInfoEventTrait
{
    /**
     * @var bool
     */
    protected $deletionAllowed = true;

    /**
     * @var string
     */
    protected $reason;

    /**
     * @return bool
     */
    public function getDeletionAllowed(): bool
    {
        return $this->deletionAllowed;
    }

    /**
     * @param bool $deletionAllowed
     */
    public function setDeletionAllowed(bool $deletionAllowed): void
    {
        $this->deletionAllowed = $deletionAllowed;
    }

    /**
     * @return string
     */
    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * @param string $reason
     */
    public function setReason(string $reason): void
    {
        $this->reason = $reason;
    }
}
