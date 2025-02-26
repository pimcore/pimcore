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

namespace Pimcore\Model\DataObject\QuantityValue;

use Exception;
use Pimcore\Model\DataObject\Data\AbstractQuantityValue;
use Pimcore\Model\DataObject\Data\QuantityValue;
use Pimcore\Model\Exception\UnsupportedException;

class DefaultConverter implements QuantityValueConverterInterface
{
    /**
     * @throws UnsupportedException If $quantityValue is no QuantityValue
     * @throws Exception
     */
    public function convert(AbstractQuantityValue $quantityValue, Unit $toUnit): AbstractQuantityValue
    {
        if (!$quantityValue instanceof QuantityValue) {
            throw new UnsupportedException('Only QuantityValue is supported.');
        }
        $fromUnit = $quantityValue->getUnit();
        if (!$fromUnit instanceof Unit) {
            throw new Exception('Quantity value has no unit');
        }

        $fromBaseUnit = $fromUnit->getBaseunit();
        if ($fromBaseUnit === null) {
            $fromUnit = clone $fromUnit;
            $fromBaseUnit = $fromUnit;
        }

        if ($fromUnit->getFactor() === null) {
            $fromUnit->setFactor(1);
        }

        if ($fromUnit->getConversionOffset() === null) {
            $fromUnit->setConversionOffset(0);
        }

        $toBaseUnit = $toUnit->getBaseunit();
        if ($toBaseUnit === null) {
            $toUnit = clone $toUnit;
            $toBaseUnit = $toUnit;
        }

        if ($toUnit->getFactor() === null) {
            $toUnit->setFactor(1);
        }

        if ($toUnit->getConversionOffset() === null) {
            $toUnit->setConversionOffset(0);
        }

        if ($fromBaseUnit->getId() !== $toBaseUnit->getId()) {
            throw new Exception($fromUnit.' must have same base unit as '.$toUnit.' to be able to convert values');
        }

        $convertedValue = ($quantityValue->getValue() * $fromUnit->getFactor() - $fromUnit->getConversionOffset()) / $toUnit->getFactor() + $toUnit->getConversionOffset();

        return new QuantityValue($convertedValue, $toUnit->getId());
    }
}
