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
                // the name of the localized fields is always "localizedfields", removeUnresolved() relies on it too
                $localizedField = $object->getObjectVar(self::LOCALIZED_FIELDS);
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
     * The affected fields are marked dirty, so saving the object removes the stored relation rows of the
     * elements that do not exist anymore. The dirty state is not part of the version data.
     *
     * @param list<UnresolvedReference> $references
     */
    public function removeUnresolved(Concrete $object, array $references): void
    {
        $localizedItems = null;
        $localizedLanguages = [];

        foreach ($references as $reference) {
            $fieldName = $reference->getFieldName();
            $language = $reference->getLanguage();

            if ($language === null) {
                $value = $object->getObjectVar($fieldName);
                if (is_array($value) && in_array(null, $value, true)) {
                    $object->setObjectVar($fieldName, $this->withoutNull($value), true);
                }
                $object->markFieldDirty($fieldName);

                continue;
            }

            $localizedField = $object->getObjectVar(self::LOCALIZED_FIELDS);
            if (!$localizedField instanceof Localizedfield) {
                continue;
            }

            $localizedItems ??= $localizedField->getItems();
            $value = $localizedItems[$language][$fieldName] ?? null;
            if (is_array($value) && in_array(null, $value, true)) {
                $localizedItems[$language][$fieldName] = $this->withoutNull($value);
            }
            $localizedLanguages[$language] = true;
        }

        if ($localizedLanguages !== []) {
            $this->markLocalizedFieldsDirty($object, $localizedItems, array_keys($localizedLanguages));
        }
    }

    /**
     * Only the languages in the dirty language map are saved, and both setItems() and the first
     * markLanguageAsDirty() replace that map. So the items are set once, and afterwards the map is set to the
     * languages that were dirty before plus every affected language, otherwise only the last affected language
     * would be saved and the relation rows of the others would stay.
     *
     * @param list<int|string> $languages
     */
    private function markLocalizedFieldsDirty(Concrete $object, ?array $items, array $languages): void
    {
        $localizedField = $object->getObjectVar(self::LOCALIZED_FIELDS);
        if (!$localizedField instanceof Localizedfield) {
            return;
        }

        $dirtyLanguages = $localizedField->getDirtyLanguages() ?? [];

        if ($items !== null && $items !== $localizedField->getItems()) {
            $localizedField->setItems($items);
        }

        $localizedField->resetLanguageDirtyMap();
        $localizedField->markLanguagesAsDirty($dirtyLanguages);
        foreach ($languages as $language) {
            $localizedField->markLanguageAsDirty((string) $language);
        }
        $localizedField->markFieldDirty('_self');

        $object->markFieldDirty(self::LOCALIZED_FIELDS);
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
