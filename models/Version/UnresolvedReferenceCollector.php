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

namespace Pimcore\Model\Version;

use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Data\Relations\AbstractRelations;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Localizedfield;
use Pimcore\Model\Element\ElementDescriptor;
use Pimcore\Model\Element\Service;
use function in_array;
use function is_array;

/**
 * Finds the relations of a stored (still marshalled) object version that point to elements which do not exist
 * anymore. Unmarshalling replaces them with null, so they have to be read before.
 *
 * Covers the relation fields of the class and of its localized fields.
 *
 * @internal
 */
final class UnresolvedReferenceCollector
{
    private const LOCALIZED_FIELDS = 'localizedfields';

    /**
     * @return list<UnresolvedReference>
     */
    public function collect(Concrete $object): array
    {
        // do not call getClass() on the object as this would set the class on the marshalled data
        $class = ClassDefinition::getById($object->getClassId());
        if ($class === null) {
            return [];
        }

        $references = [];

        foreach ($class->getFieldDefinitions() as $name => $fieldDefinition) {
            if ($fieldDefinition instanceof AbstractRelations) {
                $this->collectValue($references, $object->getObjectVar($name), $name, null);

                continue;
            }

            if ($fieldDefinition instanceof Localizedfields) {
                $localizedField = $object->getObjectVar($name);
                if (!$localizedField instanceof Localizedfield) {
                    continue;
                }

                foreach ($localizedField->getItems() as $language => $values) {
                    foreach ($fieldDefinition->getFieldDefinitions() as $childName => $childDefinition) {
                        if ($childDefinition instanceof AbstractRelations && is_array($values)) {
                            $this->collectValue($references, $values[$childName] ?? null, $childName, (string) $language);
                        }
                    }
                }
            }
        }

        return $references;
    }

    /**
     * Unmarshalling leaves null in the place of an element that does not exist anymore. A null is no valid entry
     * of a relation list (saving the object fails on it), so it is removed from the relation lists of the
     * unmarshalled object; the unresolved references collected before still tell what was there.
     *
     * @param list<UnresolvedReference> $references
     */
    public function removeUnresolved(Concrete $object, array $references): void
    {
        $class = ClassDefinition::getById($object->getClassId());
        if ($class === null) {
            return;
        }

        foreach ($references as $reference) {
            if ($reference->getIndex() === null) {
                continue;
            }

            $fieldName = $reference->getFieldName();
            $language = $reference->getLanguage();

            if ($language === null) {
                $value = $object->getObjectVar($fieldName);
                if (is_array($value) && in_array(null, $value, true)) {
                    $object->setObjectVar($fieldName, $this->withoutNull($value), true);
                }

                continue;
            }

            $localizedField = $object->getObjectVar(self::LOCALIZED_FIELDS);
            if (!$localizedField instanceof Localizedfield) {
                continue;
            }

            $items = $localizedField->getItems();
            $value = $items[$language][$fieldName] ?? null;
            if (is_array($value) && in_array(null, $value, true)) {
                $items[$language][$fieldName] = $this->withoutNull($value);
                $localizedField->setItems($items);
            }
        }
    }

    private function withoutNull(array $value): array
    {
        return array_values(array_filter($value, static fn (mixed $item) => $item !== null));
    }

    /**
     * @param list<UnresolvedReference> $references
     */
    private function collectValue(array &$references, mixed $value, string $fieldName, ?string $language): void
    {
        if ($value instanceof ElementDescriptor) {
            if ($this->isMissing($value)) {
                $references[] = $this->createReference($value, $fieldName, $language, null);
            }

            return;
        }

        if (!is_array($value)) {
            return;
        }

        foreach (array_values($value) as $index => $item) {
            if ($item instanceof ElementDescriptor && $this->isMissing($item)) {
                $references[] = $this->createReference($item, $fieldName, $language, $index);
            }
        }
    }

    private function isMissing(ElementDescriptor $descriptor): bool
    {
        return Service::getElementById($descriptor->getType(), $descriptor->getId()) === null;
    }

    private function createReference(
        ElementDescriptor $descriptor,
        string $fieldName,
        ?string $language,
        ?int $index
    ): UnresolvedReference {
        return new UnresolvedReference(
            $fieldName,
            $language,
            $index,
            $descriptor->getType(),
            $descriptor->getId()
        );
    }
}
