<?php

namespace Pimcore\Model\DataObject\ClassDefinition;

use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\CalculatedValue;

interface CalculatorClassInterface
{
    public function compute(Concrete $object, CalculatedValue $context): string;

    public function getCalculatedValueForEditMode(Concrete $object, CalculatedValue $context): string;
}
