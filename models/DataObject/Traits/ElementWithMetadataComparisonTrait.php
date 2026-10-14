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

namespace Pimcore\Model\DataObject\Traits;

use Pimcore\Model\DataObject\Data\ElementMetadata;
use Pimcore\Model\DataObject\Data\ObjectMetadata;

/**
 * @internal
 */
trait ElementWithMetadataComparisonTrait
{
    public function isEqual(mixed $oldValue, mixed $newValue): bool
    {
        $count1 = is_array($oldValue) ? count($oldValue) : 0;
        $count2 = is_array($newValue) ? count($newValue) : 0;

        if ($count1 !== $count2) {
            return false;
        }

        $values1 = array_filter(array_values(is_array($oldValue) ? $oldValue : []));
        $values2 = array_filter(array_values(is_array($newValue) ? $newValue : []));

        for ($i = 0; $i < $count1; $i++) {
            // array_filter() may have dropped an entry, so either side can be missing here.
            $container1 = $values1[$i] ?? null;
            $container2 = $values2[$i] ?? null;

            if (!$container1 || !$container2) {
                return !$container1 && !$container2;
            }

            // A value can reach isEqual() as a plain element path instead of a metadata
            // container - the getElement() call below would then fatal with a TypeError. Compare
            // such entries directly, and treat a container and a path as different.
            //
            // The trait serves both ElementMetadata (AdvancedManyToManyRelation) and
            // ObjectMetadata (AdvancedManyToManyObjectRelation), which are siblings rather than
            // subclasses, so both have to pass the guard.
            if (!self::isMetadataContainer($container1) || !self::isMetadataContainer($container2)) {
                // Strict on purpose, unlike the metadata comparison below. The result of this
                // method decides whether the field is marked dirty, and a value wrongly reported
                // as equal is never written back - so "5" and "05", or 1 and "1", must not
                // collapse into one another here. Reporting a difference that is not one only
                // costs a redundant write.
                if ($container1 !== $container2) {
                    return false;
                }

                continue;
            }

            $el1 = $container1->getElement();
            $el2 = $container2->getElement();

            if (! ($el1?->getType() == $el2?->getType() && ($el1?->getId() == $el2?->getId()))) {
                return false;
            }

            $data1 = $container1->getData();
            $data2 = $container2->getData();
            if ($data1 != $data2) {
                return false;
            }
        }

        return true;
    }

    /**
     * @phpstan-assert-if-true ElementMetadata|ObjectMetadata $value
     */
    private static function isMetadataContainer(mixed $value): bool
    {
        return $value instanceof ElementMetadata || $value instanceof ObjectMetadata;
    }
}
