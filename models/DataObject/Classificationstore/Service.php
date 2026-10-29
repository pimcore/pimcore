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

namespace Pimcore\Model\DataObject\Classificationstore;

use Pimcore;
use Pimcore\Model\DataObject;

/**
 * @internal
 */
class Service
{
    /**
     * @var array Used for storing definitions
     */
    protected static array $definitionsCache = [];

    /**
     * Clears the definitions this class caches by key id - not the ones
     * {@see KeyGroupRelationCache} holds per (groupId, keyId), which are cleared with that service.
     */
    public static function clearDefinitionsCache(): void
    {
        self::$definitionsCache = [];
    }

    public static function getFieldDefinitionFromKeyConfig(KeyConfig|KeyGroupRelation $keyConfig): ?DataObject\ClassDefinition\Data
    {
        if ($keyConfig instanceof KeyConfig) {
            $cacheId = $keyConfig->getId();
        } else {
            $cacheId = $keyConfig->getKeyId();
        }

        if (array_key_exists($cacheId, self::$definitionsCache)) {
            return self::$definitionsCache[$cacheId];
        }

        $definition = $keyConfig->getDefinition();
        $definition = json_decode($definition, true);
        $type = $keyConfig->getType();
        $fd = self::getFieldDefinitionFromJson($definition, $type);
        self::$definitionsCache[$cacheId] = $fd;

        return $fd;
    }

    public static function getFieldDefinitionFromJson(array $definition, string $type): ?DataObject\ClassDefinition\Data
    {
        if (!$definition) {
            return null;
        }

        if (!$type) {
            $type = 'input';
        }

        $loader = Pimcore::getContainer()->get('pimcore.implementation_loader.object.data');

        /** @var DataObject\ClassDefinition\Data $dataDefinition */
        $dataDefinition = $loader->build($type);

        // Classification store key names are labels referenced by keyId — they are never
        // emitted into generated PHP classes or DDL, so they are exempt from the identifier
        // validation in Data::setName() (existing stores contain names like "Voltage Type").
        $name = $definition['name'] ?? null;
        unset($definition['name']);

        $dataDefinition->setValues($definition);
        $className = get_class($dataDefinition);

        $state = (array) $dataDefinition;
        unset($state['name']);
        $dataDefinition = $className::__set_state($state);

        if (is_string($name)) {
            $dataDefinition->name = $name;
        }

        if ($dataDefinition instanceof DataObject\ClassDefinition\Data\EncryptedField) {
            $delegateDefinitionRaw = $dataDefinition->getDelegate();
            $delegateDataType = $dataDefinition->getDelegateDatatype();
            $delegateDefinition = self::getFieldDefinitionFromJson((array) $delegateDefinitionRaw, $delegateDataType);
            $dataDefinition->setDelegate($delegateDefinition);
        }

        return $dataDefinition;
    }

    /**
     * Collects every classification store field reachable from the given field definitions,
     * including the ones nested in localized fields, object bricks and field collections -
     * classSaved() reaches those as well.
     *
     * @param array<string, DataObject\ClassDefinition\Data> $fieldDefinitions
     *
     * @return array<string, DataObject\ClassDefinition\Data\Classificationstore> field name => definition
     */
    public static function getClassificationStoreFieldDefinitions(array $fieldDefinitions): array
    {
        $classificationStoreFields = [];
        self::collectClassificationStoreFieldDefinitions($fieldDefinitions, $classificationStoreFields);

        return $classificationStoreFields;
    }

    /**
     * The store id of a classification store field, or null when the field carries none.
     *
     * The property is typed and has no default, so a class definition without the key leaves it
     * uninitialized - reading it through the getter would raise an Error rather than return a default.
     */
    public static function getConfiguredStoreId(
        DataObject\ClassDefinition\Data\Classificationstore $fieldDefinition
    ): ?int {
        return isset($fieldDefinition->storeId) ? $fieldDefinition->getStoreId() : null;
    }

    /**
     * @param array<string, DataObject\ClassDefinition\Data> $fieldDefinitions
     * @param array<string, DataObject\ClassDefinition\Data\Classificationstore> $classificationStoreFields
     */
    private static function collectClassificationStoreFieldDefinitions(
        array $fieldDefinitions,
        array &$classificationStoreFields
    ): void {
        foreach ($fieldDefinitions as $fieldDefinition) {
            if ($fieldDefinition instanceof DataObject\ClassDefinition\Data\Classificationstore) {
                $classificationStoreFields[$fieldDefinition->getName()] ??= $fieldDefinition;

                continue;
            }

            foreach (self::getNestedFieldDefinitions($fieldDefinition) as $nestedFieldDefinitions) {
                self::collectClassificationStoreFieldDefinitions($nestedFieldDefinitions, $classificationStoreFields);
            }
        }
    }

    /**
     * @return array<int, array<string, DataObject\ClassDefinition\Data>>
     */
    private static function getNestedFieldDefinitions(DataObject\ClassDefinition\Data $fieldDefinition): array
    {
        if ($fieldDefinition instanceof DataObject\ClassDefinition\Data\Localizedfields) {
            return [$fieldDefinition->getFieldDefinitions()];
        }

        if ($fieldDefinition instanceof DataObject\ClassDefinition\Data\Objectbricks) {
            return self::getContainerFieldDefinitions(
                $fieldDefinition->getAllowedTypes(),
                DataObject\Objectbrick\Definition::getByKey(...)
            );
        }

        if ($fieldDefinition instanceof DataObject\ClassDefinition\Data\Fieldcollections) {
            return self::getContainerFieldDefinitions(
                $fieldDefinition->getAllowedTypes(),
                DataObject\Fieldcollection\Definition::getByKey(...)
            );
        }

        return [];
    }

    /**
     * @param string[] $allowedTypes
     * @param callable(string): (DataObject\Objectbrick\Definition|DataObject\Fieldcollection\Definition|null) $loadDefinition
     *
     * @return array<int, array<string, DataObject\ClassDefinition\Data>>
     */
    private static function getContainerFieldDefinitions(array $allowedTypes, callable $loadDefinition): array
    {
        $fieldDefinitions = [];
        foreach ($allowedTypes as $allowedType) {
            $definition = $loadDefinition($allowedType);
            if ($definition !== null) {
                $fieldDefinitions[] = $definition->getFieldDefinitions();
            }
        }

        return $fieldDefinitions;
    }
}
