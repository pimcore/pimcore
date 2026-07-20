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

namespace Pimcore\Model\DataObject\Data;

use Pimcore\Model\DataObject\OwnerAwareFieldInterface;
use Pimcore\Model\DataObject\Traits\OwnerAwareFieldTrait;
use Pimcore\Model\DataObject\Traits\RangeTrait;

class NumericRange implements OwnerAwareFieldInterface
{
    use OwnerAwareFieldTrait, RangeTrait;

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
