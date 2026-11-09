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
use Pimcore\Model\DataObject\ClassDefinition\Data\MultiValue as MultiValueKey;
use Pimcore\Model\DataObject\ClassDefinition\Data\PreGetDataInterface;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineFeature;
use Pimcore\Model\DataObject\Classificationstore\RelationDescriptor;
use Pimcore\Model\DataObject\Classificationstore\RelationEditModeResolver;
use Pimcore\Model\DataObject\Classificationstore\RelationRequest;
use Pimcore\Model\DataObject\Classificationstore\RelationResolver;
use Pimcore\Model\DataObject\Classificationstore\RenderedRelation;
use Pimcore\Model\DataObject\Data\MultiValue;
use Pimcore\Model\DataObject\Exception\UnsupportedEngineFeatureException;
use Pimcore\Model\Element\DirtyIndicatorInterface;
use Pimcore\Normalizer\NormalizerInterface;
use Pimcore\Model\Exception\InvalidConfigException;
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
     * How many levels of the items are a map: a value is addressed by group, key and language, and
     * what sits under the third of them is the value itself. {@see self::mergeArrays()}
     *
     * @internal
     */
    private const ITEM_LEVELS = 3;

    /**
     * The variant an absent qualifier resolves to, which every key is addressable under whatever it
     * declares - the declared variants are additional slots beside it, not a choice among them.
     */
    private const DEFAULT_VARIANT = 'default';

    /**
     * @internal
     *
     */
    protected array $items = [];

    /**
     * The values of every slot other than (default variant, position 0), which is the one $items
     * holds. That one value is never kept here as well, so the two cannot disagree about it.
     *
     * @internal
     *
     * @var array structure: [groupId][keyId][language][variant][position] = value
     */
    protected array $variantItems = [];

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

    /**
     * Counts the changes made to the items, so that an answer read for them is not reused once they
     * are no longer the items it was read for.
     */
    private int $itemsRevision = 0;

    /**
     * The targets of the stored descriptors this store holds that still exist, keyed
     * "<type>:<id>", and the items revision they were read for. Null outside a walk that may hold
     * them, {@see self::getExistingTargets()}.
     *
     * @var array<string, true>|null
     */
    private ?array $existingTargets = null;

    private int $existingTargetsRevision = -1;

    /**
     * The target-existence walk they were read in, which they may not outlive.
     *
     * {@see Classificationstore\RelationResolver::inTargetExistenceWalk()}
     */
    private ?int $existingTargetsWalk = null;

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
        $this->itemsRevision++;
        $this->bindAssignedItems();
        $this->markFieldDirty('_self');
    }

    public function setItems(array $items): static
    {
        $this->items = $items;
        // the qualified slots belong to the values being replaced, so they go with them rather than
        // being left behind for keys the new items no longer mention
        $this->variantItems = [];
        $this->itemsRevision++;
        $this->bindAssignedItems();
        $this->markFieldDirty('_self');
        $this->attachOwnersInTree($items);

        return $this;
    }

    /**
     * Makes every value of an assigned tree report a change back to this store,
     * {@see self::attachOwner()}.
     *
     * The language is the third level of both trees the store holds - group, key, language - and
     * what sits under it is the value itself in the items and the variant map in the qualified
     * slots. Either is handed over as it stands, because the walk descends into arrays anyway.
     *
     * @param array<array-key, mixed> $tree as the setter was given it, so every level is checked
     *                                      rather than assumed
     */
    private function attachOwnersInTree(array $tree): void
    {
        foreach ($tree as $group) {
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

    /**
     * Binds the advanced relation items of an assigned items tree to this store, so that an
     * in-place column edit reaches its dirty map whichever way the value got here.
     *
     * The tree a store is handed may carry items bound to something else entirely: the one
     * {@see ClassDefinition\Data\Classificationstore::denormalize()} builds has them bound to the
     * Concrete object the denormalizer was given, and the one
     * {@see Model\DataObject\Service::restoreClassificationstoreLanguages()} moves over has them
     * bound to the store they came from. Both would discard a column edit with no error,
     * {@see Classificationstore\RelationResolver::bindItemsToOwner()}.
     *
     * Both trees are bound, the unqualified items and the qualified slots: the two are assigned by
     * the same callers and an item of either is as readily edited in place, so binding one of them
     * would make what an in-place edit reaches depend on which slot its value sits in.
     */
    private function bindAssignedItems(): void
    {
        $this->getRelationResolver()->bindItemsToOwner($this->items, $this);
        $this->getRelationResolver()->bindItemsToOwner($this->variantItems, $this);
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

                return $this->mergeArrays($fieldsArray, $items, self::ITEM_LEVELS);
            }
        );
    }

    /**
     * The items of an ancestor's store this store may read as inherited values: the ones whose key
     * is defined with a field type that supports inheritance.
     *
     * The question is asked of the KEY's definition rather than of the field's, because one store
     * holds keys of many types at once, and it is asked through
     * {@see Model\DataObject\Classificationstore\Service::supportsInheritance()} so that this path
     * and the other two give the same answer - including for an `encryptedField`, which is answered
     * by its delegate. `consent` is the type in the set this release unlocks that answers no
     * ({@see Model\DataObject\ClassDefinition\Data\Consent::supportsInheritance()}): a consent is
     * the record of what one data subject agreed to, so a child object reading its parent's grant -
     * and the id of the audit note behind it - would answer "has this one consented" with somebody
     * else's answer. An `input` key beside it inherits as it always has.
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

        return Model\DataObject\Classificationstore\Service::supportsInheritance(
            Model\DataObject\Classificationstore\Service::getFieldDefinitionFromKeyConfig($keyConfig)
        );
    }

    /**
     * The object's own qualified slots, which {@see self::setItems()} drops along with the values
     * they belong to - a caller that rebuilds the items has to carry these across itself.
     *
     * @internal
     *
     * @return array structure: [groupId][keyId][language][variant][position] = value
     */
    public function getVariantItems(): array
    {
        return $this->variantItems;
    }

    /**
     * @internal
     *
     * @param array $variantItems structure: [groupId][keyId][language][variant][position] = value
     *
     * @return $this
     */
    public function setVariantItems(array $variantItems): static
    {
        $this->variantItems = $variantItems;
        $this->itemsRevision++;
        // the same binding the unqualified items get: the tree may carry advanced relation items
        // bound to the store they came from, whose in-place column edit would otherwise mark that
        // store and be skipped by the save of this one
        $this->bindAssignedItems();
        $this->markFieldDirty('_self');
        $this->attachOwnersInTree($variantItems);

        return $this;
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

        $keyConfig = Model\DataObject\Classificationstore\DefinitionCache::get($keyId);
        $dataDefinition = Model\DataObject\Classificationstore\Service::getFieldDefinitionFromKeyConfig($keyConfig);
        // a key of a wrapper type leaves the value itself to its inner type, which is what the
        // decisions below are about: how it reaches the resource, and which of its values count as
        // values. The wrapper answers neither, so a value of a wrapped key would be compared as
        // nothing and never mark the store dirty - a save would then write none of it
        $valueDefinition = $this->getValueDefinition($dataDefinition);

        // before anything decides whether the value changed, because a supplied password that
        // reaches storage as it was typed is a cleartext password whatever the comparison said
        $value = $this->convertPassword($value, $valueDefinition);

        // set the given group to active groups
        $this->setActiveGroups($this->activeGroups + [$groupId => true]);

        $isRelation = $this->getRelationResolver()->isRelation($valueDefinition);

        if ($isRelation) {
            // the items of an advanced relation are bound to the store that holds them, which is
            // what makes an in-place column edit reach the next save. A value assigned here has to
            // be bound the way a hydrated one is, or editing a column of an item that was assigned
            // rather than read would be discarded with no error,
            // {@see Classificationstore\RelationResolver::bindToOwner()}
            $this->getRelationResolver()->bindToOwner($value, $this);
        }

        // no ResourcePersistenceAwareInterface, so the comparison below cannot judge it
        if ($isRelation && !$this->isFieldDirty('_self')) {
            $this->markFieldDirty('_self');
        }

        if (!$this->isFieldDirty('_self')) {
            if ($this->object
                && $valueDefinition instanceof Model\DataObject\ClassDefinition\Data\ResourcePersistenceAwareInterface
            ) {
                $oldValue = $this->items[$groupId][$keyId][$language] ?? null;
                $oldData = $valueDefinition->getDataForResource($oldValue, $this->object, ['owner' => $this]);
                $newData = $valueDefinition->getDataForResource($value, $this->object, ['owner' => $this]);

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

                if ($valueDefinition instanceof Model\DataObject\ClassDefinition\Data\Password) {
                    // the value is already the hash, converted above, and asking for its resource
                    // form again hands the same string back: two hashes that differ are two
                    // passwords
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
                $sameValue = !$differ && ($this->normalizedFormsAgree($valueDefinition, $oldValue, $value)
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

        $this->itemsRevision++;

        if ($this->holdsAValue($value, $valueDefinition)) {
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
     * The values one (language, variant) slot of a key holds, in their order.
     *
     * A null $language means the default language, the way {@see self::getLanguage()} resolves it,
     * and a null $variant means the default variant. The list is keyed by the position of each of
     * its values, so a slot that holds no value at a position is read as holding none there rather
     * than as the values behind it moving up.
     *
     * What the list carries is what the current declaration of the key permits: a variant the key
     * no longer declares reads as nothing, and a key that no longer declares a list answers its
     * first value - neither read removes what is stored.
     *
     * The slot is addressed exactly, with no language fallback: a value stored under another
     * language, or under the language-independent default, is not answered here even where
     * {@see self::getLocalizedKeyValue()} would resolve it. That getter is the resolving reader of
     * position 0 of the default variant and stays the one to use for a resolved value; this one
     * answers what the slot itself holds, which is what a save stores for it - a read that resolved
     * a fallback would write an ancestor's value into the slot it was read for.
     *
     * The LANGUAGE is what the two differ in, and nothing else: a relation-typed value is answered
     * as its elements here as it is there, and the pre-read hook of the key's type is run on it -
     * the unpublished targets of a relation are left out of this read the way they are left out of
     * that one. A reader handed the {@see RelationDescriptor} the document was loaded from instead
     * would get a different kind of answer depending on whether the key had been read through the
     * other getter first, which memoizes the elements it resolves.
     *
     * @return array<int, mixed>
     *
     * @throws UnsupportedEngineFeatureException when the engine behind the store cannot address
     *                                          the given variant
     * @throws Exception
     */
    public function getKeyValues(
        int $groupId,
        int $keyId,
        ?string $language = null,
        ?string $variant = null
    ): array {
        $language = $this->getLanguage($language);
        $variant = $this->getVariant($variant);

        $this->assertTheEngineCanAddress($groupId, $keyId, $variant, 0);

        $declaration = $this->getQualifierDeclaration($keyId);
        if (!$this->declaresVariant($declaration, $variant)) {
            return [];
        }

        $values = $this->getQualifiedItems()[$groupId][$keyId][$language][$variant] ?? [];

        if ($declaration === null || !$declaration->isMultiValue()) {
            // the one position such a key permits, addressed by its position key rather than by
            // being first in the list: a slot a narrowed declaration left holding only a higher
            // position holds nothing here, instead of that value moving up into position 0
            $values = array_key_exists(0, $values) ? [0 => $values[0]] : [];
        }

        foreach ($values as $position => $value) {
            $values[$position] = $this->readQualifiedValue(
                $groupId,
                $keyId,
                $language,
                $variant,
                (int) $position,
                $value
            );
        }

        return $values;
    }

    /**
     * One value of a qualified slot as a reader gets it: a stored descriptor resolved into its
     * elements, and the pre-read hook of the key's type run on whatever comes out.
     *
     * The elements take the descriptor's place where the slot is this object's own, so that reading
     * the same slot twice resolves once and a target that has gone marks the store for the rewrite -
     * the two things {@see self::readItem()} does for position 0 of the default variant, which is
     * where that value lives. A slot selected from an ancestor is resolved for the read alone: the
     * stored value is the ancestor's, and memoizing an answer about it here - or marking this store
     * dirty for it - would put a value this object does not hold into its next save.
     *
     * @throws Exception
     */
    private function readQualifiedValue(
        int $groupId,
        int $keyId,
        string $language,
        string $variant,
        int $position,
        mixed $value
    ): mixed {
        $fieldDefinition = $this->getKeyValueDefinition($keyId);

        if ($fieldDefinition === null) {
            return $value;
        }

        if ($variant === self::DEFAULT_VARIANT
            && $position === 0
            && array_key_exists($language, $this->items[$groupId][$keyId] ?? [])
        ) {
            // the value of that one slot is held in the items, so it is read the way the resolving
            // getter reads it - resolved once and memoized there
            $value = $this->readItem($groupId, $keyId, $language, $fieldDefinition);
        } elseif ($value instanceof RelationDescriptor
            && $this->getRelationResolver()->isRelation($fieldDefinition)
        ) {
            $resolved = $this->getRelationResolver()->resolve(
                $fieldDefinition,
                $value->getValue(),
                $this->getObject(),
                $this
            );

            $value = $resolved->getData();

            if ($this->ownsQualifiedSlotValue($groupId, $keyId, $language, $variant, $position)) {
                $this->variantItems[$groupId][$keyId][$language][$variant][$position] = $value;

                if ($resolved->hasDroppedTargets()) {
                    $this->markFieldDirty('_self');
                }
            }
        }

        if ($fieldDefinition instanceof PreGetDataInterface && !$value instanceof RelationDescriptor) {
            $value = $fieldDefinition->preGetData($this, [
                'data' => $value,
                'language' => $language,
                'name' => $groupId . '-' . $keyId,
            ]);
        }

        return $value;
    }

    /**
     * Whether the value of this exact qualified slot is this object's own rather than an ancestor's.
     *
     * Position 0 of the default variant is left out: that value lives in the items and is resolved
     * and memoized by {@see self::readItem()}, which the resolving getter reaches.
     */
    private function ownsQualifiedSlotValue(
        int $groupId,
        int $keyId,
        string $language,
        string $variant,
        int $position
    ): bool {
        return array_key_exists(
            $position,
            $this->variantItems[$groupId][$keyId][$language][$variant] ?? []
        );
    }

    /**
     * One value of a (language, variant) slot, or null when the slot holds no value at that
     * position - a position past the end of the list is an absent value, not an error.
     *
     * The slot is addressed exactly, the way {@see self::getKeyValues()} addresses it: there is no
     * language fallback here, so this is not a drop-in replacement for
     * {@see self::getLocalizedKeyValue()} on a key whose value is answered by one.
     *
     * @throws UnsupportedEngineFeatureException when the engine behind the store cannot address
     *                                          the given variant or position
     * @throws Exception
     */
    public function getKeyValue(
        int $groupId,
        int $keyId,
        ?string $language = null,
        ?string $variant = null,
        int $position = 0
    ): mixed {
        $this->assertTheEngineCanAddress($groupId, $keyId, $this->getVariant($variant), $position);

        return $this->getKeyValues($groupId, $keyId, $language, $variant)[$position] ?? null;
    }

    /**
     * Replaces the (language, variant) slots the given entries name, and leaves every other slot of
     * the key as it is: writing one variant does not touch the values of another.
     *
     * The entries of one slot form its list, in the order their positions give. An absent qualifier
     * is the default - the default language, the default variant, position 0 - so two entries whose
     * qualifiers resolve to one slot and one position are a conflict, and the whole write is refused
     * before anything is stored. So is a variant the key does not declare, and a position above 0 on
     * a key that declares no list; the declared maximum is not refused here but reported by
     * {@see \Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore::checkValidity()}.
     *
     * @param MultiValue[] $entries
     *
     * @return $this
     *
     * @throws InvalidConfigException when the entries do not fit the declaration of the key
     * @throws UnsupportedEngineFeatureException when the engine behind the store cannot store them
     * @throws Exception
     */
    public function setKeyValues(int $groupId, int $keyId, array $entries): static
    {
        if (!$groupId) {
            throw new Exception('groupId not valid');
        }

        if (!$keyId) {
            throw new Exception('keyId not valid');
        }

        $declaration = $this->getQualifierDeclaration($keyId);
        $slots = $this->resolveSlots($groupId, $keyId, $entries, $declaration);

        foreach ($slots as $slot) {
            $this->storeSlot(
                $groupId,
                $keyId,
                $slot['language'],
                $slot['variant'],
                $slot['values'],
                $declaration !== null && $declaration->isMultiValue()
            );
        }

        return $this;
    }

    /**
     * Puts one stored value on the model, as the qualifiers of the document it came from place it.
     *
     * Nothing is checked against the declaration of the key here: a stored value whose variant the
     * key no longer declares is kept and hidden by the read, so that narrowing a declaration does
     * not destroy values and widening it again brings them back.
     *
     * @internal
     *
     * @return $this
     *
     * @throws Exception
     */
    public function setQualifiedKeyValue(
        int $groupId,
        int $keyId,
        mixed $value,
        ?string $language = null,
        ?string $variant = null,
        int $position = 0
    ): static {
        $language = $this->getLanguage($language);
        $variant = $this->getVariant($variant);

        if ($variant === self::DEFAULT_VARIANT && $position === 0) {
            return $this->setLocalizedKeyValue($groupId, $keyId, $value, $language);
        }

        $valueDefinition = $this->getKeyValueDefinition($keyId);

        // every write converts the password it is given, whichever slot it addresses: the branch of
        // setLocalizedKeyValue() that used to be the only one doing it sits inside its change
        // detection, so a store already marked dirty - which setKeyValues() marks it before it gets
        // there - stored what the caller typed
        $value = $this->convertPassword($value, $valueDefinition);

        // the owner binding the unqualified slot gets, for the same reason: an advanced relation
        // item of a qualified slot is as readily edited in place, and an item left bound to the
        // object - or to the store it came from - marks a dirty flag no save of this store reads,
        // {@see Classificationstore\RelationResolver::bindToOwner()}
        if ($this->getRelationResolver()->isRelation($valueDefinition)) {
            $this->getRelationResolver()->bindToOwner($value, $this);
        }

        // the one emptiness rule, so that the same write means the same thing in every slot: a value
        // the key's own type does not count as one empties the slot here as it does on the path
        // above, rather than being stored as a value of its own
        if ($this->holdsAValue($value, $valueDefinition)) {
            $this->variantItems[$groupId][$keyId][$language][$variant][$position] = $value;
            $this->attachOwner($value, $language);
        } else {
            $this->forgetQualifiedSlotValue($groupId, $keyId, $language, $variant, $position);
        }

        $this->itemsRevision++;
        $this->setActiveGroups($this->activeGroups + [$groupId => true]);
        $this->markFieldDirty('_self');

        return $this;
    }

    /**
     * The hash of a supplied password, and every other value as it stands.
     *
     * {@see ClassDefinition\Data\Password::getDataForResource()} is what hashes one, and it is
     * idempotent: a value that already carries a hash is handed straight back, which is what lets
     * every write path run it - the hydration of a stored value included - without rehashing what is
     * stored. It is given this store as the `owner`, so that it does not call a setter of the object
     * for a field the object does not have.
     *
     * Applied independently of whether anything thinks the value changed. The conversion used to sit
     * inside the change detection of {@see self::setLocalizedKeyValue()}, which a dirty store skips
     * altogether, and a qualified slot never reached that branch at all: a supplied password then
     * reached storage as the caller typed it, because `Password::normalize()` leaves a value
     * unchanged and the document holds what `normalize()` returns.
     */
    private function convertPassword(mixed $value, ?Model\DataObject\ClassDefinition\Data $valueDefinition): mixed
    {
        if (!$valueDefinition instanceof Model\DataObject\ClassDefinition\Data\Password) {
            return $value;
        }

        return $valueDefinition->getDataForResource($value, $this->object, ['owner' => $this]);
    }

    /**
     * Refuses every qualified value the engine behind the store cannot hold.
     *
     * {@see self::setKeyValues()} asks the same question, but a store that has not learned its class
     * yet has no store configuration to resolve an engine from, so the write is accepted and nobody
     * asks again - the values would then be dropped by an engine that cannot address them instead of
     * being refused. The class is known by the time the store is saved, so the question is put once
     * more here, where the drop would otherwise happen.
     *
     * @internal
     *
     * @throws UnsupportedEngineFeatureException when the engine cannot address a value held
     * @throws Exception
     */
    public function assertTheEngineCanHoldItsValues(): void
    {
        foreach ($this->variantItems as $groupId => $keys) {
            foreach ($keys as $keyId => $languages) {
                foreach ($languages as $variants) {
                    $this->assertTheEngineCanAddressEverySlot((int) $groupId, (int) $keyId, $variants);
                }
            }
        }
    }

    /**
     * @param array<string, array<int, mixed>> $variants structure: [variant][position] = value
     *
     * @throws UnsupportedEngineFeatureException
     * @throws Exception
     */
    private function assertTheEngineCanAddressEverySlot(int $groupId, int $keyId, array $variants): void
    {
        foreach ($variants as $variant => $values) {
            if ($values === []) {
                continue;
            }

            // the highest position the slot holds, so that a single value sitting at a position of
            // its own needs the same capability a list does
            $this->assertTheEngineCanAddress($groupId, $keyId, (string) $variant, max(array_keys($values)));
        }
    }

    /**
     * Whether a value is one the store holds rather than the absence of one.
     *
     * It is the rule {@see self::setLocalizedKeyValue()} has always applied to the one slot it
     * writes - "0" and an empty array are values, null and the empty string are not - and every
     * other slot shares it, so that clearing a value does not depend on which slot it sits in.
     */
    private function holdsAValue(mixed $value, ?Model\DataObject\ClassDefinition\Data $valueDefinition): bool
    {
        // Workaround for booleanSelect
        // @TODO Find a better solution for using isEmpty() in all ClassDefintion DataTypes
        if ($valueDefinition instanceof Model\DataObject\ClassDefinition\Data\BooleanSelect) {
            return true;
        }

        if ($valueDefinition instanceof Model\DataObject\ClassDefinition\Data\Multiselect
            && is_array($value) && $value === []
        ) {
            return true;
        }

        // treat value "0" nonempty
        $nonEmpty = is_string($value) ? strlen($value) > 0 : isset($value);

        return $nonEmpty || (bool) $value;
    }

    /**
     * Removes one value of a qualified slot, and the levels it was the last value of - the slot then
     * holds nothing at that position rather than holding the absence of a value there.
     */
    private function forgetQualifiedSlotValue(
        int $groupId,
        int $keyId,
        string $language,
        string $variant,
        int $position
    ): void {
        unset($this->variantItems[$groupId][$keyId][$language][$variant][$position]);

        if (($this->variantItems[$groupId][$keyId][$language][$variant] ?? null) === []) {
            unset($this->variantItems[$groupId][$keyId][$language][$variant]);
        }

        if (($this->variantItems[$groupId][$keyId][$language] ?? null) === []) {
            unset($this->variantItems[$groupId][$keyId][$language]);
        }

        if (($this->variantItems[$groupId][$keyId] ?? null) === []) {
            unset($this->variantItems[$groupId][$keyId]);
        }

        if (($this->variantItems[$groupId] ?? null) === []) {
            unset($this->variantItems[$groupId]);
        }
    }

    /**
     * Every stored value with the qualifiers that place it, which is what an engine writes a
     * document from and what {@see self::getKeyValues()} answers from.
     *
     * The lists are keyed by the position of their values and ordered by it, and the inheritance of
     * a (language, variant) slot is all-or-nothing: an object either holds that slot's own list or
     * takes the nearest ancestor's whole list, and the two are never merged into one.
     *
     * Position 0 of the default variant is the exception, and deliberately so: it is the one value
     * {@see self::getItems()} answers, so it is taken from there rather than from the slot merge.
     * That getter merges a value with its ancestors' element by element where the value is an array
     * - a multiselect of a child keeps the parent's further entries - and it is what every engine
     * wrote before a value could carry qualifiers, so changing it here would change what a save
     * stores for a key that declares none.
     *
     * A slot whose SELECTED list starts above position 0 is left out of that exception: the value
     * {@see self::getItems()} answers there belongs to a further ancestor than the one the list came
     * from, and writing it in would merge two objects' lists into one. The question is asked of the
     * store the merge actually selected the slot from rather than of this one - with a grandparent
     * holding [0 => 'a', 1 => 'b'], a parent holding [1 => 'x'] and nothing of its own, a child
     * takes the parent's whole list and must answer [1 => 'x']; asked only whether IT owns the slot,
     * it would answer [0 => 'a', 1 => 'x'], which is two objects' values in one list.
     *
     * @internal
     *
     * @return array structure: [groupId][keyId][language][variant][position] = value
     */
    public function getQualifiedItems(): array
    {
        if (!Model\DataObject::doGetInheritedValues()) {
            return $this->getOwnQualifiedItems();
        }

        /** @var array<int|string, array<int|string, array<string, bool>>> $startsAboveZero */
        $startsAboveZero = [];

        $qualified = $this->getAllDataFromField(
            function ($classificationStore, $fieldsArray) use (&$startsAboveZero) {
                $own = $classificationStore->getOwnQualifiedItems();

                foreach ($own as $groupId => $keys) {
                    foreach ($keys as $keyId => $languages) {
                        foreach ($languages as $language => $variants) {
                            // the merge keeps the nearest store's list for a slot, so the nearest
                            // store that holds the slot at all is the one whose answer counts: the
                            // walk runs nearest first, and a verdict already recorded stands
                            if (!isset($variants[self::DEFAULT_VARIANT])
                                || isset($startsAboveZero[$groupId][$keyId][$language])
                            ) {
                                continue;
                            }

                            $startsAboveZero[$groupId][$keyId][$language] = $classificationStore
                                ->ownsSlotWithoutItsFirstValue((int) $groupId, (int) $keyId, (string) $language);
                        }
                    }
                }

                return $this->mergeQualifiedSlots($fieldsArray, $own);
            }
        );

        foreach ($this->getItems() as $groupId => $keys) {
            foreach ($keys as $keyId => $languages) {
                foreach ($languages as $language => $value) {
                    if ($startsAboveZero[$groupId][$keyId][$language] ?? false) {
                        continue;
                    }

                    $qualified[$groupId][$keyId][$language][self::DEFAULT_VARIANT][0] = $value;
                    ksort($qualified[$groupId][$keyId][$language][self::DEFAULT_VARIANT]);
                }
            }
        }

        return $qualified;
    }

    /**
     * Whether this object holds the default-variant slot of a key in a language without holding its
     * first value - the list it owns starts above position 0, so position 0 of that slot is nobody's
     * but an ancestor's to answer.
     */
    private function ownsSlotWithoutItsFirstValue(int $groupId, int $keyId, string $language): bool
    {
        return isset($this->variantItems[$groupId][$keyId][$language][self::DEFAULT_VARIANT])
            && !array_key_exists($language, $this->items[$groupId][$keyId] ?? []);
    }

    /**
     * The variant a qualifier addresses, with the default variant for an absent one - the way
     * {@see self::getLanguage()} resolves an absent language.
     */
    private function getVariant(?string $variant = null): string
    {
        if ($variant !== null && $variant !== '') {
            return $variant;
        }

        return self::DEFAULT_VARIANT;
    }

    /**
     * The entries grouped into the slots they address, with every refusal raised before the first
     * of them is stored.
     *
     * @param MultiValue[] $entries
     * @param MultiValueKey|null $declaration the qualifier declaration of the key, resolved once by
     *                                        the caller because what the write may replace is read
     *                                        off the same declaration
     *
     * @return array<string, array{language: string, variant: string, values: array<int, mixed>}>
     *
     * @throws InvalidConfigException
     * @throws UnsupportedEngineFeatureException
     * @throws Exception
     */
    private function resolveSlots(
        int $groupId,
        int $keyId,
        array $entries,
        ?MultiValueKey $declaration
    ): array {
        $slots = [];

        foreach ($entries as $entry) {
            $language = $this->getLanguage($entry->getLanguage());
            $variant = $this->getVariant($entry->getVariant());
            $position = $entry->getPosition() ?? 0;

            if (!$this->declaresVariant($declaration, $variant)) {
                throw new InvalidConfigException(sprintf(
                    'Classification store key %d does not declare the variant "%s".',
                    $keyId,
                    $variant
                ));
            }

            if ($position > 0 && ($declaration === null || !$declaration->isMultiValue())) {
                throw new InvalidConfigException(sprintf(
                    'Classification store key %d does not declare a list of values,'
                    . ' so it holds no position %d.',
                    $keyId,
                    $position
                ));
            }

            $slotKey = $language . "\0" . $variant;
            $slot = $slots[$slotKey] ?? ['language' => $language, 'variant' => $variant, 'values' => []];

            if (array_key_exists($position, $slot['values'])) {
                throw new InvalidConfigException(sprintf(
                    'Two values of classification store key %d address the same slot'
                    . ' (language "%s", variant "%s", position %d).',
                    $keyId,
                    $language,
                    $variant,
                    $position
                ));
            }

            $slot['values'][$position] = $entry->getValue();
            $slots[$slotKey] = $slot;
        }

        foreach ($slots as $slot) {
            // the highest position the slot addresses, so that a single value written at a position
            // of its own needs the same capability as a list does
            $this->assertTheEngineCanAddress($groupId, $keyId, $slot['variant'], max(array_keys($slot['values'])));
        }

        return $slots;
    }

    /**
     * One (language, variant) slot, replaced by the values the write gives it.
     *
     * What the write may replace is what the declaration lets it address. While the key declares a
     * list, the entries ARE the list and the whole slot is replaced, so a write that drops a value
     * drops it. While it declares none, the write can address position 0 alone -
     * {@see self::resolveSlots()} refuses anything above it - and the positions a narrowed
     * declaration hides are not the write's to remove: narrowing HIDES values and deletes none, so
     * widening the declaration again has to bring them back. Clearing the slot here would delete
     * them on the next save instead, and nothing about the write said so.
     *
     * @param array<int, mixed> $values
     * @param bool $replaceWholeSlot whether the declaration permits a list, which is what makes the
     *                               entries the whole list rather than its first value
     *
     * @throws Exception
     */
    private function storeSlot(
        int $groupId,
        int $keyId,
        string $language,
        string $variant,
        array $values,
        bool $replaceWholeSlot
    ): void {
        ksort($values);

        // the slot is replaced rather than added to, so a write that drops a value changes the
        // store even where nothing new lands in it - and a save compares documents anyway, so a
        // write that changed nothing still writes no row
        $this->markFieldDirty('_self');

        if ($replaceWholeSlot) {
            unset($this->variantItems[$groupId][$keyId][$language][$variant]);
        } else {
            $this->forgetQualifiedSlotValue($groupId, $keyId, $language, $variant, 0);
        }

        foreach ($values as $position => $value) {
            $this->setQualifiedKeyValue($groupId, $keyId, $value, $language, $variant, (int) $position);
        }

        if ($variant === self::DEFAULT_VARIANT && !array_key_exists(0, $values)) {
            // $items holds position 0 of the default variant, so a list that no longer carries one
            // has to clear it rather than leave the value the slot used to start with behind
            $this->setLocalizedKeyValue($groupId, $keyId, null, $language);
        }
    }

    /**
     * The qualifier declaration of a key, or null for a key that carries none - which is every key
     * of a type other than `multiValue`, and a key whose configuration is gone.
     */
    private function getQualifierDeclaration(int $keyId): ?MultiValueKey
    {
        $keyConfig = Model\DataObject\Classificationstore\DefinitionCache::get($keyId);
        if (!$keyConfig) {
            return null;
        }

        $definition = Model\DataObject\Classificationstore\Service::getFieldDefinitionFromKeyConfig($keyConfig);

        return $definition instanceof MultiValueKey ? $definition : null;
    }

    /**
     * The definition of the value a key holds, resolved from the key's configuration - null for a
     * key whose configuration is gone, which the load path of an engine reaches for a value whose
     * key was deleted.
     */
    private function getKeyValueDefinition(int $keyId): ?Model\DataObject\ClassDefinition\Data
    {
        $keyConfig = Model\DataObject\Classificationstore\DefinitionCache::get($keyId);
        if (!$keyConfig) {
            return null;
        }

        return $this->getValueDefinition(
            Model\DataObject\Classificationstore\Service::getFieldDefinitionFromKeyConfig($keyConfig)
        );
    }

    /**
     * The definition of the value a key holds: the inner type's for a key of a wrapper type, as it
     * is the inner value that is stored and read back.
     *
     * Resolved through {@see Model\DataObject\Classificationstore\Service::getValueDefinition()},
     * the one boundary that answers this - the engines and this store would otherwise each have
     * their own answer, and a key whose inner type one of them did not see is a value that reaches
     * storage unrefused.
     */
    private function getValueDefinition(
        ?Model\DataObject\ClassDefinition\Data $definition
    ): ?Model\DataObject\ClassDefinition\Data {
        return Model\DataObject\Classificationstore\Service::getValueDefinition($definition);
    }

    private function declaresVariant(?MultiValueKey $declaration, string $variant): bool
    {
        if ($variant === self::DEFAULT_VARIANT) {
            return true;
        }

        return $declaration !== null && in_array($variant, $declaration->getVariantNames(), true);
    }

    /**
     * @throws UnsupportedEngineFeatureException
     * @throws Exception
     */
    private function assertTheEngineCanAddress(int $groupId, int $keyId, string $variant, int $position): void
    {
        if ($variant !== self::DEFAULT_VARIANT) {
            $this->assertTheEngineSupports(EngineFeature::Variants, $groupId, $keyId);
        }

        if ($position !== 0) {
            $this->assertTheEngineSupports(EngineFeature::MultiValue, $groupId, $keyId);
        }
    }

    /**
     * @throws UnsupportedEngineFeatureException
     * @throws Exception
     */
    private function assertTheEngineSupports(EngineFeature $feature, int $groupId, int $keyId): void
    {
        // a store that carries neither a class nor an object has no store configuration to resolve
        // an engine from, so there is no storage for the capability to be a property of yet
        if (!$this->getClass()) {
            return;
        }

        if (!$this->getDao()->supports($feature)) {
            throw new UnsupportedEngineFeatureException(
                $feature,
                $groupId,
                $keyId,
                $this->getKeyValueDefinition($keyId)?->getFieldtype() ?? 'unknown'
            );
        }
    }

    /**
     * The object's own stored values, with the qualifiers that place them. $items answers position 0
     * of the default variant of every key it holds and $variantItems the rest, so the one value the
     * two could describe differently is only ever read from $items.
     *
     * @return array structure: [groupId][keyId][language][variant][position] = value
     */
    private function getOwnQualifiedItems(): array
    {
        $qualified = [];

        foreach ($this->items as $groupId => $keys) {
            foreach ($keys as $keyId => $languages) {
                foreach ($languages as $language => $value) {
                    $qualified[$groupId][$keyId][$language][self::DEFAULT_VARIANT] = [0 => $value];
                }
            }
        }

        foreach ($this->variantItems as $groupId => $keys) {
            foreach ($keys as $keyId => $languages) {
                foreach ($languages as $language => $variants) {
                    foreach ($variants as $variant => $values) {
                        if ($variant === self::DEFAULT_VARIANT
                            && array_key_exists($language, $this->items[$groupId][$keyId] ?? [])
                        ) {
                            $values[0] = $this->items[$groupId][$keyId][$language];
                        }

                        ksort($values);
                        // the position a value holds is its key and stays its key: compacting the
                        // list would renumber every value behind a position the slot does not hold
                        $qualified[$groupId][$keyId][$language][$variant] = $values;
                    }
                }
            }
        }

        return $qualified;
    }

    /**
     * The qualified values of a child merged over an ancestor's, with a (language, variant) slot as
     * the unit: a slot the child holds is answered by the child's whole list.
     *
     * @param array $child structure: [groupId][keyId][language][variant][position] = value
     * @param array $parent structure: [groupId][keyId][language][variant][position] = value
     *
     * @return array structure: [groupId][keyId][language][variant][position] = value
     */
    private function mergeQualifiedSlots(array $child, array $parent): array
    {
        foreach ($child as $groupId => $keys) {
            foreach ($keys as $keyId => $languages) {
                foreach ($languages as $language => $variants) {
                    foreach ($variants as $variant => $values) {
                        $parent[$groupId][$keyId][$language][$variant] = $values;
                    }
                }
            }
        }

        return $parent;
    }

    /**
     * Removes the group with the given id
     *
     */
    public function removeGroupData(int $groupId): void
    {
        unset($this->items[$groupId], $this->variantItems[$groupId]);
        $this->itemsRevision++;
    }

    /**
     * The groups this object holds a value for, in either tree: a group whose values sit only in a
     * named variant, or only above position 0, holds data as surely as one whose value is in the
     * items. Its only caller is the cleanup of the editmode save, which destroys the data of a
     * group the payload no longer declares active - read off the items alone, such a group was
     * never offered to it, and its values outlived the deactivation.
     *
     * @return array<int, int|string>
     */
    public function getGroupIdsWithData(): array
    {
        return array_keys($this->items + $this->variantItems);
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

    private function getFallbackValue(
        int $groupId,
        int $keyId,
        string $language,
        ClassDefinition\Data $fielddefinition,
        bool $resolveRelations = true
    ): mixed {
        $fallbackLanguages = Tool::getFallbackLanguagesFor($language);
        $data = null;

        foreach ($fallbackLanguages as $l) {
            if (
                array_key_exists($groupId, $this->items)
                && array_key_exists($keyId, $this->items[$groupId])
                && array_key_exists($l, $this->items[$groupId][$keyId])
            ) {
                $data = $this->readItem($groupId, $keyId, $l, $fielddefinition, $resolveRelations);
                if (!$this->isEmptyValue($fielddefinition, $data)) {
                    return $data;
                }
            }
        }

        foreach ($fallbackLanguages as $l) {
            $data = $this->getFallbackValue($groupId, $keyId, $l, $fielddefinition, $resolveRelations);
        }

        return $data;
    }

    /**
     * The resolving reader of position 0 of the default variant: it falls back through the fallback
     * languages and then to the language-independent default, and takes an inherited value, which
     * {@see self::getKeyValues()} and {@see self::getKeyValue()} deliberately do not - they answer
     * the one slot they are given.
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
        return $this->selectKeyValue(
            $groupId,
            $keyId,
            $language,
            $ignoreFallbackLanguage,
            $ignoreDefaultLanguage,
            true
        );
    }

    /**
     * The same value {@see self::getLocalizedKeyValue()} selects, with a relation-typed one left as
     * the {@see RelationDescriptor} it is stored as instead of being resolved into its elements.
     *
     * For the callers that have something to do with the stored value rather than with the elements
     * it names - validation, {@see ClassDefinition\Data\Classificationstore::checkValidity()}. They
     * need the value the fallback language, the default language and the inheritance chain select,
     * which is why this goes through the same walk rather than reading
     * {@see self::getItems()}: a coordinate one store holds an empty value at and an ancestor holds
     * a value at is answered here the way a read answers it, and a merge of the items is not.
     *
     * No target is instantiated on the way, so a save of an object whose relation keys were never
     * read costs no element load. An unresolved descriptor counts as present exactly when it names
     * a target, {@see self::isEmptyValue()}, so that an empty one falls through to the fallback
     * language and the inheritance chain as a resolved empty value does.
     *
     * @internal
     *
     * @throws Exception
     */
    public function getStoredKeyValue(
        int $groupId,
        int $keyId,
        ?string $language = 'default',
        bool $ignoreFallbackLanguage = false,
        bool $ignoreDefaultLanguage = false
    ): mixed {
        return $this->selectKeyValue(
            $groupId,
            $keyId,
            $language,
            $ignoreFallbackLanguage,
            $ignoreDefaultLanguage,
            false
        );
    }

    /**
     * @param bool $resolveRelations whether a relation-typed value is resolved into its elements,
     *                               which is what the two callers above differ in
     *
     * @throws Exception
     */
    private function selectKeyValue(
        int $groupId,
        int $keyId,
        ?string $language,
        bool $ignoreFallbackLanguage,
        bool $ignoreDefaultLanguage,
        bool $resolveRelations
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
            $data = $this->readItem($groupId, $keyId, $language, $fieldDefinition, $resolveRelations);
        }

        // check for fallback value
        if ($this->isEmptyValue($fieldDefinition, $data) && !$ignoreFallbackLanguage && self::doGetFallbackValues()) {
            $data = $this->getFallbackValue($groupId, $keyId, $language, $fieldDefinition, $resolveRelations);
        }

        if ($this->isEmptyValue($fieldDefinition, $data) && !$ignoreDefaultLanguage && $language != 'default') {
            $data = $this->readItem($groupId, $keyId, 'default', $fieldDefinition, $resolveRelations);
        }

        // check for inherited value
        //
        // A key whose field type does not support inheritance does not read its ancestor's value,
        // the same way DataObject\Localizedfield::getLocalizedValue() consults
        // `$fieldDefinition->supportsInheritance()` before walking up, and DataObject\Concrete\Dao
        // does for a field of the object itself. The question is the key's, not the field's: a
        // store holds keys of many types, and `consent` is the one in the set this release unlocks
        // that answers no - see {@see self::inheritableItems()} for why it does, and
        // {@see Model\DataObject\Classificationstore\Service::supportsInheritance()} for why an
        // `encryptedField` is answered by its delegate rather than by the wrapper.
        $doGetInheritedValues = Model\DataObject::doGetInheritedValues();
        if ($this->isEmptyValue($fieldDefinition, $data)
            && $doGetInheritedValues
            && Model\DataObject\Classificationstore\Service::supportsInheritance($fieldDefinition)
        ) {
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
                                    // the ancestor is asked the same way it was asked itself, so
                                    // that a store overriding either entry point keeps answering
                                    // for the values it holds
                                    $data = $resolveRelations
                                        ? $classificationStore->getLocalizedKeyValue($groupId, $keyId, $language, false)
                                        : $classificationStore->getStoredKeyValue($groupId, $keyId, $language, false);
                                }
                            }
                        }
                    }
                }
            }
        }

        // an unresolved descriptor is not a value this hook can be given: for a store container it
        // hands the value straight back after filtering the unpublished elements out of it,
        // {@see ClassDefinition\Data\ManyToOneRelation::preGetData()}, and a descriptor holds no
        // element to filter. The value the caller gets is the stored one, which is what the
        // non-inheriting branch of checkValidity() has always read.
        if ($fieldDefinition instanceof PreGetDataInterface && !$data instanceof RelationDescriptor) {
            $data = $fieldDefinition->preGetData($this, [
                'data' => $data,
                'language' => $language,
                'name' => $groupId . '-' . $keyId,
            ]);
        }

        return $data;
    }

    /**
     * Whether this value is the key's empty one, which decides whether the fallback language, the
     * default language and the inheritance chain are consulted for it.
     *
     * An unresolved relation value is asked of its surviving descriptors rather than of the key:
     * every relation type reads emptiness off the value itself - `empty($data)`,
     * {@see ClassDefinition\Data::isEmpty()} - and a {@see RelationDescriptor} is an object, so a
     * key storing no relation at all would otherwise count as holding one.
     *
     * A descriptor whose targets have all been deleted is the key holding nothing, the same answer
     * the elements would have given: reading the key drops a dead target,
     * {@see self::readItem()}, so counting one as present here would make the value this selects -
     * and with it what a save refuses - depend on whether the key had been read. Deciding it costs
     * no element load and one round of lookups for the whole store,
     * {@see self::getExistingTargets()}.
     */
    private function isEmptyValue(ClassDefinition\Data $fieldDefinition, mixed $data): bool
    {
        if ($data instanceof RelationDescriptor) {
            return !$this->getRelationResolver()->hasSurvivingTarget(
                $data->getValue(),
                $this->getExistingTargets()
            );
        }

        return $fieldDefinition->isEmpty($data);
    }

    /**
     * The targets of every stored descriptor this store holds that still exist, keyed "<type>:<id>".
     *
     * Read for the whole store at once, in one chunked statement per element type: the paths that
     * ask whether a stored relation is empty ask for key after key and language after language -
     * the validity check of a store with fifty relation keys asks once per key and language - and a
     * lookup per question would be a statement per key where the store means to cost one round.
     * Nothing is instantiated for it either.
     *
     * The answer is held for the walk that asked for it and no longer,
     * {@see Classificationstore\RelationResolver::inTargetExistenceWalk()}. Deleting a target
     * changes nothing about this store, so neither the items nor their revision can tell that a
     * held answer has gone stale: a second validation of the same instance would still be told a
     * dead descriptor names a target, select it over the live value the default language or an
     * ancestor holds, and be refused by the summaries of that same validation, which are read
     * fresh. Outside a walk nothing is held at all - a caller asking one question at a time is
     * answered about the elements as they stand now.
     *
     * @return array<string, true>
     *
     * @throws Exception
     */
    private function getExistingTargets(): array
    {
        $resolver = $this->getRelationResolver();
        $walk = $resolver->currentTargetExistenceWalk();

        if (
            $walk !== null
            && $this->existingTargets !== null
            && $this->existingTargetsWalk === $walk
            && $this->existingTargetsRevision === $this->itemsRevision
        ) {
            return $this->existingTargets;
        }

        $descriptors = [];

        foreach ($this->items as $keys) {
            foreach ($keys as $languages) {
                foreach ($languages as $value) {
                    if ($value instanceof RelationDescriptor) {
                        foreach ($resolver->describe($value->getValue()) as $descriptor) {
                            $descriptors[] = $descriptor;
                        }
                    }
                }
            }
        }

        // a store holding no descriptor pays for no statement: the lookup is per element type of
        // the descriptors handed over, and there are none
        $existingTargets = $resolver->findExistingTargets($descriptors);

        $this->existingTargets = $walk === null ? null : $existingTargets;
        $this->existingTargetsRevision = $this->itemsRevision;
        $this->existingTargetsWalk = $walk;

        return $existingTargets;
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
     * A key that is not a relation any more keeps its stored descriptor: the definition in force
     * cannot read one - it is not a normalizer, so the resolution answers null,
     * {@see Classificationstore\RelationResolver::resolve()} - and memoizing that null would put it
     * where the stored value was and have the next ordinary save write it over the document. The
     * descriptor is handed back as it stands instead, which is what the callers that can be given
     * one expect: the validity check leaves such a key alone,
     * {@see ClassDefinition\Data\Classificationstore::checkValidity()}, and the hook of a
     * definition that cannot read it is not asked either, {@see self::selectKeyValue()}.
     *
     * @param bool $resolveRelations false for a caller that wants the stored descriptor itself,
     *                               {@see self::getStoredKeyValue()}; nothing is resolved and
     *                               nothing is memoized then
     *
     * @throws Exception
     */
    private function readItem(
        int $groupId,
        int $keyId,
        string $language,
        ?ClassDefinition\Data $fieldDefinition,
        bool $resolveRelations = true
    ): mixed {
        $data = $this->items[$groupId][$keyId][$language] ?? null;

        if ($fieldDefinition === null || !$resolveRelations || !$data instanceof RelationDescriptor) {
            return $data;
        }

        if (!$this->getRelationResolver()->isRelation($fieldDefinition)) {
            return $data;
        }

        $resolved = $this->getRelationResolver()->resolve(
            $fieldDefinition,
            $data->getValue(),
            $this->getObject(),
            $this
        );
        $this->items[$groupId][$keyId][$language] = $resolved->getData();

        if ($resolved->hasDroppedTargets()) {
            $this->markFieldDirty('_self');
        }

        return $resolved->getData();
    }

    /**
     * The edit mode value of every relation-typed value the store holds, against one set of lookups
     * and without instantiating a target.
     *
     * For the reader that takes the items as they are instead of going through
     * {@see self::getLocalizedKeyValue()} - the edit mode payload. It needs the columns of a target
     * rather than the target, {@see RelationEditModeResolver}, so what comes back is the rendered
     * value rather than the elements, and the cost of the whole store is one chunked statement per
     * element type instead of one element load per target.
     *
     * What that reader sees is merged across the inheritance chain, {@see self::getItems()}, so
     * every store on that chain renders its own descriptors - a value an ancestor contributes would
     * otherwise reach the reader as the descriptor the ancestor still holds. A coordinate a store
     * holds an already resolved value at contributes `null`, so that the ancestor's rendering does
     * not take the place of the value the nearer store actually holds.
     *
     * The whole chain is rendered here, against one round of lookups, and the answer carries one
     * merged map per store on it - the one that store's own reader needs, with the stores nearer to
     * the object left out of it. The reader walks the chain itself, one level per ancestor whose
     * values it is still missing, and asking each level for its own rendering would render the
     * ancestors of level two again at level two, those of level three again at level three: a chain
     * of H levels would cost H(H+1)/2 rounds of lookups to show one editor tab. So the reader asks
     * once, {@see ClassDefinition\Data\Classificationstore::getDataForEditmode()}, and takes the map
     * of the object each of its levels is at.
     *
     * The chain is walked whatever {@see Model\DataObject::doGetInheritedValues()} says, because the
     * reader walks it on the class allowing inheritance alone: with inherited values off, each level
     * shows its own items, and each level still has to have its descriptors rendered.
     *
     * @internal
     *
     * @return array<int, array<int, array<int, array<string, RenderedRelation|null>>>>
     *         objectId => groupId => keyId => language
     *
     * @throws Exception
     */
    public function renderRelationsForEditMode(): array
    {
        $chain = $this->collectInheritanceChain();
        $own = $this->renderChainForEditMode($chain);

        $byObject = [];
        $merged = [];

        // folded from the far end of the chain, so that every store's map holds its own rendering
        // over its ancestors' - the way the items it reads are merged, {@see self::mergeArrays()}
        for ($level = count($chain) - 1; $level >= 0; $level--) {
            $merged = $this->mergeArrays($own[$level], $merged, self::ITEM_LEVELS);
            $byObject[(int) $chain[$level]->getObject()?->getId()] = $merged;
        }

        return $byObject;
    }

    /**
     * The stores an edit mode reader walks from this one, nearest first.
     *
     * @return list<Classificationstore>
     */
    private function collectInheritanceChain(): array
    {
        $chain = [$this];
        $object = $this->getObject();

        while (!is_null($object) && ($parent = Service::hasInheritableParentObject($object)) !== null) {
            $store = $parent->{'get' . ucfirst($this->getFieldname())}();

            if (!$store instanceof self) {
                break;
            }

            $chain[] = $store;
            $object = $parent;
        }

        return $chain;
    }

    /**
     * The rendering of each store on the chain, its own descriptors only, against one round of
     * lookups for all of them together: the requests of every store go into one call, so a chain
     * costs what a single store costs.
     *
     * @param list<Classificationstore> $chain
     *
     * @return array<int, array<int, array<int, array<string, RenderedRelation|null>>>> by level
     *
     * @throws Exception
     */
    private function renderChainForEditMode(array $chain): array
    {
        $renderer = $this->getRelationEditModeResolver();
        $rendered = [];
        $renderable = [];

        foreach ($chain as $level => $store) {
            $rendered[$level] = $store->collectResolvedCoordinates();

            foreach ($store->collectRelationRequests() as $request) {
                if ($renderer->supports($request->getFieldDefinition())) {
                    $renderable[] = [$level, $request];

                    continue;
                }

                // a project relation type keeps the resolution it had: the elements go into the
                // items, and the reader renders them itself
                $store->resolveItem($request);
                $rendered[$level][$request->getGroupId()][$request->getKeyId()][$request->getLanguage()] = null;
            }
        }

        return $this->applyRenderedRelations($chain, $rendered, $renderable, $renderer);
    }

    /**
     * The coordinates of this store whose value is not a descriptor, mapped to nothing to render.
     *
     * They are what keeps the rendering an ancestor contributes for a coordinate from standing in
     * for the value this store holds there - a key read earlier in the request holds its elements
     * rather than its descriptor, and those are the value the payload has to show. Deciding this
     * needs no key config, so a key that holds no descriptor still pays for none.
     *
     * @return array<int, array<int, array<string, null>>>
     */
    private function collectResolvedCoordinates(): array
    {
        $coordinates = [];

        foreach ($this->items as $groupId => $keys) {
            foreach ($keys as $keyId => $languages) {
                foreach ($languages as $language => $value) {
                    if (!$value instanceof RelationDescriptor) {
                        $coordinates[(int) $groupId][(int) $keyId][(string) $language] = null;
                    }
                }
            }
        }

        return $coordinates;
    }

    /**
     * @param list<Classificationstore> $chain
     * @param array<int, array<int, array<int, array<string, RenderedRelation|null>>>> $rendered by level
     * @param list<array{0: int, 1: RelationRequest}> $renderable the level each request belongs to,
     *                                                           and the request
     *
     * @return array<int, array<int, array<int, array<string, RenderedRelation|null>>>> by level
     *
     * @throws Exception
     */
    private function applyRenderedRelations(
        array $chain,
        array $rendered,
        array $renderable,
        RelationEditModeResolver $renderer
    ): array {
        if ($renderable === []) {
            return $rendered;
        }

        $relations = $renderer->renderAll(array_column($renderable, 1));

        foreach ($renderable as $index => [$level, $request]) {
            if ($relations[$index]->hasDroppedTargets()) {
                // a descriptor whose target is gone has to come out of the stored value, and the
                // per-relation column values of an advanced key are addressed by the position of
                // the entry they belong to - so the key is resolved into its elements, which is
                // what the save rebuilds both the document and those rows from. Rewriting the
                // descriptor instead would reindex the document and leave the rows behind it.
                // Only a key that actually lost a target pays for that resolution.
                $chain[$level]->resolveItem($request);
                $rendered[$level][$request->getGroupId()][$request->getKeyId()][$request->getLanguage()] = null;

                continue;
            }

            $rendered[$level][$request->getGroupId()][$request->getKeyId()][$request->getLanguage()]
                = $relations[$index];
        }

        return $rendered;
    }

    /**
     * @throws Exception
     */
    private function resolveItem(RelationRequest $request): void
    {
        $resolved = $this->getRelationResolver()->resolve(
            $request->getFieldDefinition(),
            $request->getNormalizedValue(),
            $this->getObject(),
            $this
        );

        $this->items[$request->getGroupId()][$request->getKeyId()][$request->getLanguage()]
            = $resolved->getData();

        if ($resolved->hasDroppedTargets()) {
            $this->markFieldDirty('_self');
        }
    }

    /**
     * One request per stored descriptor this store holds.
     *
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

    private function getRelationEditModeResolver(): RelationEditModeResolver
    {
        return Pimcore::getContainer()->get(RelationEditModeResolver::class);
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

    /**
     * Merges the nearer store's map over an ancestor's, down to the given number of levels.
     *
     * A value is a leaf, never a map to merge into: the items are addressed by group, key and
     * language, and what sits under those three belongs to one store as a whole. A relation-typed
     * value makes that unmistakable - one store can hold its elements while an ancestor still holds
     * the descriptor it was loaded from, and merging a list of elements into a
     * {@see RelationDescriptor} would ask for the descriptor as an array. It is no less true of
     * every other value a key holds as an array: a child's multiselect is its own value, and the
     * entries an ancestor happens to have beyond it are not part of it.
     *
     * The level count is what keeps that rule at the value and nowhere above it, and the type of
     * both sides is checked as well, so a map one level shallower than it should be cannot make a
     * merge of two arrays out of a value and a map.
     */
    private function mergeArrays(array $a1, array $a2, int $levels = PHP_INT_MAX): array
    {
        foreach ($a1 as $key => $value) {
            if ($levels > 1 && is_array($value) && is_array($a2[$key] ?? null)) {
                $a2[$key] = $this->mergeArrays($value, $a2[$key], $levels - 1);
            } else {
                $a2[$key] = $value;
            }
        }

        return $a2;
    }
}
