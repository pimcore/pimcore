<?php

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

use Pimcore\Localization\LocaleServiceInterface;
use Pimcore\Model\DataObject\QuantityValue\Unit;
use Pimcore\Model\DataObject\Traits\ObjectVarTrait;

class QuantityValue extends AbstractQuantityValue
{
    use ObjectVarTrait;

    /**
     * @var float|int|null
     */
    protected $value;

    /**
     * @param float|int|null $value
     * @param Unit|string|null $unit
     */
    public function __construct($value = null, $unit = null)
    {
        $this->value = $value;
        parent::__construct($unit);
    }

    /**
     * @param float|int|null $value
     */
    public function setValue($value)
    {
        $this->value = $value;
        $this->markMeDirty();
    }

    /**
     * @return float|int|null
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return string
     *
     * @throws \Exception
     */
    public function __toString()
    {
        $value = $this->getValue();
        if (is_numeric($value)) {
            $locale = \Pimcore::getContainer()->get(LocaleServiceInterface::class)->findLocale();

            if ($locale) {
                $formatter = new \NumberFormatter($locale, \NumberFormatter::DECIMAL);
                $value = $formatter->format($value);
            }
        }

        if ($this->getUnit() instanceof Unit) {
            $translator = \Pimcore::getContainer()->get('translator');
            $value .= ' ' . $translator->trans($this->getUnit()->getAbbreviation(), [], 'admin');
        }

        return $value ? (string)$value : '';
    }
}
