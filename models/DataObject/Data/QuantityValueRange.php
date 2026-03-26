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

use NumberFormatter;
use Pimcore;
use Pimcore\Localization\LocaleServiceInterface;
use Pimcore\Model\DataObject\QuantityValue\Unit;
use Pimcore\Model\DataObject\Traits\ObjectVarTrait;

class QuantityValueRange extends AbstractQuantityValue
{
    use ObjectVarTrait, Pimcore\Model\DataObject\Traits\RangeTrait;

    protected int|float|null $minimum;

    protected int|float|null $maximum;

    public function __construct(int|float|null $minimum, int|float|null $maximum, Unit|string|null $unit)
    {
        $this->minimum = $minimum;
        $this->maximum = $maximum;

        parent::__construct($unit);

        $this->markMeDirty();
    }

    public function getMinimum(): int|float|null
    {
        return $this->minimum;
    }

    public function setMinimum(int|float|null $minimum): void
    {
        $this->minimum = $minimum;

        $this->markMeDirty();
    }

    public function getMaximum(): int|float|null
    {
        return $this->maximum;
    }

    public function setMaximum(int|float|null $maximum): void
    {
        $this->maximum = $maximum;

        $this->markMeDirty();
    }

    public function getValue(): array
    {
        return [$this->getMinimum(), $this->getMaximum()];
    }

    public function toArray(): array
    {
        return [
            'minimum' => $this->getMinimum(),
            'maximum' => $this->getMaximum(),
            'unitId' => $this->getUnitId(),
        ];
    }

    public function __toString(): string
    {
        $locale = Pimcore::getContainer()->get(LocaleServiceInterface::class)->findLocale();

        $minimum = $this->getMinimum() ?: '-∞';
        $maximum = $this->getMaximum() ?: '+∞';
        $unit = $this->getUnit();

        if (is_numeric($minimum) && $locale) {
            $formatter = new NumberFormatter($locale, NumberFormatter::DECIMAL);
            $minimum = $formatter->format($minimum);
        }

        if (is_numeric($maximum) && $locale) {
            $formatter = new NumberFormatter($locale, NumberFormatter::DECIMAL);
            $maximum = $formatter->format($maximum);
        }

        if ($unit instanceof Unit) {
            $translator = Pimcore::getContainer()->get('translator');
            $unit = $translator->trans($unit->getAbbreviation(), [], 'admin');
        }

        return sprintf('[%s, %s] %s', $minimum, $maximum, $unit);
    }
}
