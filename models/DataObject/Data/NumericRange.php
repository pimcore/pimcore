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

namespace Pimcore\Model\DataObject\Data;

use Pimcore\Model\DataObject\OwnerAwareFieldInterface;
use Pimcore\Model\DataObject\Traits\OwnerAwareFieldTrait;

class NumericRange implements OwnerAwareFieldInterface
{
    use OwnerAwareFieldTrait;

    protected int|null|float $minimum = null;

    protected int|null|float $maximum = null;

    public function __construct(float|int|null $minimum, float|int|null $maximum)
    {
        $this->minimum = $minimum;
        $this->maximum = $maximum;

        $this->markMeDirty();
    }

    public function getMinimum(): float|int|null
    {
        return $this->minimum;
    }

    public function setMinimum(float|int|null $minimum): void
    {
        $this->minimum = $minimum;

        $this->markMeDirty();
    }

    public function getMaximum(): float|int|null
    {
        return $this->maximum;
    }

    public function setMaximum(float|int|null $maximum): void
    {
        $this->maximum = $maximum;

        $this->markMeDirty();
    }

    public function getRange(int|float $step = 1): array
    {
        return range($this->getMinimum(), $this->getMaximum(), $step);
    }

    public function toArray(): array
    {
        return [
            'minimum' => $this->getMinimum(),
            'maximum' => $this->getMaximum(),
        ];
    }

    public function __toString(): string
    {
        $minimum = $this->getMinimum() ?: '-∞';
        $maximum = $this->getMaximum() ?: '+∞';

        return sprintf('[%s, %s]', $minimum, $maximum);
    }
}
