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
use Pimcore\Model;
use Pimcore\Model\DataObject\ClassDefinition\Data\PreGetDataInterface;
use Pimcore\Model\Element\DirtyIndicatorInterface;
use Pimcore\Normalizer\NormalizerInterface;
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

        foreach ($items as $group) {
            if (!is_array($group)) {
                continue;
            }
            foreach ($group as $key) {
                if (!is_array($key)) {
                    continue;
                }
                foreach ($key as $language => $value) {
                    $this->attachOwner($value, (string) $language);
                }
            }
        }

        return $this;
    }

    /**
     * Makes a value that carries its own setters report a change back to this store.
     *
     * A value of a type such as geopoint, numericRange or quantityValue is handed out by
     * {@see self::getLocalizedKeyValue()} as the object that is stored, and is as readily changed
     * through its own setters as it is set again. Those setters call `markMeDirty()`, which reaches
     * whatever the value carries as its owner - with no owner it reaches nothing, so
     * {@see Classificationstore\Engine\EngineInterface::save()} finds a clean store and writes
     * nothing: the change is lost without an error.
     *
     * The owner is this store and not the object, the same way a localized value's owner is its
     * {@see Localizedfield} container rather than the object - that container hands itself down as
     * `$params['owner']` from its own DAO, and `markMeDirty()` reads exactly that - because the
     * container is what holds the dirty flag its storage reads. `_self` is the one field name this
     * store's dirty map uses.
     *
     * The walk reaches the values a value is made of, not only the value itself: a `geopolygon` is
     * a plain array of {@see Data\GeoCoordinates}, an `imageGallery` holds its
     * {@see Data\Hotspotimage} items, a `geobounds` its two corners and an `encryptedField` its
     * plain value - each of them owner-aware and each with setters of its own, so a store that
     * attached the outer value alone would lose `$gallery->getItems()[0]->setCrop(...)` after a
     * reload exactly as it lost the outer setter before. Only an array and an owner-aware value are
     * descended into, which stops the walk at the elements a value references - an asset, an object
     * - rather than walking an element graph, and the identity guard keeps a value that holds
     * itself from looping.
     *
     * This is the single site for it on purpose: both engines hydrate through
     * {@see self::setLocalizedKeyValue()} and reset the dirty map afterwards, so one attachment
     * covers assignment and hydration and cannot leave a freshly loaded store dirty.
     *
     * @param array<int, true> $seen the values already attached on this walk, by object id
     */
    private function attachOwner(mixed $value, ?string $language, array &$seen = []): void
    {
        if (is_array($value)) {
            foreach ($value as $member) {
                $this->attachOwner($member, $language, $seen);
            }

            return;
        }

        if (!$value instanceof OwnerAwareFieldInterface || isset($seen[spl_object_id($value)])) {
            return;
        }

        $seen[spl_object_id($value)] = true;

        $value->_setOwner($this);
        $value->_setOwnerFieldname('_self');
        $value->_setOwnerLanguage($language);

        // the members of the value, whatever their visibility: a container holds its children in a
        // protected property, and the one that reads them back is a getter this code cannot name
        foreach ((array) $value as $member) {
            if ($member !== $this) {
                $this->attachOwner($member, $language, $seen);
            }
        }
    }

    public function getItems(): array
    {
        $doGetInheritedValues = Model\DataObject::doGetInheritedValues();
        if (!$doGetInheritedValues) {
            return $this->items;
        }

        // The walk starts at this store itself, whose own values are not inherited values: they are
        // taken whatever their key's definition says about inheritance. Every store after it is an
        // ancestor's and contributes only the keys that may be inherited at all, the same question
        // {@see self::getLocalizedKeyValue()} asks of the one key it reads - a value a per-key read
        // refuses to inherit must not arrive through the whole-store read instead.
        $own = true;

        return $this->getAllDataFromField(
            function ($classificationStore, $fieldsArray) use (&$own) {
                $items = $own
                    ? $classificationStore->items
                    : $this->inheritableItems($classificationStore->items);
                $own = false;

                return $this->mergeArrays($fieldsArray, $items);
            }
        );
    }

    /**
     * The items of an ancestor's store this store may read as inherited values: the ones whose key
     * is defined with a field type that supports inheritance.
     *
     * The question is asked of the KEY's definition rather than of the field's, because one store
     * holds keys of many types at once. `consent` is the type in the set this release unlocks that
     * answers no ({@see Model\DataObject\ClassDefinition\Data\Consent::supportsInheritance()}): a
     * consent is the record of what one data subject agreed to, so a child object reading its
     * parent's grant - and the id of the audit note behind it - would answer "has this one
     * consented" with somebody else's answer. An `input` key beside it inherits as it always has.
     *
     * @param array<int, array<int, array<string, mixed>>> $items structure: [groupId][keyId][language]
     *
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function inheritableItems(array $items): array
    {
        $keyIds = [];
        foreach ($items as $keys) {
            foreach (array_keys($keys) as $keyId) {
                $keyIds[] = (int) $keyId;
            }
        }

        // resolved in one statement on a cold cache rather than one per key: this runs on every
        // read of a store that has an inheritable ancestor
        Model\DataObject\Classificationstore\DefinitionCache::load($keyIds);

        $inheritable = [];

        foreach ($items as $groupId => $keys) {
            foreach ($keys as $keyId => $values) {
                if ($this->keySupportsInheritance((int) $keyId)) {
                    $inheritable[$groupId][$keyId] = $values;
                }
            }
        }

        return $inheritable;
    }

    /**
     * Whether a value of this key may be inherited. A key whose config has gone - deleted while
     * values of it are still stored - is answered the way it was answered before this check
     * existed, because nothing is known about it to answer otherwise.
     */
    private function keySupportsInheritance(int $keyId): bool
    {
        $keyConfig = Model\DataObject\Classificationstore\DefinitionCache::get($keyId);
        if (!$keyConfig) {
            return true;
        }

        return Model\DataObject\Classificationstore\Service::getFieldDefinitionFromKeyConfig($keyConfig)
            ->supportsInheritance();
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

        if (!$this->isFieldDirty('_self')) {
            if ($this->object
                && $dataDefinition instanceof Model\DataObject\ClassDefinition\Data\ResourcePersistenceAwareInterface
            ) {
                $oldValue = $this->items[$groupId][$keyId][$language] ?? null;
                $oldData = $dataDefinition->getDataForResource($oldValue, $this->object, ['owner' => $this]);
                $newData = $dataDefinition->getDataForResource($value, $this->object, ['owner' => $this]);

                // two resource forms that are both `null` are not evidence that the value is
                // unchanged: that is also what a definition whose resource form cannot carry the
                // value returns for every value it is handed.
                // ClassDefinition\Data\EncryptedField::getDataForResource() does exactly that when
                // its delegate is not ResourcePersistenceAware - a combination this release
                // accepts, because the document engine stores what normalize() returns and needs no
                // resource form at all. Two values that are both absent are the one case where two
                // nulls do say the value is unchanged.
                $undecided = $newData === null && $oldData === null
                    && ($value !== null || $oldValue !== null);

                if ($dataDefinition instanceof Model\DataObject\ClassDefinition\Data\Password) {
                    $value = $newData;
                    $differ = $newData != $oldData;
                } else {
                    $differ = serialize($newData) != serialize($oldData);
                }

                // Resource forms that differ say the value changed. Resource forms that are equal
                // do not say it did not, and `null` on both sides is only the loudest way they
                // mislead: a resource form can also be NARROWER than what is stored.
                // StructuredTable::getDataForResource() returns one entry per configured row and
                // column - `[]` for a table with none at all - while
                // StructuredTable::normalize() keeps the whole table, and a document holds what
                // normalize() returns. So two values a document tells apart have equal resource
                // forms, the save finds a clean store and writes nothing, and the edit is lost
                // without an error.
                //
                // An equal resource form is therefore confirmed against the normalized form, the
                // representation the document engine stores. A definition that cannot answer there
                // leaves the resource form's verdict standing, which is what every release before
                // this one went on.
                $sameValue = !$differ && ($this->normalizedFormsAgree($dataDefinition, $oldValue, $value)
                    ?? !$undecided);

                if (!$sameValue) {
                    $this->markFieldDirty('_self');
                }
            } else {
                // no object to resolve the value against, or a definition that has no resource
                // form at all to compare in: nothing here can tell whether the value changed, and
                // only a store that is marked dirty is one an engine writes. Being wrong this way
                // costs the engine's own comparison - the document engine reads the stored
                // documents and writes the groups that differ, which is none of them - where
                // being wrong the other way loses the value.
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
            $this->attachOwner($value, $language);
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
     * Whether two values of a key normalize to the same thing, or `null` when nothing here can
     * answer that.
     *
     * The normalized form is the one a document holds - {@see Classificationstore\Engine\DocumentEngine}
     * stores what `normalize()` returns - so it is the representation that decides whether a write
     * would change anything, and it is lossless for the value in a way a resource form built for
     * two database columns need not be.
     *
     * The two are compared as JSON because that is how the document holds them, and because it is
     * the one comparison a value that refers back to this store cannot take down: a value handed out
     * by this store carries it as its owner ({@see self::attachOwner()}), and an owner is a way out
     * of the value and into the whole object graph. `json_encode()` reports such a cycle as an
     * error, where `serialize()` would walk it and `==` would exhaust the nesting limit. A form JSON
     * cannot carry is no answer, the same as no normalizer at all.
     *
     * Only an Exception is read as "cannot tell" - that is what a form JSON cannot carry arrives as.
     * A PHP Error is a fault in this code rather than evidence about a value, so it propagates, the
     * same way {@see Classificationstore\Engine\DocumentEngine::encodesTheSameValue()} lets one
     * through.
     */
    private function normalizedFormsAgree(
        Model\DataObject\ClassDefinition\Data $dataDefinition,
        mixed $oldValue,
        mixed $value
    ): ?bool {
        if (!$dataDefinition instanceof NormalizerInterface) {
            return null;
        }

        $params = ['object' => $this->object, 'fieldDefinition' => $dataDefinition];

        try {
            return json_encode($dataDefinition->normalize($oldValue, $params), JSON_THROW_ON_ERROR)
                === json_encode($dataDefinition->normalize($value, $params), JSON_THROW_ON_ERROR);
        } catch (Exception) {
            return null;
        }
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
                $data = $this->items[$groupId][$keyId][$l];
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
            $data = $this->items[$groupId][$keyId][$language];
        }

        // check for fallback value
        if ($fieldDefinition->isEmpty($data) && !$ignoreFallbackLanguage && self::doGetFallbackValues()) {
            $data = $this->getFallbackValue($groupId, $keyId, $language, $fieldDefinition);
        }

        if ($fieldDefinition->isEmpty($data) && !$ignoreDefaultLanguage && $language != 'default') {
            $data = $this->items[$groupId][$keyId]['default'] ?? null;
        }

        // check for inherited value
        //
        // A key whose field type does not support inheritance does not read its ancestor's value,
        // the same way DataObject\Localizedfield::getLocalizedValue() consults
        // `$fieldDefinition->supportsInheritance()` before walking up, and DataObject\Concrete\Dao
        // does for a field of the object itself. The question is the key's, not the field's: a
        // store holds keys of many types, and `consent` is the one in the set this release unlocks
        // that answers no - see {@see self::inheritableItems()} for why it does.
        $doGetInheritedValues = Model\DataObject::doGetInheritedValues();
        if ($fieldDefinition->isEmpty($data) && $doGetInheritedValues && $fieldDefinition->supportsInheritance()) {
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
