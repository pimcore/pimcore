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
     * The name spaces a container key can come from, used to qualify a collected field identity.
     * Object brick keys and field collection keys are unique within their own kind only, so the
     * kind has to be part of the identity for the two to stay apart.
     */
    private const OBJECTBRICK_CONTAINER = 'objectbrick';

    private const FIELDCOLLECTION_CONTAINER = 'fieldcollection';

    /**
     * The marshaller format the classification store packs its values with,
     * {@see self::marshallerPacksIntoValueColumns()}.
     */
    private const MARSHALLER_FORMAT = 'classificationstore';

    /**
     * @var array Used for storing definitions
     */
    protected static array $definitionsCache = [];

    /**
     * Clears every definition this class memoizes - the ones held by key id for a {@see KeyConfig} as
     * well as the ones held per (groupId, keyId) for a {@see KeyGroupRelation}. The definitions
     * {@see KeyGroupRelationCache} holds of its own are cleared with that service.
     *
     * Called at the messenger message boundary by
     * {@see \Pimcore\Bundle\CoreBundle\EventListener\MessengerClearRuntimeCacheListener}, so that a
     * definition edited after the first read is not answered from the memo for the life of a worker.
     */
    public static function clearDefinitionsCache(): void
    {
        self::$definitionsCache = [];
    }

    public static function getFieldDefinitionFromKeyConfig(KeyConfig|KeyGroupRelation $keyConfig): ?DataObject\ClassDefinition\Data
    {
        $cacheId = self::getDefinitionsCacheId($keyConfig);

        if ($cacheId !== null && array_key_exists($cacheId, self::$definitionsCache)) {
            return self::$definitionsCache[$cacheId];
        }

        $definition = $keyConfig instanceof KeyGroupRelation
            ? $keyConfig->getEffectiveDefinition()
            : $keyConfig->getDefinition();
        $definition = json_decode($definition, true);
        $type = $keyConfig->getType();
        $fd = self::getFieldDefinitionFromJson($definition, $type);

        if ($cacheId !== null) {
            self::$definitionsCache[$cacheId] = $fd;
        }

        return $fd;
    }

    /**
     * The entry a definition is memoized under, or null when nothing identifies what it was built
     * from: a key config that is not saved yet, whose {@see KeyConfig::getId()} is nullable and whose
     * null id would become the '' entry every other unsaved key config shares, and a relation that
     * does not answer both of the ids its own entry is made of.
     *
     * Those two ids are typed properties without a default, so a relation built with `new` carries
     * neither until it is set and reading one raises an Error. They are read through getObjectVars()
     * for that reason, the way {@see Dao} reads an uninitialised `fieldname`.
     */
    private static function getDefinitionsCacheId(KeyConfig|KeyGroupRelation $keyConfig): int|string|null
    {
        if ($keyConfig instanceof KeyConfig) {
            return $keyConfig->getId();
        }

        $relationVars = $keyConfig->getObjectVars();
        if (!isset($relationVars['groupId'], $relationVars['keyId'])) {
            return null;
        }

        // non numeric on purpose: a numeric string entry would be coerced to an int key id
        return 'group:' . $keyConfig->getGroupId() . ':key:' . $keyConfig->getKeyId();
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

        // __set_state() rebuilds the definition from its own properties, which leaves a wrapper's
        // delegate as the raw array it was decoded from - it is rebuilt here, as the inner type,
        // from exactly that array, so the inner definition keeps its own properties
        if ($dataDefinition instanceof DataObject\ClassDefinition\Data\EncryptedField
            || $dataDefinition instanceof DataObject\ClassDefinition\Data\MultiValue
        ) {
            $delegateDefinitionRaw = $dataDefinition->getDelegate();
            $delegateDataType = $dataDefinition->getDelegateDatatype();
            // a blob naming no inner type leaves a delegate-less wrapper rather than the raw decoded
            // array, which no reader of the delegate would recognise as a definition
            $delegateDefinition = $delegateDataType === null
                ? null
                : self::getFieldDefinitionFromJson((array) $delegateDefinitionRaw, $delegateDataType);
            $dataDefinition->setDelegate($delegateDefinition);
        }

        return $dataDefinition;
    }

    /**
     * The definition that answers for the VALUE of a key: the inner type of a `multiValue` wrapper,
     * and the definition itself for every other key.
     *
     * The one boundary for that question, so that the paths asking it cannot come apart. A
     * `multiValue` key declares the qualifiers its values may carry and nothing about the values
     * themselves - what a slot holds is a value of the inner type, which is what stores it, reads it
     * back, decides whether it is a relation and names its dependencies. A caller asking the wrapper
     * instead is told "no" about every one of those, and the value then reaches storage unrefused,
     * loses its dependency rows or is read without the hook its own type would have run.
     *
     * An `encryptedField` is NOT unwrapped here: that wrapper carries the stored form itself - it is
     * the one that encrypts - so its own definition is the one that answers for the value. The walk
     * below therefore stops ON one rather than going through it.
     *
     * CONSECUTIVE `multiValue` wrappers are unwrapped to the end. Nothing refuses a definition blob
     * whose `delegateDatatype` is `multiValue` again - the delegate is accepted by
     * {@see KeyTypeSupport::isAcceptedDelegateType()} and built by
     * {@see self::getFieldDefinitionFromJson()}, which recurses - and resolving one layer alone
     * answered this question with a wrapper: `multiValue<multiValue<encryptedField<input>>>`
     * normalized its way down to the secret while the engine, asked whether the value reaches the
     * document through its marshaller, was looking at a `multiValue` and stored the plaintext. One
     * boundary that answers "the value's definition" has to answer it for a chain of any length.
     *
     * @see DataObject\ClassDefinition\Data\MultiValue for the behaviours the wrapper forwards itself
     */
    public static function getValueDefinition(
        ?DataObject\ClassDefinition\Data $definition
    ): ?DataObject\ClassDefinition\Data {
        $seen = [];

        while ($definition instanceof DataObject\ClassDefinition\Data\MultiValue) {
            // a delegate that is its own ancestor can only be built by hand, and a walk that met one
            // would not end: the chain stops at the wrapper it has already answered for
            if (isset($seen[spl_object_id($definition)])) {
                break;
            }

            $seen[spl_object_id($definition)] = true;

            $delegate = $definition->getDelegate();
            if (!$delegate instanceof DataObject\ClassDefinition\Data) {
                // a wrapper naming no inner type answers itself: it normalizes the plain value
                // unchanged, {@see DataObject\ClassDefinition\Data\MultiValue::normalize()}
                break;
            }

            $definition = $delegate;
        }

        return $definition;
    }

    /**
     * Whether a value of this definition is packed into the two scalar value columns by a
     * classification store marshaller, rather than reaching one as the structure it normalizes to.
     *
     * The one boundary for that question, like {@see self::getValueDefinition()} is for "which
     * definition answers for the value", and asked of the DELEGATE of an `encryptedField`: that
     * marshaller encrypts a scalar and hands anything else to its delegate's marshaller to be packed
     * first, so a delegate that packs nothing leaves a structured value with no scalar to encrypt.
     *
     * It is answered through the COMPLETE chain. A `multiValue` marshaller packs nothing itself -
     * {@see \Pimcore\DataObject\ClassificationstoreDataMarshaller\MultiValue} hands the value to the
     * marshaller of its own inner type - so its registration says nothing about the shape it
     * produces, and a wrapper whose inner type has no marshaller returns the value in the `value`
     * member unchanged. Asked one layer deep, `encryptedField<multiValue<geopoint>>` answered "the
     * delegate has a marshaller, it packs" about a wrapper that handed the coordinates straight back
     * as an array-valued column, which
     * {@see \Pimcore\DataObject\ClassificationstoreDataMarshaller\EncryptedField::encrypt()} casts to
     * the string `'Array'`. The coordinates were lost with no error, on the document engine for want
     * of an envelope and on the EAV engine for want of a refusal.
     *
     * A chain ending in a wrapper that names no inner type does NOT pack: such a wrapper returns the
     * value it was handed in the `value` member, which is the structure rather than a column.
     */
    public static function marshallerPacksIntoValueColumns(
        ?DataObject\ClassDefinition\Data $definition,
        Pimcore\Element\MarshallerService $marshallerService
    ): bool {
        $definition = self::getValueDefinition($definition);

        if (!$definition instanceof DataObject\ClassDefinition\Data
            || $definition instanceof DataObject\ClassDefinition\Data\MultiValue
        ) {
            return false;
        }

        return $marshallerService->supportsFielddefinition(
            self::MARSHALLER_FORMAT,
            $definition->getFieldType()
        );
    }

    /**
     * The inner type of a wrapper key, or null for a key that is not one - the two wrappers a
     * classification store key can be defined with, {@see DataObject\ClassDefinition\Data\EncryptedField}
     * and {@see DataObject\ClassDefinition\Data\MultiValue}.
     */
    private static function getDelegateDefinition(
        DataObject\ClassDefinition\Data $definition
    ): ?DataObject\ClassDefinition\Data {
        if (!$definition instanceof DataObject\ClassDefinition\Data\EncryptedField
            && !$definition instanceof DataObject\ClassDefinition\Data\MultiValue
        ) {
            return null;
        }

        $delegate = $definition->getDelegate();

        return $delegate instanceof DataObject\ClassDefinition\Data ? $delegate : null;
    }

    /**
     * Whether a value of a key defined with this field type may be inherited from an ancestor's
     * store.
     *
     * The question is the key's and not the classification store field's, because one store holds
     * keys of many types at once - so every path that reaches an ancestor's store asks this, and
     * they have to agree: the per-key read ({@see DataObject\Classificationstore::getLocalizedKeyValue()}),
     * the whole-store merge ({@see DataObject\Classificationstore::getItems()}) and both halves of
     * the editmode payload's parent walk
     * ({@see DataObject\ClassDefinition\Data\Classificationstore::doGetDataForEditMode()}). One
     * answer in one place is what keeps them from coming apart.
     *
     * A WRAPPER is answered by its DELEGATE, both of them: an `encryptedField` and a `multiValue`
     * key hold a value of the inner type, and whether that value may be read off an ancestor is the
     * inner type's answer. A `multiValue` key declares qualifiers, which says nothing about it.
     *
     * An `encryptedField` is answered by its DELEGATE. The wrapper stores, reads, validates and
     * renders whatever the delegate says - {@see DataObject\ClassDefinition\Data\EncryptedField}
     * hands `getDataForEditmode()`, `checkValidity()`, `isEmpty()` and `normalize()` straight on -
     * and it does not override `supportsInheritance()`, so it answers the base class's `true` for
     * every delegate. A `consent` delegate says no for a reason that the encryption does not touch:
     * a consent is the record of what one data subject agreed to, so a child reading its parent's
     * grant - and the id of the audit note behind it - answers "has this one consented" with
     * somebody else's answer, encrypted or not. The same holds for the other types that decline
     * inheritance, `calculatedValue` and `urlSlug` among them.
     *
     * A key whose config has gone - deleted while values of it are still stored - and an
     * `encryptedField` with no delegate configured are both answered the way they were answered
     * before this check existed, because nothing is known about them to answer otherwise.
     */
    public static function supportsInheritance(?DataObject\ClassDefinition\Data $fieldDefinition): bool
    {
        if (!$fieldDefinition) {
            return true;
        }

        $delegate = self::getDelegateDefinition($fieldDefinition);

        if ($delegate !== null) {
            return self::supportsInheritance($delegate);
        }

        return $fieldDefinition->supportsInheritance();
    }

    /**
     * Collects every classification store field reachable from the given field definitions,
     * including the ones nested in localized fields, object bricks and field collections -
     * classSaved() reaches those as well.
     *
     * Two object bricks (or field collections) allowed on the same class may each define a
     * classification store field under the same name, on different stores. The keys are therefore
     * qualified by the container the field was found in - "objectbrick:brickKey.fieldName" - so that
     * no definition is dropped and the error messages name the one that is at fault. Object bricks
     * and field collections are keyed in separate name spaces, so the kind is part of the identity
     * as well: both may be called "Shared" and both may contain a "cs".
     *
     * @param array<string, DataObject\ClassDefinition\Data> $fieldDefinitions
     *
     * @return array<string, DataObject\ClassDefinition\Data\Classificationstore>
     *         container-qualified field identity => definition
     */
    public static function getClassificationStoreFieldDefinitions(array $fieldDefinitions): array
    {
        $classificationStoreFields = [];
        self::collectClassificationStoreFieldDefinitions($fieldDefinitions, $classificationStoreFields);

        return $classificationStoreFields;
    }

    /**
     * Groups collected classification store fields by the field name they are stored under.
     *
     * The value tables key their rows by (object, fieldname), so every definition sharing a field
     * name shares its rows - which is why they are resolved together rather than one at a time.
     *
     * @param array<string, DataObject\ClassDefinition\Data\Classificationstore> $classificationStoreFields
     *
     * @return array<string, array<string, int|null>> field name => (qualified identity => store id)
     */
    public static function groupStoreIdsByFieldName(array $classificationStoreFields): array
    {
        $storeIds = [];
        foreach ($classificationStoreFields as $identity => $fieldDefinition) {
            $storeIds[$fieldDefinition->getName()][$identity] = self::getConfiguredStoreId($fieldDefinition);
        }

        return $storeIds;
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
     * @param string $prefix the container path the given field definitions were reached through
     */
    private static function collectClassificationStoreFieldDefinitions(
        array $fieldDefinitions,
        array &$classificationStoreFields,
        string $prefix = ''
    ): void {
        foreach ($fieldDefinitions as $fieldDefinition) {
            if ($fieldDefinition instanceof DataObject\ClassDefinition\Data\Classificationstore) {
                $classificationStoreFields[$prefix . $fieldDefinition->getName()] = $fieldDefinition;

                continue;
            }

            foreach (self::getNestedFieldDefinitions($fieldDefinition) as $nestedPrefix => $nestedFieldDefinitions) {
                self::collectClassificationStoreFieldDefinitions(
                    $nestedFieldDefinitions,
                    $classificationStoreFields,
                    $prefix . $nestedPrefix
                );
            }
        }
    }

    /**
     * @return array<string, array<string, DataObject\ClassDefinition\Data>> container prefix => field definitions
     */
    private static function getNestedFieldDefinitions(DataObject\ClassDefinition\Data $fieldDefinition): array
    {
        if ($fieldDefinition instanceof DataObject\ClassDefinition\Data\Localizedfields) {
            // localized fields share the name space of the class they belong to, so they add no prefix
            return ['' => $fieldDefinition->getFieldDefinitions()];
        }

        if ($fieldDefinition instanceof DataObject\ClassDefinition\Data\Objectbricks) {
            return self::getContainerFieldDefinitions(
                self::OBJECTBRICK_CONTAINER,
                $fieldDefinition->getAllowedTypes(),
                DataObject\Objectbrick\Definition::getByKey(...)
            );
        }

        if ($fieldDefinition instanceof DataObject\ClassDefinition\Data\Fieldcollections) {
            return self::getContainerFieldDefinitions(
                self::FIELDCOLLECTION_CONTAINER,
                $fieldDefinition->getAllowedTypes(),
                DataObject\Fieldcollection\Definition::getByKey(...)
            );
        }

        return [];
    }

    /**
     * @param string $containerKind the name space the allowed types are keyed in - an object brick and
     *                              a field collection may carry the same key without being related
     * @param string[] $allowedTypes
     * @param callable(string): (DataObject\Objectbrick\Definition|DataObject\Fieldcollection\Definition|null) $loadDefinition
     *
     * @return array<string, array<string, DataObject\ClassDefinition\Data>> container prefix => field definitions
     */
    private static function getContainerFieldDefinitions(
        string $containerKind,
        array $allowedTypes,
        callable $loadDefinition
    ): array {
        $fieldDefinitions = [];
        foreach ($allowedTypes as $allowedType) {
            $definition = $loadDefinition($allowedType);
            if ($definition !== null) {
                $fieldDefinitions[$containerKind . ':' . $allowedType . '.'] = $definition->getFieldDefinitions();
            }
        }

        return $fieldDefinitions;
    }
}
