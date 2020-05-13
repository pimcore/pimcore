<?php

namespace Pimcore\Model\DataObject\QuantityValue;

use Pimcore\Model\DataObject\Data\QuantityValue;

class DefaultConverter implements QuantityValueConverterInterface
{
    public function convert(QuantityValue $quantityValue, Unit $toUnit): QuantityValue
    {
        $fromUnit = $quantityValue->getUnit();
        if (!$fromUnit instanceof Unit) {
            throw new \Exception('Quantity value has no unit');
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

        if ($fromBaseUnit === null || $toBaseUnit === null || $fromBaseUnit->getId() !== $toBaseUnit->getId()) {
            throw new \Exception($fromUnit.' must have same base unit as '.$toUnit.' to be able to convert values');
        }

        $convertedValue = ($quantityValue->getValue() * $fromUnit->getFactor() - $fromUnit->getConversionOffset()) / $toUnit->getFactor() + $toUnit->getConversionOffset();

        return new QuantityValue($convertedValue, $toUnit->getId());
    }
}
