<?php

namespace Pimcore\Model\DataObject\QuantityValue;

use Pimcore\Model\DataObject\Data\QuantityValue;

interface QuantityValueConverterInterface
{
    public function convert(QuantityValue $quantityValue, Unit $toUnit): QuantityValue;
}
