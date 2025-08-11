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

namespace Pimcore\Event\Model\DataObject;

use Pimcore\Model\DataObject\QuantityValue\Unit;
use Symfony\Contracts\EventDispatcher\Event;

class QuantityValueUnitEvent extends Event
{
    protected Unit $unit;

    /**
     * QuantityValueUnitEvent constructor.
     *
     */
    public function __construct(Unit $unit)
    {
        $this->unit = $unit;
    }

    public function getUnit(): Unit
    {
        return $this->unit;
    }

    public function setUnit(Unit $unit): void
    {
        $this->unit = $unit;
    }
}
