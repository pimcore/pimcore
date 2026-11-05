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

namespace Pimcore\Model\DataObject;

use Exception;
use Pimcore;
use Pimcore\Model;
use Pimcore\Model\DataObject\ClassDefinition\Data\PreGetDataInterface;
use Pimcore\Model\DataObject\Classificationstore\RelationDescriptor;
use Pimcore\Model\DataObject\Classificationstore\RelationRequest;
use Pimcore\Model\DataObject\Classificationstore\RelationResolver;
use Pimcore\Model\Element\DirtyIndicatorInterface;
use Pimcore\Tool;

/**
 * @method \Pimcore\Model\DataObject\Classificationstore\Dao createUpdateTable()
 * @method \Pimcore\Model\DataObject\Classificationstore\Dao getDao()
 * @method void delete()
 * @method Classificationstore load()
 * @method void save()
 */
class Classificationstore extends Model\AbstractModel implements DirtyIndicatorInterface
{
    use Model\Element\Traits\DirtyIndicatorTrait;

    /**
     * @internal
     *
     */
    protected array $items = [];

    /**
     * @internal
     *
     */
    protected Concrete|Model\Element\ElementDescriptor|null $object = null;

    /**
     * @internal
     */
    protected ?ClassDefinition $class = null;

    /**
     * @internal
     *
     */
    protected string $fieldname;

    /**
     * @internal
     *
     * @var array<int, bool>
     */
    protected array $activeGroups = [];

    /**
     * @internal
     *
     * @var array<int, int>
     */
    protected array $groupCollectionMapping = [];

    public function __construct(?array $items = null)
    {
        if ($items) {
            $this->setItems($items);
            $this->markFieldDirty('_self');
        }
    }

    public function addItem(array $item): void
    {
        $this->items[] = $item;
        $this->markFieldDirty('_self');
    }

    public function setItems(array $items): static
    {
        $this->items = $items;
        $this->markFieldDirty('_self');

        return $this;
    }

    public function getItems(): array
    {
        $doGetInheritedValues = Model\DataObject::doGetInheritedValues();
        if (!$doGetInheritedValues) {
            return $this->items;
        }

        return $this->getAllDataFromField(
            fn ($classificationStore, $fieldsArray) => $this->mergeArrays(
                $fieldsArray,
                $classificationStore->items
            )
        );
    }

    public function setObject(Concrete $object): static
    {
        if ($this->object) {
            if ($this->object->getId() != $object->getId()) {
                $this->markFieldDirty('_self');
            }
        }
        $this->object = $object;

        return $this;
    }

    public function getObject(): ?Concrete
    {
        return $this->object;
    }

    public function setClass(?ClassDefinition $class): static
    {
        $this->class = $class;

        return $this;
    }

    public function getClass(): ?ClassDefinition
    {
        if (!$this->class && $this->getObject()) {
            $this->class = $this->getObject()->getClass();
        }

        return $this->class;
    }

    public function getLanguage(?string $language = null): string
    {
        if ($language) {
            return $language;
        }

        return 'default';
    }

    /**
     *
     * @return $this
     *
     * @throws Exception
     */
    public function setLocalizedKeyValue(int $groupId, int $keyId, mixed $value, ?string $language = null): static
    {
        if (!$groupId) {
            throw new Exception('groupId not valid');
        }

        if (!$keyId) {
            throw new Exception('keyId not valid');
        }

        $language = $this->getLanguage($language);

        // treat value "0" nonempty
        $nonEmpty = is_string($value) ? strlen($value) > 0 : isset($value);

        // Workaround for booleanSelect
        // @TODO Find a better solution for using isEmpty() in all ClassDefintion DataTypes

        $keyConfig = Model\DataObject\Classificationstore\DefinitionCache::get($keyId);
        $dataDefinition = Model\DataObject\Classificationstore\Service::getFieldDefinitionFromKeyConfig($keyConfig);

        // set the given group to active groups
        $this->setActiveGroups($this->activeGroups + [$groupId => true]);

        // no ResourcePersistenceAwareInterface, so the comparison below cannot judge it
        if (!$this->isFieldDirty('_self') && $this->getRelationResolver()->isRelation($dataDefinition)) {
            $this->markFieldDirty('_self');
        }

        if (!$this->isFieldDirty('_self') && $dataDefinition instanceof Model\DataObject\ClassDefinition\Data\ResourcePersistenceAwareInterface) {
            if ($this->object) {
                $oldData = $this->items[$groupId][$keyId][$language] ?? null;
                $oldData = $dataDefinition->getDataForResource($oldData, $this->object, ['owner' => $this]);
                if (!$dataDefinition instanceof Model\DataObject\ClassDefinition\Data\Password) {
                    $oldData = serialize($oldData);
                }

                $newData = $dataDefinition->getDataForResource($value, $this->object, ['owner' => $this]);
                if ($dataDefinition instanceof Model\DataObject\ClassDefinition\Data\Password) {
                    $value = $newData;
                } else {
                    $newData = serialize($newData);
                }

                if ($newData != $oldData) {
                    $this->markFieldDirty('_self');
                }
            } else {
                $this->markFieldDirty('_self');
            }
        }

        if ($dataDefinition instanceof Model\DataObject\ClassDefinition\Data\BooleanSelect) {
            $nonEmpty = true;
        }

        if ($dataDefinition instanceof Model\DataObject\ClassDefinition\Data\Multiselect && is_array($value) && empty($value)) {
            $nonEmpty = true;
        }

        if ($nonEmpty || $value) {
            $this->items[$groupId][$keyId][$language] = $value;
        } elseif (isset($this->items[$groupId][$keyId][$language])) {
            unset($this->items[$groupId][$keyId][$language]);
            if (empty($this->items[$groupId][$keyId])) {
                unset($this->items[$groupId][$keyId]);
                if (empty($this->items[$groupId])) {
                    unset($this->items[$groupId]);
                }
            }
        }

        return $this;
    }

