<?php

namespace Pimcore\Model\DataObject\ClassDefinition;

use Pimcore\Model\DataObject\Concrete;

interface DefaultValueGeneratorInterface
{
    public function getValue(Concrete $object, Data $fieldDefinition, array $context);
}
