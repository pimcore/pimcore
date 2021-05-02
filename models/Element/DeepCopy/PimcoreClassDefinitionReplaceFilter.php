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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Element\DeepCopy;

use DeepCopy\Filter\Filter;
use DeepCopy\Reflection\ReflectionHelper;
use Pimcore\Model\DataObject\Concrete;

/**
 * @internal
 */
class PimcoreClassDefinitionReplaceFilter implements Filter
{
    /**
     * @var callable
     */
    protected $callback;

    /**
     * @param callable $callable Will be called to get the new value for each property to replace
     */
    public function __construct(callable $callable)
    {
        $this->callback = $callable;
    }

    public function apply($object, $property, $objectCopier)
    {
        if (!$object instanceof Concrete) {
            return;
        }

        $fieldDefinition = $object->getClass()->getFieldDefinition($property);

        if (!$fieldDefinition) {
            return;
        }

        $reflectionProperty = ReflectionHelper::getProperty($object, $property);
        $reflectionProperty->setAccessible(true);

        $value = ($this->callback)($object, $fieldDefinition, $property, $reflectionProperty->getValue($object));

        $reflectionProperty->setValue($object, $value);
    }
}