    /**
     * Removes the group with the given id
     *
     */
    public function removeGroupData(int $groupId): void
    {
        unset($this->items[$groupId]);
    }

    /** Returns an array of
     */
    public function getGroupIdsWithData(): array
    {
        return array_keys($this->items);
    }

    public function getFieldname(): string
    {
        return $this->fieldname;
    }

    public function setFieldname(string $fieldname): void
    {
        $this->fieldname = $fieldname;
    }

    /**
     * @return array<int, bool>
     */
    public function getActiveGroups(): array
    {
        $doGetInheritedValues = Model\DataObject::doGetInheritedValues();
        if (!$doGetInheritedValues) {
            return $this->activeGroups;
        }

        return $this->getAllDataFromField(fn ($classificationStore, $fieldsArray) => $classificationStore->activeGroups + $fieldsArray);
    }

    private function sanitizeActiveGroups(array $activeGroups): array
    {
        $newList = [];

        if ($activeGroups) {
            foreach ($activeGroups as $key => $value) {
                if ($value) {
                    $newList[$key] = true;
                }
            }
        }

        return $newList;
    }

    /**
     * @param array<int, bool> $activeGroups
     */
    public function setActiveGroups(array $activeGroups): void
    {
        $activeGroups = $this->sanitizeActiveGroups($activeGroups);
        $diff1 = array_diff(array_keys($activeGroups), array_keys($this->activeGroups));
        $diff2 = array_diff(array_keys($this->activeGroups), array_keys($activeGroups));
        if ($diff1 || $diff2) {
            $this->markFieldDirty('_self');
        }
        $this->activeGroups = $activeGroups;
    }

    private function getFallbackValue(int $groupId, int $keyId, string $language, ClassDefinition\Data $fielddefinition): mixed
    {
        $fallbackLanguages = Tool::getFallbackLanguagesFor($language);
        $data = null;

        foreach ($fallbackLanguages as $l) {
            if (
                array_key_exists($groupId, $this->items)
                && array_key_exists($keyId, $this->items[$groupId])
                && array_key_exists($l, $this->items[$groupId][$keyId])
            ) {
                $data = $this->readItem($groupId, $keyId, $l, $fielddefinition);
                if (!$fielddefinition->isEmpty($data)) {
                    return $data;
                }
            }
        }

        foreach ($fallbackLanguages as $l) {
            $data = $this->getFallbackValue($groupId, $keyId, $l, $fielddefinition);
        }

        return $data;
    }

