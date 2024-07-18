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

use Exception;
use NumberFormatter;
use Pimcore;
use Pimcore\Localization\LocaleServiceInterface;
use Pimcore\Model\DataObject\QuantityValue\Unit;

class QuantityValue extends AbstractQuantityValue
{
    protected float|int|string|null $value = null;

    public function __construct(float|int|string|null $value = null, Unit|string $unit = null)
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

        if ($this->getUnit() instanceof Unit) {
            $translator = Pimcore::getContainer()->get('translator');
            $value .= ' ' . $translator->trans($this->getUnit()->getAbbreviation(), [], 'admin');
        }

        return $value ? (string)$value : '';
    }
}
