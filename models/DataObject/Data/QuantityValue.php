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

use Exception;
use NumberFormatter;
use Pimcore;
use Pimcore\Localization\LocaleServiceInterface;
use Pimcore\Model\DataObject\QuantityValue\Unit;

class QuantityValue extends AbstractQuantityValue
{
    protected float|int|string|null $value = null;

    public function __construct(float|int|string|null $value = null, Unit|string|null $unit = null)
    {
        $this->value = $value;
        parent::__construct($unit);
    }

    public function setValue(float|int|string|null $value): void
    {
        $this->value = $value;
        $this->markMeDirty();
    }

    public function getValue(): float|int|string|null
    {
        return $this->value;
    }

    /**
     * @throws Exception
     */
    public function __toString(): string
    {
        $value = $this->getValue();
        if (is_numeric($value)) {
            $locale = Pimcore::getContainer()->get(LocaleServiceInterface::class)->findLocale();

            if ($locale) {
                $formatter = new NumberFormatter($locale, NumberFormatter::DECIMAL);
                $value = $formatter->format((float) $value);
            }
        }

        $unit = $this->getUnit();
        if ($unit instanceof Unit) {
            if ($unit->getAbbreviation() === null) {
                $unitAbbreviation = $unit->getId();
            } else {
                $translator = Pimcore::getContainer()->get('translator');
                $unitAbbreviation = $translator->trans($unit->getAbbreviation(), [], 'admin');
            }

            $value .= ' ' . $unitAbbreviation;
        }

        return $value ? (string)$value : '';
    }
}
