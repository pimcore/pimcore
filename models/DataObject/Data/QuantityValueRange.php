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

use NumberFormatter;
use Pimcore;
use Pimcore\Localization\LocaleServiceInterface;
use Pimcore\Model\DataObject\QuantityValue\Unit;
use Pimcore\Model\DataObject\Traits\ObjectVarTrait;

class QuantityValueRange extends AbstractQuantityValue
{
    use ObjectVarTrait;

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

    public function getRange(int $step = 1): array
    {
        return range($this->getMinimum(), $this->getMaximum(), $step);
    }

    public function getValue(int $step = 1): array
    {
        return $this->getRange($step);
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