    /**
     *
     *
     * @throws Exception
     */
    public function getLocalizedKeyValue(
        int $groupId,
        int $keyId,
        ?string $language = 'default',
        bool $ignoreFallbackLanguage = false,
        bool $ignoreDefaultLanguage = false
    ): mixed {
        $language = $this->getLanguage($language);

        $keyConfig = Model\DataObject\Classificationstore\DefinitionCache::get($keyId);
        if ($keyConfig->getType() == 'calculatedValue') {
            $data = new Model\DataObject\Data\CalculatedValue($this->getFieldname());
            $childDef = Model\DataObject\Classificationstore\Service::getFieldDefinitionFromKeyConfig($keyConfig);
            $data->setContextualData('classificationstore', $this->getFieldname(), null, $language, $groupId, $keyId, $childDef);
            $data = Model\DataObject\Service::getCalculatedFieldValueForEditMode($this->getObject(), [], $data);

            return $data;
        }

        $fieldDefinition = Model\DataObject\Classificationstore\Service::getFieldDefinitionFromKeyConfig($keyConfig);
        $data = null;

        if (array_key_exists($groupId, $this->items) && array_key_exists($keyId, $this->items[$groupId])
                && array_key_exists($language, $this->items[$groupId][$keyId])
        ) {
            $data = $this->readItem($groupId, $keyId, $language, $fieldDefinition);
        }

        // check for fallback value
        if ($fieldDefinition->isEmpty($data) && !$ignoreFallbackLanguage && self::doGetFallbackValues()) {
            $data = $this->getFallbackValue($groupId, $keyId, $language, $fieldDefinition);
        }

        if ($fieldDefinition->isEmpty($data) && !$ignoreDefaultLanguage && $language != 'default') {
            $data = $this->readItem($groupId, $keyId, 'default', $fieldDefinition);
        }

        // check for inherited value
        $doGetInheritedValues = Model\DataObject::doGetInheritedValues();
        if ($fieldDefinition->isEmpty($data) && $doGetInheritedValues) {
            $object = $this->getObject();
            $class = $object->getClass();
            $allowInherit = $class->getAllowInherit();

            if ($allowInherit) {
                if ($object->getParent() instanceof AbstractObject) {
                    $parent = $object->getParent();
                    while ($parent && $parent->getType() == AbstractObject::OBJECT_TYPE_FOLDER) {
                        $parent = $parent->getParent();
                    }

                    if ($parent && ($parent->getType() == AbstractObject::OBJECT_TYPE_OBJECT || $parent->getType() == AbstractObject::OBJECT_TYPE_VARIANT)) {
                        /** @var Concrete $parent */
                        if ($parent->getClassId() == $object->getClassId()) {
                            $getter = 'get' . ucfirst($this->fieldname);
                            $classificationStore = $parent->$getter();
                            if ($classificationStore instanceof Classificationstore) {
                                if ($classificationStore->object->getId() != $this->object->getId()) {
                                    $data = $classificationStore->getLocalizedKeyValue($groupId, $keyId, $language, false);
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($fieldDefinition instanceof PreGetDataInterface) {
            $data = $fieldDefinition->preGetData($this, [
                'data' => $data,
                'language' => $language,
                'name' => $groupId . '-' . $keyId,
            ]);
        }

        return $data;
    }

    public static function doGetFallbackValues(): bool
    {
        return true;
    }

    /**
     * One stored value, with a relation-typed one resolved into its elements on the way out.
     *
     * The resolved elements take the descriptor's place in the items, so that reading the same key
     * twice resolves once; the store is not marked dirty for that, as nothing about what is stored
     * changed. A descriptor whose target is gone does mark it dirty - the value the read answers
     * with no longer matches the stored document, and the next ordinary save is what rewrites it.
     *
     * @throws Exception
     */
    private function readItem(int $groupId, int $keyId, string $language, ?ClassDefinition\Data $fieldDefinition): mixed
    {
        $data = $this->items[$groupId][$keyId][$language] ?? null;

        if ($fieldDefinition === null || !$data instanceof RelationDescriptor) {
            return $data;
        }

        $resolved = $this->getRelationResolver()->resolve($fieldDefinition, $data->getValue(), $this->getObject());
        $this->items[$groupId][$keyId][$language] = $resolved->getData();

        if ($resolved->hasDroppedTargets()) {
            $this->markFieldDirty('_self');
        }

        return $resolved->getData();
    }

    /**
     * Resolves every relation-typed value the store still holds as a descriptor, against one set of
     * lookups.
     *
     * For the readers that take the items as they are instead of going through
     * {@see self::getLocalizedKeyValue()} - the edit mode payload - so that they see elements, and
     * see them without a lookup per key.
     *
     * What those readers see is merged across the inheritance chain, {@see self::getItems()}, so
     * every store on that chain resolves its own descriptors - a value an ancestor contributes
     * would otherwise reach the reader as the descriptor the ancestor still holds. The chain is
     * walked the way the items are merged, and only when inheritance is on, as it is then.
     *
     * @internal
     *
     * @throws Exception
     */
    public function resolveRelationDescriptors(): void
    {
        $this->resolveOwnRelationDescriptors();

        if (!Model\DataObject::doGetInheritedValues()) {
            return;
        }

        $this->getAllDataFromField(static function (Classificationstore $store, array $carry): array {
            $store->resolveOwnRelationDescriptors();

            return $carry;
        });
    }

    /**
     * @throws Exception
     */
    private function resolveOwnRelationDescriptors(): void
    {
        $requests = $this->collectRelationRequests();

        if ($requests === []) {
            return;
        }

        $resolved = $this->getRelationResolver()->resolveAll($requests, $this->getObject());

        foreach ($requests as $index => $request) {
            $relation = $resolved[$index];

            $this->items[$request->getGroupId()][$request->getKeyId()][$request->getLanguage()]
                = $relation->getData();

            if ($relation->hasDroppedTargets()) {
                $this->markFieldDirty('_self');
            }
        }
    }

    /**
     * @return list<RelationRequest>
     *
     * @throws Exception
     */
    private function collectRelationRequests(): array
    {
        $requests = [];

        foreach ($this->items as $groupId => $keys) {
            foreach ($keys as $keyId => $languages) {
                $descriptors = array_filter(
                    $languages,
                    static fn (mixed $value): bool => $value instanceof RelationDescriptor
                );

                // only a key holding a descriptor pays for its key config here
                $fieldDefinition = $descriptors === [] ? null : $this->resolveKeyDefinition((int) $keyId);

                if ($fieldDefinition === null) {
                    continue;
                }

                foreach ($descriptors as $language => $descriptor) {
                    $requests[] = new RelationRequest(
                        (int) $groupId,
                        (int) $keyId,
                        (string) $language,
                        $fieldDefinition,
                        $descriptor->getValue()
                    );
                }
            }
        }

        return $requests;
    }

    /**
     * @throws Exception
     */
    private function resolveKeyDefinition(int $keyId): ?ClassDefinition\Data
    {
        $keyConfig = Classificationstore\DefinitionCache::get($keyId);

        return $keyConfig === null
            ? null
            : Classificationstore\Service::getFieldDefinitionFromKeyConfig($keyConfig);
    }

    private function getRelationResolver(): RelationResolver
    {
        return Pimcore::getContainer()->get(RelationResolver::class);
    }

    /**
     * @return array<int, int>
     */
    public function getGroupCollectionMappings(): array
    {
        $doGetInheritedValues = Model\DataObject::doGetInheritedValues();
        if (!$doGetInheritedValues) {
            return $this->groupCollectionMapping;
        }

        return $this->getAllDataFromField(fn ($classificationStore, $fieldsArray) => $fieldsArray + $classificationStore->groupCollectionMapping);
    }

    /**
     * @param array<int, int> $groupCollectionMapping
     */
    public function setGroupCollectionMappings(array $groupCollectionMapping): void
    {
        $this->groupCollectionMapping = $groupCollectionMapping;
    }

    public function setGroupCollectionMapping(?int $groupId = null, ?int $collectionId = null): void
    {
        if ($groupId && $collectionId) {
            $this->groupCollectionMapping[$groupId] = $collectionId;
        }
    }

    public function getGroupCollectionMapping(int $groupId): ?int
    {
        return $this->getGroupCollectionMappings()[$groupId] ?? null;
    }

    /**
     * @return Model\DataObject\Classificationstore\Group[]
     */
    public function getGroups(): array
    {
        return Classificationstore::getActiveGroupsWithConfig($this);
    }

    private function getAllDataFromField(callable $mergeFunction): array
    {
        $fieldsArray = $mergeFunction($this, []);
        $object = $this->getObject();
        while (!is_null($object) && ($parent = Service::hasInheritableParentObject($object)) !== null) {
            $fieldsArray = $mergeFunction($parent->{'get' . ucfirst($this->getFieldname())}(), $fieldsArray);

            $object = $parent;
        }

        return $fieldsArray;
    }

    /**
     * @return Model\DataObject\Classificationstore\Group[]
     */
    private static function getActiveGroupsWithConfig(Classificationstore $classificationStore): array
    {
        $groups = [];
        $activeGroups = $classificationStore->getActiveGroups();
        foreach (array_keys($activeGroups) as $groupId) {
            if ($groupConfig = $classificationStore->getGroupConfigById($groupId)) {
                $groups[] = $classificationStore->createGroup($classificationStore, $groupConfig);
            }
        }

        return $groups;
    }

    public function createGroup(
        Classificationstore $classificationstore,
        Classificationstore\GroupConfig $groupConfig
    ): Model\DataObject\Classificationstore\Group {
        return new Model\DataObject\Classificationstore\Group($classificationstore, $groupConfig);
    }

    private function getGroupConfigById(int $groupId): ?Classificationstore\GroupConfig
    {
        return Classificationstore\GroupConfig::getById($groupId);
    }

    private function mergeArrays(array $a1, array $a2): array
    {
        foreach ($a1 as $key => $value) {
            if (array_key_exists($key, $a2)) {
                if (is_array($value)) {
                    $a2[$key] = $this->mergeArrays($value, $a2[$key]);
                } else {
                    $a2[$key] = $value;
                }
            } else {
                $a2[$key] = $value;
            }
        }

        return $a2;
    }
}
