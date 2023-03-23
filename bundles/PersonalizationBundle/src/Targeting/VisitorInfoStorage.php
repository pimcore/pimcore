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

namespace Pimcore\Bundle\PersonalizationBundle\Targeting;

use Pimcore\Bundle\PersonalizationBundle\Targeting\Model\VisitorInfo;

class VisitorInfoStorage implements VisitorInfoStorageInterface
{
    private ?VisitorInfo $visitorInfo = null;

    public function getVisitorInfo(): VisitorInfo
    {
        return $this->visitorInfo;
    }

    public function setVisitorInfo(VisitorInfo $visitorInfo): void
    {
        $this->visitorInfo = $visitorInfo;
    }

    public function hasVisitorInfo(): bool
    {
        return null !== $this->visitorInfo;
    }
}
