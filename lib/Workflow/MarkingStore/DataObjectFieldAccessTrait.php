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

namespace Pimcore\Workflow\MarkingStore;

use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Reads and writes a data object field through the getter/setter Pimcore generates for it.
 *
 * The PropertyAccessor camelizes the field name (product_workflow => getProductWorkflow()),
 * while Pimcore keeps underscores (getProduct_workflow()). The accessor remains the fallback
 * for subjects without such a method.
 *
 * @internal
 */
trait DataObjectFieldAccessTrait
{
    private function getFieldValue(object $subject, string $property, PropertyAccessorInterface $propertyAccessor): mixed
    {
        $getter = 'get' . ucfirst($property);

        if (method_exists($subject, $getter)) {
            return $subject->$getter();
        }

        return $propertyAccessor->getValue($subject, $property);
    }

    private function setFieldValue(object $subject, string $property, mixed $value, PropertyAccessorInterface $propertyAccessor): void
    {
        $setter = 'set' . ucfirst($property);

        if (method_exists($subject, $setter)) {
            $subject->$setter($value);

            return;
        }

        $propertyAccessor->setValue($subject, $property, $value);
    }
}
