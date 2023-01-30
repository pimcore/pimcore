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

namespace Pimcore\Bundle\PersonalizationBundle\Targeting\Model;

use Pimcore\Bundle\PersonalizationBundle\Model\Tool\Targeting\TargetGroup;

class TargetGroupAssignment
{
    private TargetGroup $targetGroup;

    private int $count = 1;

    public function __construct(TargetGroup $targetGroup, int $count = 1)
    {
        $this->targetGroup = $targetGroup;

        $this->setCount($count);
    }

    public function getTargetGroup(): TargetGroup
    {
        return $this->targetGroup;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function setCount(int $count): void
    {
        if ($count < 0) {
            throw new \OutOfBoundsException('Count must be a positive integer');
        }

        $this->count = $count;
    }

    public function inc(int $amount = 1): void
    {
        $this->setCount($this->count += $amount);
    }
}
