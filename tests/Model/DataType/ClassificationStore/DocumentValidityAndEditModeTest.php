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

namespace Pimcore\Tests\Model\DataType\ClassificationStore;

use Pimcore;
use Pimcore\Loader\ImplementationLoader\ClassMapLoader;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\MultiValue;
use Pimcore\Model\DataObject\ClassDefinition\Data\Numeric;
use Pimcore\Model\DataObject\ClassDefinition\Data\QuantityValue;
use Pimcore\Model\DataObject\ClassDefinition\Data\Time;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Classificationstore\Engine\DocumentEngine;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineResolver;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Csstore;
use Pimcore\Model\DataObject\Csstorelocalized;
use Pimcore\Model\DataObject\Data\MultiValue as MultiValueEntry;
use Pimcore\Model\DataObject\QuantityValue\Unit;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Tests\Support\Util\TestHelper;
use ReflectionMethod;

/**
 * ValidityAndEditModeTest against a store on the document engine: validation and the edit mode data
 * resolve in PHP at read time, so they have to come out the same whichever engine stored them.
 *
 * The relation cases cover the two shapes the validity check takes: a class with inheritance walks
 * the chain for its values, {@see \Pimcore\Model\DataObject\Classificationstore::getStoredKeyValue()},
 * one without it reads the raw items. Neither resolves a descriptor - the value the check is handed
 * is the stored one until something reads the key - except for a relation type core does not ship,
 * which is asked its own validator over the elements it expects.
 *
 * @group model.datatype.classificationstore
 */
class DocumentValidityAndEditModeTest extends ValidityAndEditModeTest
{
    private const RELATION_KEY_PREFIX = 'cs-validity-relation-';

    private const FIELDNAME = 'csstore';

    private const MAXIMUM = 3;

    private const INNER_MAX_LENGTH = 5;

    /**
     * Loose enough for a value the tightened constraint refuses to be stored under it in the first
     * place, {@see self::createParentHoldingAFirstValueThatIsAboutToBeRefused()}.
     */
    private const LOOSE_MAX_LENGTH = 50;

    private const VARIANT = 'metric';

    private const KEPT_VALUE = 'kept';

    protected function setUp(): void
    {
        parent::setUp();

        DocumentValidityProjectRelation::reset();
        $this->registerProjectRelationType();
    }

    protected function tearDown(): void
    {
        DocumentValidityProjectRelation::reset();

        parent::tearDown();
    }

    protected function getStoreEngine(): ?string
    {
        return 'document';
    }

    /**
     * A null entry would shadow the collection a parent maps, because the mappings are merged as an
     * array union, so the document engine maps only the groups that have a collection.
     */
    protected function expectedMappingOfAGroupWithoutCollection(int $groupId): array
    {
        return [];
    }

    /**
     * The document engine resolves the definition of a stored value through the assignment of its
     * own group, and through the key config for a key that was removed from the group.
     */
    public function testTheDocumentEngineResolvesAStoredValueThroughItsOwnGroupsAssignment(): void
    {
        $groupA = $this->createGroup('engine_units_a');
        $groupB = $this->createGroup('engine_units_b');
        $removedFrom = $this->createGroup('engine_units_removed');

        $millimetre = $this->createUnit('mm');
        $centimetre = $this->createUnit('cm');

        $quantity = new QuantityValue();
        $quantity->setDefaultUnit($centimetre);
        $keyConfig = $this->createKey('engine_units', true, $quantity);

        $this->assignKey($groupA, $keyConfig, 0, false, ['defaultUnit' => $millimetre]);
        $this->assignKey($groupB, $keyConfig);

        $engine = Pimcore::getContainer()->get(EngineResolver::class)->resolveForStore($this->getStoreId());
        $this->assertInstanceOf(DocumentEngine::class, $engine);

        $resolve = new ReflectionMethod(DocumentEngine::class, 'resolveFieldDefinition');

        $this->assertSame($millimetre, $resolve->invoke($engine, $groupA->getId(), $keyConfig->getId())->getDefaultUnit());
        $this->assertSame($centimetre, $resolve->invoke($engine, $groupB->getId(), $keyConfig->getId())->getDefaultUnit());
        $this->assertSame(
            $centimetre,
            $resolve->invoke($engine, $removedFrom->getId(), $keyConfig->getId())->getDefaultUnit()
        );
    }

    private function createUnit(string $abbreviation): string
    {
        $unit = Unit::getByAbbreviation($abbreviation);
        if (!$unit) {
            $unit = new Unit();
            $unit->setAbbreviation($abbreviation);
            $unit->save();
        }

        return $unit->getId();
    }

    public function testARelationKeyPassesValidationOnAClassWithoutInheritance(): void
    {
        $object = $this->createObjectWithRelation(new Csstore(), 'validity-relation-plain');

        $this->assertNotNull(
            Csstore::getById($object->getId(), ['force' => true]),
            'saving the object with a relation key must pass validation'
        );
    }

    public function testARelationKeyPassesValidationOnAClassWithInheritance(): void
    {
        $object = $this->createObjectWithRelation(new Csstorelocalized(), 'validity-relation-inherit');

        $this->assertNotNull(
            Csstorelocalized::getById($object->getId(), ['force' => true]),
            'saving the object with a relation key must pass validation'
        );
    }

    /**
     * The key was never read, so the raw items hold the descriptor - which is what the validity
     * check of a class without inheritance is handed.
     */
    public function testARelationKeyPassesValidationWhenItWasNeverRead(): void
    {
        $object = $this->createObjectWithRelation(new Csstore(), 'validity-relation-unread');

        $reloaded = Csstore::getById($object->getId(), ['force' => true]);
        $reloaded->save();

        $this->assertNotNull(Csstore::getById($object->getId(), ['force' => true]));
    }

    public function testARelationKeyPassesValidationWhenItWasReadFirst(): void
    {
        $object = $this->createObjectWithRelation(new Csstorelocalized(), 'validity-relation-read');
        [$groupId, $keyId] = $this->relationCoordinatesOf($object);

        $reloaded = Csstorelocalized::getById($object->getId(), ['force' => true]);
        $this->assertCount(1, $reloaded->getCsstore()->getLocalizedKeyValue($groupId, $keyId));

        $reloaded->save();

        $this->assertNotNull(Csstorelocalized::getById($object->getId(), ['force' => true]));
    }

    /**
     * The payload merges the items of the whole inheritance chain, so a relation value an ancestor
     * contributes has to reach it as its elements rather than as the descriptor the ancestor holds.
     */
    public function testTheEditModePayloadResolvesARelationValueInheritedFromTheParent(): void
    {
        $group = $this->createGroup('validity_inherited_relation');
        $keyConfig = $this->createKey('validity_inherited_key', true, new ManyToManyObjectRelation());
        $this->assignKey($group, $keyConfig, 0);

        $target = TestHelper::createEmptyObject(self::RELATION_KEY_PREFIX . 'inherited');

        $parent = new Csstorelocalized();
        $parent->setParentId(1);
        $parent->setKey('validity-relation-parent');
        $parent->setPublished(true);
        $parent->getCsstore()->setLocalizedKeyValue($group->getId(), $keyConfig->getId(), [$target]);
        $parent->save();

        $child = new Csstorelocalized();
        $child->setParentId($parent->getId());
        $child->setKey('validity-relation-child');
        $child->setPublished(true);
        $child->getCsstore()->setActiveGroups([$group->getId() => true]);
        $child->save();

        $child = Csstorelocalized::getById($child->getId(), ['force' => true]);
        $payload = $child->getClass()
            ->getFieldDefinition(self::FIELDNAME)
            ->getDataForEditmode($child->getCsstore(), $child);

        $rows = $payload['data']['default'][$group->getId()][$keyConfig->getId()] ?? null;

        $this->assertIsArray($rows, 'the inherited relation value has to reach the payload');
        $this->assertSame([$target->getId()], array_column($rows, 'id'));
    }

    /**
     * A relation type a project adds keeps its own validator, and the stored descriptors of an
     * unread key are resolved into the elements that validator expects before it is asked.
     *
     * The four constraints checked from the descriptors alone are the ones the five relation types
     * core ships declare, {@see \Pimcore\Model\DataObject\Classificationstore\RelationResolver::checkStoredValidity()}.
     * A subclass may refuse what they allow from an overridden `checkValidity()`, so the bulk check
     * is claimed for those five by exact class, the way
     * {@see \Pimcore\Model\DataObject\Classificationstore\RelationEditModeResolver::supports()}
     * claims the rendering. What a save refuses must not depend on whether the key had been read.
     */
    public function testAProjectRelationTypeKeepsItsOwnValidatorWhenTheKeyWasNeverRead(): void
    {
        $object = $this->createObjectWithProjectRelation('validity-project-unread');

        // nothing reads the key, so its value is still the descriptor the document was loaded from
        $reloaded = Csstore::getById($object->getId(), ['force' => true]);

        DocumentValidityProjectRelation::reset();
        DocumentValidityProjectRelation::$refuse = true;

        $refusal = null;

        try {
            $reloaded->save();
        } catch (ValidationException $exception) {
            $refusal = $exception;
        }

        $this->assertNotNull(
            $refusal,
            'the project type\'s own checkValidity() has to be asked for a key nobody read'
        );
        $this->assertNotEmpty(
            DocumentValidityProjectRelation::$received,
            'and has to be asked about the value of that key'
        );

        foreach (DocumentValidityProjectRelation::$received as $received) {
            $this->assertIsArray(
                $received,
                'the project validator must be handed the elements of the descriptor, not the descriptor'
            );
            $this->assertContainsOnlyInstancesOf(Concrete::class, $received);
        }
    }

    /**
     * The same key read before the save: the project type refuses it there as well, so its outcome
     * is the one the unread save above reaches.
     */
    public function testAProjectRelationTypeIsRefusedTheSameWayWhenTheKeyWasReadFirst(): void
    {
        $object = $this->createObjectWithProjectRelation('validity-project-read');
        [$groupId, $keyId] = $this->relationCoordinatesOf($object);

        $reloaded = Csstore::getById($object->getId(), ['force' => true]);

        $this->assertCount(
            1,
            $reloaded->getCsstore()->getLocalizedKeyValue($groupId, $keyId),
            'precondition: reading the key resolves its descriptor'
        );

        DocumentValidityProjectRelation::reset();
        DocumentValidityProjectRelation::$refuse = true;

        $this->expectException(ValidationException::class);

        $reloaded->save();
    }

    /**
     * A stored descriptor is the state the definition in force when it was written allowed. Making
     * the key's assignment to the group mandatory afterwards has to be noticed on the next save of
     * an object whose value is still that descriptor - the one case the key's own check cannot be
     * asked about, because it reads the element of every entry.
     */
    public function testAStoredRelationThatHasBecomeMandatoryFailsValidation(): void
    {
        $group = $this->createGroup('validity_mandatory_relation');
        $keyConfig = $this->createKey('validity_mandatory_key', true, new ManyToManyObjectRelation());
        $relation = $this->assignKey($group, $keyConfig, 0);

        $object = new Csstore();
        $object->setParentId(1);
        $object->setKey('validity-relation-mandatory');
        $object->setPublished(true);

        // the state a key whose every target was deleted is left in by a read: the resolved value
        // takes the descriptor's place in the items, and the next save stores it as an empty one
        $store = $object->getCsstore();
        $store->setActiveGroups([$group->getId() => true]);
        $store->setItems([$group->getId() => [$keyConfig->getId() => ['default' => []]]]);
        $object->save();

        $relation->setMandatory(true);
        $relation->save();
        $this->getRelationCache()->reset();

        $reloaded = Csstore::getById($object->getId(), ['force' => true]);

        $this->expectException(ValidationException::class);

        $reloaded->save();
    }

    /**
     * A stored descriptor is checked against what the key's assignment to the group allows now, and
     * a target that is still allowed must not be refused.
     */
    public function testAStoredRelationThatStillSatisfiesItsDefinitionPassesValidation(): void
    {
        $object = $this->createObjectWithRelation(new Csstore(), 'validity-relation-allowed');

        $reloaded = Csstore::getById($object->getId(), ['force' => true]);
        $reloaded->save();

        $this->assertNotNull(Csstore::getById($object->getId(), ['force' => true]));
    }

    /**
     * A stored descriptor whose every target has been deleted is the key holding nothing, so the
     * value the validity check is handed is the one the inheritable parent contributes.
     *
     * Reading the key is what drops a dead target, {@see \Pimcore\Model\DataObject\Classificationstore::readItem()},
     * so counting a descriptor of nothing but dead targets as a value would make the outcome depend
     * on whether anyone had read the key: the unread save of a mandatory key would be refused while
     * the same save after a read of the same unchanged state passed. Both cases are measured, and
     * they have to agree.
     *
     * Nothing is instantiated to decide it: the targets of the whole store are looked up in one
     * round, {@see \Pimcore\Model\DataObject\Classificationstore\RelationResolver::findExistingTargets()}.
     */
    public function testAMandatoryRelationOfDeadTargetsFallsBackToTheParentOnAnUnreadSave(): void
    {
        [$child] = $this->createChildWhoseOwnRelationTargetIsGone('validity-dead-parent-unread');

        $reloaded = Csstorelocalized::getById($child->getId(), ['force' => true]);
        $reloaded->save();

        $this->assertNotNull(
            Csstorelocalized::getById($child->getId(), ['force' => true]),
            'the live value the parent holds is what satisfies the mandatory key'
        );
    }

    public function testAMandatoryRelationOfDeadTargetsFallsBackToTheParentWhenItWasReadFirst(): void
    {
        [$child, $groupId, $keyId] = $this->createChildWhoseOwnRelationTargetIsGone('validity-dead-parent-read');

        $reloaded = Csstorelocalized::getById($child->getId(), ['force' => true]);

        $this->assertCount(
            1,
            $reloaded->getCsstore()->getLocalizedKeyValue($groupId, $keyId),
            'precondition: the read drops the dead target and inherits the value of the parent'
        );

        $reloaded->save();

        $this->assertNotNull(Csstorelocalized::getById($child->getId(), ['force' => true]));
    }

    /**
     * The same, one language in: a descriptor of nothing but dead targets in one language has to let
     * the default language answer for the key, read first or not.
     */
    public function testAMandatoryRelationOfDeadTargetsFallsBackToTheDefaultLanguageOnAnUnreadSave(): void
    {
        [$object] = $this->createObjectWhoseRelationTargetIsGoneInOneLanguage('validity-dead-default-unread');

        $reloaded = Csstorelocalized::getById($object->getId(), ['force' => true]);
        $reloaded->save();

        $this->assertNotNull(
            Csstorelocalized::getById($object->getId(), ['force' => true]),
            'the live value of the default language is what satisfies the mandatory key'
        );
    }

    public function testAMandatoryRelationOfDeadTargetsFallsBackToTheDefaultLanguageWhenItWasReadFirst(): void
    {
        [$object, $groupId, $keyId] = $this->createObjectWhoseRelationTargetIsGoneInOneLanguage(
            'validity-dead-default-read'
        );

        $reloaded = Csstorelocalized::getById($object->getId(), ['force' => true]);

        $this->assertCount(
            1,
            $reloaded->getCsstore()->getLocalizedKeyValue($groupId, $keyId, 'en'),
            'precondition: the read drops the dead target and falls back to the default language'
        );

        $reloaded->save();

        $this->assertNotNull(Csstorelocalized::getById($object->getId(), ['force' => true]));
    }

    /**
     * A target deleted between two validations of the same instance has to be seen by the second
     * one.
     *
     * Whether a stored descriptor still has a target is looked up for the whole store at once, and
     * held for the walk that asked - deleting the target changes nothing about the store, so
     * neither its items nor their revision can tell that a held answer has gone stale. Held past
     * the walk, the second validation would select the child's dead descriptor over the live value
     * the parent holds, while the summaries of that same validation are read fresh,
     * {@see \Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore::checkStoredRelations()},
     * and refuse a mandatory key the very same save passes after a reload.
     */
    public function testATargetDeletedBetweenTwoValidationsOfTheSameInstanceIsSeenByTheSecond(): void
    {
        $group = $this->createGroup('validity_deleted_between');
        $keyConfig = $this->createKey('validity_deleted_between_key', true, new ManyToManyObjectRelation());
        $this->assignKey($group, $keyConfig, 0, true);

        $groupId = (int) $group->getId();
        $keyId = (int) $keyConfig->getId();

        $ofTheParent = TestHelper::createEmptyObject(self::RELATION_KEY_PREFIX . 'between-parent');
        $ofTheChild = TestHelper::createEmptyObject(self::RELATION_KEY_PREFIX . 'between-child');

        $parent = new Csstorelocalized();
        $parent->setParentId(1);
        $parent->setKey('validity-between-parent');
        $parent->setPublished(true);
        $parent->getCsstore()->setLocalizedKeyValue($groupId, $keyId, [$ofTheParent]);
        $parent->save();

        $child = new Csstorelocalized();
        $child->setParentId($parent->getId());
        $child->setKey('validity-between-child');
        $child->setPublished(true);
        $child->getCsstore()->setActiveGroups([$groupId => true]);
        $child->getCsstore()->setLocalizedKeyValue($groupId, $keyId, [$ofTheChild]);
        $child->save();

        $reloaded = Csstorelocalized::getById($child->getId(), ['force' => true]);

        // the first validation: the child's own target is alive, so its own value is what the
        // mandatory key is satisfied by, and the lookup that says so is held for that walk
        $reloaded->save();

        $ofTheChild->delete();

        // the same instance, nothing about its items changed - and the live value the parent holds
        // is what the key has to be satisfied by now
        $reloaded->save();

        $this->assertNotNull(
            Csstorelocalized::getById($child->getId(), ['force' => true]),
            'the second save must select the live value of the parent, not the dead local descriptor'
        );
    }

    /**
     * Reading a child's own to-many key replaces its descriptor with the elements, while the parent
     * at the same coordinate still holds the descriptor it was loaded as. The two must not be
     * merged into one another: a value belongs to one store as a whole, and the nearer store's is
     * the one that answers.
     */
    public function testReadingAChildsOwnRelationKeyLeavesTheInheritedItemsAndPayloadIntact(): void
    {
        $group = $this->createGroup('validity_merged_relation');
        $keyConfig = $this->createKey('validity_merged_key', true, new ManyToManyObjectRelation());
        $this->assignKey($group, $keyConfig, 0);

        $groupId = (int) $group->getId();
        $keyId = (int) $keyConfig->getId();

        $ofTheParent = TestHelper::createEmptyObject(self::RELATION_KEY_PREFIX . 'merged-parent');
        $ofTheChild = TestHelper::createEmptyObject(self::RELATION_KEY_PREFIX . 'merged-child');

        $parent = new Csstorelocalized();
        $parent->setParentId(1);
        $parent->setKey('validity-merged-parent');
        $parent->setPublished(true);
        $parent->getCsstore()->setLocalizedKeyValue($groupId, $keyId, [$ofTheParent]);
        $parent->save();

        $child = new Csstorelocalized();
        $child->setParentId($parent->getId());
        $child->setKey('validity-merged-child');
        $child->setPublished(true);
        $child->getCsstore()->setLocalizedKeyValue($groupId, $keyId, [$ofTheChild]);
        $child->save();

        $child = Csstorelocalized::getById($child->getId(), ['force' => true]);
        $store = $child->getCsstore();

        $this->assertSame(
            [$ofTheChild->getId()],
            array_map(
                static fn ($element): int => $element->getId(),
                $store->getLocalizedKeyValue($groupId, $keyId)
            ),
            'precondition: the child answers with its own value, which resolves its descriptor'
        );

        $items = $store->getItems();

        $this->assertSame(
            [$ofTheChild->getId()],
            array_map(
                static fn ($element): int => $element->getId(),
                $items[$groupId][$keyId]['default']
            ),
            'the parent\'s unread descriptor must not be merged into the value the child holds'
        );

        $payload = $child->getClass()
            ->getFieldDefinition(self::FIELDNAME)
            ->getDataForEditmode($store, $child);

        $rows = $payload['data']['default'][$groupId][$keyId] ?? null;

        $this->assertIsArray($rows);
        $this->assertSame([$ofTheChild->getId()], array_column($rows, 'id'));
    }

    /**
     * A parent holding a live relation value and a child holding its own at the same coordinate,
     * whose target is then deleted - so the child's stored descriptor names nothing that exists.
     * The key's assignment to the group is mandatory, which is what makes the value the check
     * selects decide whether the save passes.
     *
     * @return array{0: Csstorelocalized, 1: int, 2: int} the child, and the coordinate of the key
     */
    private function createChildWhoseOwnRelationTargetIsGone(string $key): array
    {
        $group = $this->createGroup('validity_dead_parent');
        $keyConfig = $this->createKey('validity_dead_parent_key', true, new ManyToManyObjectRelation());
        $this->assignKey($group, $keyConfig, 0, true);

        $groupId = (int) $group->getId();
        $keyId = (int) $keyConfig->getId();

        $ofTheParent = TestHelper::createEmptyObject(self::RELATION_KEY_PREFIX . 'dead-parent-live');
        $ofTheChild = TestHelper::createEmptyObject(self::RELATION_KEY_PREFIX . 'dead-parent-dead');

        $parent = new Csstorelocalized();
        $parent->setParentId(1);
        $parent->setKey($key . '-parent');
        $parent->setPublished(true);
        $parent->getCsstore()->setLocalizedKeyValue($groupId, $keyId, [$ofTheParent]);
        $parent->save();

        $child = new Csstorelocalized();
        $child->setParentId($parent->getId());
        $child->setKey($key . '-child');
        $child->setPublished(true);
        $child->getCsstore()->setActiveGroups([$groupId => true]);
        $child->getCsstore()->setLocalizedKeyValue($groupId, $keyId, [$ofTheChild]);
        $child->save();

        $ofTheChild->delete();

        return [$child, $groupId, $keyId];
    }

    /**
     * One object whose mandatory relation key holds a live value in the default language and a
     * value in `en` whose target is then deleted.
     *
     * @return array{0: Csstorelocalized, 1: int, 2: int} the object, and the coordinate of the key
     */
    private function createObjectWhoseRelationTargetIsGoneInOneLanguage(string $key): array
    {
        $group = $this->createGroup('validity_dead_default');
        $keyConfig = $this->createKey('validity_dead_default_key', true, new ManyToManyObjectRelation());
        $this->assignKey($group, $keyConfig, 0, true);

        $groupId = (int) $group->getId();
        $keyId = (int) $keyConfig->getId();

        $ofTheDefault = TestHelper::createEmptyObject(self::RELATION_KEY_PREFIX . 'dead-default-live');
        $ofEnglish = TestHelper::createEmptyObject(self::RELATION_KEY_PREFIX . 'dead-default-dead');

        $object = new Csstorelocalized();
        $object->setParentId(1);
        $object->setKey($key);
        $object->setPublished(true);
        $object->getCsstore()->setLocalizedKeyValue($groupId, $keyId, [$ofTheDefault]);
        $object->getCsstore()->setLocalizedKeyValue($groupId, $keyId, [$ofEnglish], 'en');
        $object->save();

        $ofEnglish->delete();

        return [$object, $groupId, $keyId];
    }

    private function createObjectWithRelation(Concrete $object, string $key): Concrete
    {
        $group = $this->createGroup('validity_relation');
        $keyConfig = $this->createKey('validity_relation_key', true, new ManyToManyObjectRelation());
        $this->assignKey($group, $keyConfig, 0);

        $target = TestHelper::createEmptyObject(self::RELATION_KEY_PREFIX);

        $object->setParentId(1);
        $object->setKey($key);
        $object->setPublished(true);
        $object->getCsstore()->setLocalizedKeyValue($group->getId(), $keyConfig->getId(), [$target]);
        $object->save();

        return $object;
    }

    /**
     * One object whose store holds a key of a relation type a project adds, the way a bundle
     * registering its own data type would leave it.
     */
    private function createObjectWithProjectRelation(string $key): Csstore
    {
        $group = $this->createGroup('validity_project_relation');
        $keyConfig = $this->createKey('validity_project_key', true, new DocumentValidityProjectRelation());
        $this->assignKey($group, $keyConfig, 0);

        $target = TestHelper::createEmptyObject(self::RELATION_KEY_PREFIX . 'project');

        $object = new Csstore();
        $object->setParentId(1);
        $object->setKey($key);
        $object->setPublished(true);
        $object->getCsstore()->setLocalizedKeyValue($group->getId(), $keyConfig->getId(), [$target]);
        $object->save();

        return $object;
    }

    /**
     * Puts the project relation type into the object data type map, which is what a bundle does in
     * its configuration: the key config holds the type by name, and the field definition of the
     * key's assignment to a group is built from it,
     * {@see \Pimcore\Model\DataObject\Classificationstore\KeyGroupRelationCache::getMutableFieldDefinition()}.
     */
    private function registerProjectRelationType(): void
    {
        $loader = Pimcore::getContainer()->get('pimcore.implementation_loader.object.data');

        if ($loader->supports(DocumentValidityProjectRelation::FIELD_TYPE)) {
            return;
        }

        $loader->addLoader(new ClassMapLoader([
            DocumentValidityProjectRelation::FIELD_TYPE => DocumentValidityProjectRelation::class,
        ]));
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function relationCoordinatesOf(Concrete $object): array
    {
        $items = $object->getCsstore()->getItems();
        $groupId = (int) array_key_first($items);

        return [$groupId, (int) array_key_first($items[$groupId])];
    }

    /**
     * A list at the declared maximum validates, so the check is a maximum rather than a limit one
     * value below it.
     */
    public function testAListAtTheDeclaredMaximumValidates(): void
    {
        $group = $this->createGroup('validity_at_maximum');
        $listKey = $this->createKey('validity_at_maximum', true, $this->multiValueDefinition());

        $this->assignKey($group, $listKey, 0);

        $object = new Csstorelocalized();
        $object->setParentId(1);
        $object->setKey('validity-at-maximum');
        $object->setPublished(true);
        $object->getCsstore()->setActiveGroups([$group->getId() => true]);
        $object->getCsstore()->setKeyValues($group->getId(), $listKey->getId(), [
            new MultiValueEntry('one', null, null, 0),
            new MultiValueEntry('two', null, null, 1),
            new MultiValueEntry('three', null, null, 2),
        ]);

        $object->getClass()->getFieldDefinition(self::FIELDNAME)->checkValidity($object->getCsstore());

        $this->addToAssertionCount(1);
    }

    /**
     * A list longer than the key's declared maximum is one of the per-key failures the field
     * collects, so it is reported beside the mandatory failures of the same save rather than
     * instead of them.
     */
    public function testAListLongerThanTheDeclaredMaximumIsReportedBesideTheMandatoryFailures(): void
    {
        $group = $this->createGroup('validity_maximum');
        $listKey = $this->createKey('validity_maximum', true, $this->multiValueDefinition());
        $mandatoryKey = $this->createKey('validity_mandatory');

        $this->assignKey($group, $listKey, 0);
        $this->assignKey($group, $mandatoryKey, 1, true);

        $object = new Csstorelocalized();
        $object->setParentId(1);
        $object->setKey('validity-maximum');
        $object->setPublished(true);
        $object->getCsstore()->setActiveGroups([$group->getId() => true]);
        $object->getCsstore()->setKeyValues($group->getId(), $listKey->getId(), [
            new MultiValueEntry('one', null, null, 0),
            new MultiValueEntry('two', null, null, 1),
            new MultiValueEntry('three', null, null, 2),
            new MultiValueEntry('four', null, null, 3),
        ]);

        $fieldDefinition = $object->getClass()->getFieldDefinition(self::FIELDNAME);

        try {
            $fieldDefinition->checkValidity($object->getCsstore());
            $this->fail('a list longer than the declared maximum must not validate');
        } catch (ValidationException $validationException) {
            $messages = array_map(
                static fn (ValidationException $subItem): string => $subItem->getMessage(),
                $validationException->getSubItems()
            );

            $maximumMessages = array_values(array_filter(
                $messages,
                static fn (string $message): bool => str_contains($message, 'at most')
            ));

            $this->assertCount(1, $maximumMessages, 'the one over-long slot is reported once');
            $this->assertStringContainsString($listKey->getName(), $maximumMessages[0]);
            $this->assertStringContainsString((string) self::MAXIMUM, $maximumMessages[0]);

            $this->assertNotEmpty(
                array_filter(
                    $messages,
                    static fn (string $message): bool => str_contains($message, $mandatoryKey->getName())
                ),
                'the mandatory failure of the other key is reported alongside it'
            );
        }
    }

    /**
     * And a mandatory multi-value key that does hold a value validates, so the check is about the
     * value rather than about the type of the key.
     */
    public function testAMandatoryMultiValueKeyHoldingAValueValidates(): void
    {
        $group = $this->createGroup('validity_mandatory_mv_filled');
        $listKey = $this->createKey('validity_mandatory_mv_filled', true, $this->multiValueDefinition());

        $this->assignKey($group, $listKey, 0, true);

        $object = new Csstorelocalized();
        $object->setParentId(1);
        $object->setKey('validity-mandatory-mv-filled');
        $object->setPublished(true);
        $object->getCsstore()->setActiveGroups([$group->getId() => true]);

        foreach ($this->getValidLanguages() as $language) {
            $object->getCsstore()->setKeyValues($group->getId(), $listKey->getId(), [
                new MultiValueEntry('held', $language, null, 0),
            ]);
        }

        $object->getClass()->getFieldDefinition(self::FIELDNAME)->checkValidity($object->getCsstore());

        $this->addToAssertionCount(1);
    }

    /**
     * A mandatory key holds a value when any slot of it does: a list whose first value was cleared
     * still holds the two behind it, and the positions of a list are what it is read back by - so a
     * check that only ever saw position 0 would refuse to save a key that is not empty.
     */
    public function testAMandatoryMultiValueKeyWhoseListStartsAboveTheFirstPositionValidates(): void
    {
        $group = $this->createGroup('validity_mandatory_mv_tail');
        $listKey = $this->createKey('validity_mandatory_mv_tail', true, $this->multiValueDefinition());

        $this->assignKey($group, $listKey, 0, true);

        $object = new Csstorelocalized();
        $object->setParentId(1);
        $object->setKey('validity-mandatory-mv-tail');
        $object->setPublished(true);
        $object->getCsstore()->setActiveGroups([$group->getId() => true]);

        foreach ($this->getValidLanguages() as $language) {
            $object->getCsstore()->setKeyValues($group->getId(), $listKey->getId(), [
                new MultiValueEntry('two', $language, null, 1),
                new MultiValueEntry('three', $language, null, 2),
            ]);
        }

        $object->getClass()->getFieldDefinition(self::FIELDNAME)->checkValidity($object->getCsstore());

        $this->addToAssertionCount(1);
    }

    /**
     * The mandatory flag of the key itself - the one its assignment to a group carries - is
     * consulted on a multi-value key as well. The wrapper hides the inner type from the check, and
     * the inner type carries a mandatory flag of its own, so delegating alone would report nothing
     * for a key that holds no value at all.
     */
    public function testAMandatoryMultiValueKeyWithoutAValueIsReported(): void
    {
        $group = $this->createGroup('validity_mandatory_mv');
        $listKey = $this->createKey('validity_mandatory_mv', true, $this->multiValueDefinition());

        $this->assignKey($group, $listKey, 0, true);

        $object = new Csstorelocalized();
        $object->setParentId(1);
        $object->setKey('validity-mandatory-mv');
        $object->setPublished(true);
        $object->getCsstore()->setActiveGroups([$group->getId() => true]);

        $fieldDefinition = $object->getClass()->getFieldDefinition(self::FIELDNAME);

        try {
            $fieldDefinition->checkValidity($object->getCsstore());
            $this->fail('an empty mandatory multi-value key must not validate');
        } catch (ValidationException $validationException) {
            $messages = array_map(
                static fn (ValidationException $subItem): string => $subItem->getMessage(),
                $validationException->getSubItems()
            );

            $this->assertNotEmpty(
                array_filter(
                    $messages,
                    static fn (string $message): bool => str_contains($message, $listKey->getName())
                ),
                'the mandatory failure of the multi-value key is reported'
            );
        }
    }

    /**
     * A value the inner type refuses in a slot the declaration no longer permits is hidden rather
     * than reported: narrowing a declaration keeps the values it stops showing, so they must not
     * make the object unsavable.
     */
    public function testASlotTheDeclarationNoLongerPermitsIsNotValidated(): void
    {
        $group = $this->createGroup('validity_hidden_slots');
        $listKey = $this->createKey('validity_hidden_slots', true, $this->constrainedMultiValueDefinition());

        $this->assignKey($group, $listKey, 0);

        $object = new Csstorelocalized();
        $object->setParentId(1);
        $object->setKey('validity-hidden-slots');
        $object->setPublished(true);
        $object->getCsstore()->setActiveGroups([$group->getId() => true]);
        $object->getCsstore()->setKeyValues($group->getId(), $listKey->getId(), [
            new MultiValueEntry('ok', null, null, 0),
            new MultiValueEntry('far too long', null, null, 1),
            new MultiValueEntry('also too long', null, self::VARIANT),
        ]);

        $listKey->setDefinition((string) json_encode($this->plainMultiValueDefinition()));
        $listKey->save();
        $this->clearDefinitionCaches();

        $object->getClass()->getFieldDefinition(self::FIELDNAME)->checkValidity($object->getCsstore());

        $this->addToAssertionCount(1);
    }

    /**
     * The second copy of the gate: a default-variant list longer than the maximum, on a key that
     * declines the unqualified value, is a hidden slot and so reports no violation.
     */
    public function testAnOverLongDefaultVariantListOfAKeyDecliningItIsNotReported(): void
    {
        $group = $this->createGroup('validity_declined_maximum');
        $listKey = $this->createKey('validity_declined_maximum', true, $this->multiValueDefinition());

        $this->assignKey($group, $listKey, 0);

        $object = new Csstorelocalized();
        $object->setParentId(1);
        $object->setKey('validity-declined-maximum');
        $object->setPublished(true);
        $object->getCsstore()->setActiveGroups([$group->getId() => true]);
        $object->getCsstore()->setKeyValues($group->getId(), $listKey->getId(), [
            new MultiValueEntry('one', null, null, 0),
            new MultiValueEntry('two', null, null, 1),
            new MultiValueEntry('three', null, null, 2),
            new MultiValueEntry('four', null, null, 3),
        ]);

        $this->assertNotEmpty(
            $this->validationFailuresContaining($object, 'at most'),
            'a key that permits the unqualified value reports the over-long list'
        );

        $declining = $this->multiValueDefinition();
        $declining->setUnqualifiedValue(false);
        $this->tightenTheInnerConstraint($listKey, $declining);

        $this->assertSame([], $this->validationFailuresContaining($object, 'at most'));
    }

    /**
     * The per-slot skip of the same gate: a default-variant value above position 0 that the inner type
     * refuses is validated while the key permits the unqualified value and is hidden once it declines.
     */
    public function testARefusedDefaultVariantValueAbovePositionZeroOfAKeyDecliningItIsNotValidated(): void
    {
        $group = $this->createGroup('validity_declined_slots');
        $listKey = $this->createKey('validity_declined_slots', true, $this->constrainedMultiValueDefinition());

        $this->assignKey($group, $listKey, 0);

        $object = new Csstorelocalized();
        $object->setParentId(1);
        $object->setKey('validity-declined-slots');
        $object->setPublished(true);
        $object->getCsstore()->setActiveGroups([$group->getId() => true]);
        $object->getCsstore()->setKeyValues($group->getId(), $listKey->getId(), [
            new MultiValueEntry('ok', null, null, 0),
            new MultiValueEntry('far too long', null, null, 1),
            new MultiValueEntry('ok', null, self::VARIANT),
        ]);

        $this->assertNotEmpty(
            $this->validationFailuresContaining($object, ''),
            'a key that permits the unqualified value validates the slot above position 0'
        );

        $declining = $this->constrainedMultiValueDefinition();
        $declining->setUnqualifiedValue(false);
        $this->tightenTheInnerConstraint($listKey, $declining);

        $this->assertSame([], $this->validationFailuresContaining($object, ''));
    }

    /**
     * A stored unqualified value on a key that no longer permits one stays valid, saves and stays
     * readable, and the editor re-sending it through the legacy write path does not refuse the save.
     */
    public function testAnUnqualifiedValueOfAKeyThatNoLongerPermitsOneStillValidatesSavesAndResends(): void
    {
        $group = $this->createGroup('validity_declined_resend');
        $listKey = $this->createKey('validity_declined_resend', true, $this->plainMultiValueDefinition());

        $this->assignKey($group, $listKey, 0);

        $object = new Csstorelocalized();
        $object->setParentId(1);
        $object->setKey('validity-declined-resend');
        $object->setPublished(true);
        $object->getCsstore()->setActiveGroups([$group->getId() => true]);
        $object->getCsstore()->setKeyValues($group->getId(), $listKey->getId(), [
            new MultiValueEntry(self::KEPT_VALUE),
        ]);
        $object->save();

        $declining = $this->plainMultiValueDefinition();
        $declining->setUnqualifiedValue(false);
        $this->tightenTheInnerConstraint($listKey, $declining);

        $object = Csstorelocalized::getById($object->getId(), ['force' => true]);
        $fieldDefinition = $object->getClass()->getFieldDefinition(self::FIELDNAME);

        $fieldDefinition->checkValidity($object->getCsstore());
        $object->save();

        $this->assertSame(
            self::KEPT_VALUE,
            $object->getCsstore()->getLocalizedKeyValue($group->getId(), $listKey->getId())
        );
        $this->assertSame(
            self::KEPT_VALUE,
            $object->getCsstore()->getItems()[$group->getId()][$listKey->getId()]['default']
        );

        $payload = $fieldDefinition->getDataForEditmode($object->getCsstore(), $object);
        $restored = $fieldDefinition->getDataFromEditmode($payload, $object);
        $object->setCsstore($restored);
        $object->save();

        $object = Csstorelocalized::getById($object->getId(), ['force' => true]);

        $this->assertSame(
            self::KEPT_VALUE,
            $object->getCsstore()->getLocalizedKeyValue($group->getId(), $listKey->getId())
        );
    }

    /**
     * Switching the unqualified value off on a mandatory key that declares variants hides the stored
     * value; it must not make the object unsavable.
     */
    public function testDecliningTheUnqualifiedValueOfAMandatoryKeyHoldingOneDoesNotRefuseTheSave(): void
    {
        $group = $this->createGroup('validity_declined_mandatory');
        $declaration = $this->plainMultiValueDefinition();
        $declaration->setMandatory(true);
        $declaration->setVariants([['name' => self::VARIANT]]);
        $listKey = $this->createKey('validity_declined_mandatory', true, $declaration);

        $this->assignKey($group, $listKey, 0);

        $object = new Csstorelocalized();
        $object->setParentId(1);
        $object->setKey('validity-declined-mandatory');
        $object->setPublished(true);
        $object->getCsstore()->setActiveGroups([$group->getId() => true]);
        $object->getCsstore()->setKeyValues($group->getId(), $listKey->getId(), [
            new MultiValueEntry(self::KEPT_VALUE),
        ]);
        $object->save();

        $declining = clone $declaration;
        $declining->setUnqualifiedValue(false);
        $this->tightenTheInnerConstraint($listKey, $declining);

        $object = Csstorelocalized::getById($object->getId(), ['force' => true]);

        $this->assertSame([], $this->validationFailuresContaining($object, ''));
    }

    /**
     * A key that declines the unqualified value and declares no variant has no writable slot. The
     * declaration is accepted and the writes refused, but an empty mandatory key is still empty: the
     * mandatory refusal must not depend on there being a variant to hold the placeholder.
     */
    public function testAnEmptyMandatoryKeyDecliningTheUnqualifiedValueWithNoVariantIsStillRefused(): void
    {
        $group = $this->createGroup('validity_declined_no_variant');
        $declaration = $this->plainMultiValueDefinition();
        $declaration->setMandatory(true);
        $declaration->setUnqualifiedValue(false);
        $listKey = $this->createKey('validity_declined_no_variant', true, $declaration);

        $this->assignKey($group, $listKey, 0);

        $object = new Csstorelocalized();
        $object->setParentId(1);
        $object->setKey('validity-declined-no-variant');
        $object->setPublished(true);
        $object->getCsstore()->setActiveGroups([$group->getId() => true]);

        $this->assertNotSame([], $this->validationFailuresContaining($object, 'mandatory'));
    }

    /**
     * The inner type validates every slot the declaration permits and not only the first value of
     * the default variant: a value it refuses at a higher position, or under a variant, is a per-key
     * failure of the save rather than something that stores clean.
     */
    /**
     * A slot is skipped only when no value was supplied for it, never because the inner type calls
     * the value it holds empty - those are two different questions, and some types answer the second
     * about the SHAPE of a value: `Time::isEmpty()` is a format check, so it calls `'25:99'` empty.
     *
     * Skipping on that answer left every malformed value after the first valid one unvalidated, at a
     * higher position and under a named variant alike, and the object saved with them.
     *
     * @dataProvider malformedTimeProvider
     */
    public function testAMalformedValueAfterAValidOneIsStillValidated(
        bool $mandatory,
        string $expectedMessage
    ): void {
        $group = $this->createGroup('validity_time_slots');
        $listKey = $this->createKey('validity_time_slots', true, $this->timeMultiValueDefinition($mandatory));

        $this->assignKey($group, $listKey, 0);

        $object = new Csstorelocalized();
        $object->setParentId(1);
        $object->setKey('validity-time-slots-' . ($mandatory ? 'mandatory' : 'optional'));
        $object->setPublished(true);
        $object->getCsstore()->setActiveGroups([$group->getId() => true]);
        $object->getCsstore()->setKeyValues($group->getId(), $listKey->getId(), [
            new MultiValueEntry('06:49', null, null, 0),
            new MultiValueEntry('25:99', null, null, 1),
            new MultiValueEntry('31:00', null, self::VARIANT),
        ]);

        try {
            $object->getClass()->getFieldDefinition(self::FIELDNAME)->checkValidity($object->getCsstore());
            $this->fail('a malformed value must not validate because the type calls it empty');
        } catch (ValidationException $validationException) {
            $messages = array_map(
                static fn (ValidationException $subItem): string => $subItem->getMessage(),
                $validationException->getSubItems()
            );

            $malformed = array_values(array_filter(
                $messages,
                static fn (string $message): bool => str_contains($message, $expectedMessage)
            ));

            $this->assertCount(2, $malformed, 'the higher position and the variant are both reported');
            $this->assertNotEmpty(
                array_filter($malformed, static fn (string $message): bool => str_contains($message, '/1')),
                'the failure names the position it was found at'
            );
            $this->assertNotEmpty(
                array_filter($malformed, static fn (string $message): bool => str_contains($message, self::VARIANT)),
                'and the variant it was found in'
            );
            $this->assertSame(
                [],
                array_values(array_filter(
                    $messages,
                    static fn (string $message): bool => str_contains($message, 'Empty mandatory field')
                )),
                'the mandatory flag is answered once per key, by the slot holding a value, and the '
                . 'slots that do not answer it are not refused for being empty'
            );
        }
    }

    /**
     * The mandatory flag is a statement about the key rather than about one of its slots, so a key
     * holding a valid value at position 0 is never refused for being empty. Checked in full,
     * `$omitMandatoryCheck` false, the malformed slots answered `isEmpty()` with `true` and a
     * mandatory key reported "Empty mandatory field" against each of them - a false statement, and
     * one that masks the format failure the slot really has.
     *
     * @return array<string, array{0: bool, 1: string}>
     */
    public static function malformedTimeProvider(): array
    {
        return [
            'an optional key' => [false, 'Wrong time format'],
            'a mandatory key' => [true, 'Wrong time format'],
        ];
    }

    public function testTheInnerTypeValidatesEveryDeclaredSlotAndNotOnlyTheFirst(): void
    {
        $group = $this->createGroup('validity_inner_slots');
        $listKey = $this->createKey('validity_inner_slots', true, $this->constrainedMultiValueDefinition());

        $this->assignKey($group, $listKey, 0);

        $object = new Csstorelocalized();
        $object->setParentId(1);
        $object->setKey('validity-inner-slots');
        $object->setPublished(true);
        $object->getCsstore()->setActiveGroups([$group->getId() => true]);
        $object->getCsstore()->setKeyValues($group->getId(), $listKey->getId(), [
            new MultiValueEntry('ok', null, null, 0),
            new MultiValueEntry('far too long', null, null, 1),
            new MultiValueEntry('also too long', null, self::VARIANT),
        ]);

        try {
            $object->getClass()->getFieldDefinition(self::FIELDNAME)->checkValidity($object->getCsstore());
            $this->fail('a value the inner type refuses must not validate wherever it sits');
        } catch (ValidationException $validationException) {
            $messages = array_map(
                static fn (ValidationException $subItem): string => $subItem->getMessage(),
                $validationException->getSubItems()
            );

            $tooLong = array_values(array_filter(
                $messages,
                static fn (string $message): bool => str_contains($message, 'is longer than')
            ));

            $this->assertCount(2, $tooLong, 'the higher position and the variant are both reported');
            $this->assertNotEmpty(
                array_filter($tooLong, static fn (string $message): bool => str_contains($message, '/1')),
                'the failure names the position it was found at'
            );
            $this->assertNotEmpty(
                array_filter($tooLong, static fn (string $message): bool => str_contains($message, self::VARIANT)),
                'and the variant it was found in'
            );
        }
    }

    /**
     * A child that inherits only a named variant of a mandatory key holds a value, and the check has
     * to see it.
     *
     * The per-language read turns inherited values on for a class that allows inheritance, so the
     * default value of a child is validated as the value in force. The qualified tree was read under
     * whatever mode the caller happened to leave behind, so the parent's variant was invisible to
     * the check: the child held a value and was refused for holding none.
     *
     * The mode the check is put under here is inherited values OFF, which is admin mode - the mode a
     * save from the editor runs in, {@see \Pimcore\Tests\Support\Helper\Pimcore::setAdminMode()}.
     */
    public function testAChildInheritingOnlyAVariantSatisfiesTheMandatoryCheck(): void
    {
        $group = $this->createGroup('validity_inherited_variant');
        $listKey = $this->createKey('validity_inherited_variant', true, $this->constrainedMultiValueDefinition());

        $this->assignKey($group, $listKey, 0, true);

        $parent = new Csstorelocalized();
        $parent->setParentId(1);
        $parent->setKey('validity-inherited-variant-parent');
        $parent->setPublished(true);
        $parent->getCsstore()->setActiveGroups([$group->getId() => true]);

        // the variant alone: the slot the one unqualified value would sit in stays empty
        foreach ($this->getValidLanguages() as $language) {
            $parent->getCsstore()->setKeyValues($group->getId(), $listKey->getId(), [
                new MultiValueEntry('ok', $language, self::VARIANT),
            ]);
        }

        $parent->save();

        $child = new Csstorelocalized();
        $child->setParentId((int) $parent->getId());
        $child->setKey('validity-inherited-variant-child');
        $child->setPublished(true);
        $child->getCsstore()->setActiveGroups([$group->getId() => true]);

        $child->getClass()->getFieldDefinition(self::FIELDNAME)->checkValidity($child->getCsstore());

        $this->addToAssertionCount(1);
    }

    /**
     * A key that has stopped being localized keeps the lists it holds in the languages it is no
     * longer kept in, and they must not refuse the save.
     *
     * The maximum check was handed every stored language, where every other per-key check is put to
     * the effective ones, {@see \Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore::getValidLanguages()}.
     * So a list nobody can now reach - or shorten - made the object unsavable.
     */
    public function testAListRetainedInALanguageTheKeyIsNoLongerKeptInDoesNotRefuseTheSave(): void
    {
        $group = $this->createGroup('validity_delocalized_maximum');
        $listKey = $this->createKey('validity_delocalized_maximum', true, $this->multiValueDefinition());
        $listKey->setLocalized(true);
        $listKey->save();

        $this->assignKey($group, $listKey, 0);
        $this->clearDefinitionCaches();

        $object = new Csstorelocalized();
        $object->setParentId(1);
        $object->setKey('validity-delocalized-maximum');
        $object->setPublished(true);
        $object->getCsstore()->setActiveGroups([$group->getId() => true]);
        $object->getCsstore()->setKeyValues($group->getId(), $listKey->getId(), [
            new MultiValueEntry('one', 'de', null, 0),
            new MultiValueEntry('two', 'de', null, 1),
            new MultiValueEntry('three', 'de', null, 2),
            new MultiValueEntry('four', 'de', null, 3),
        ]);

        $fieldDefinition = $object->getClass()->getFieldDefinition(self::FIELDNAME);

        try {
            $fieldDefinition->checkValidity($object->getCsstore());
            $this->fail('while the key is kept per language, the over-long German list is reported');
        } catch (ValidationException) {
            $this->addToAssertionCount(1);
        }

        $listKey->setLocalized(false);
        $listKey->save();
        $this->clearDefinitionCaches();

        $fieldDefinition->checkValidity($object->getCsstore());

        $this->addToAssertionCount(1);
    }

    /**
     * The delegated constraints core's types gate on `$omitMandatoryCheck` reach every slot that is
     * validated, and not only the first populated one. An `input`'s REGEX is one of them.
     *
     * `$omitMandatoryCheck` is not the switch its name suggests: `Input::checkValidity()` gates its
     * regex on it and `Numeric::checkValidity()` its range, its integer and its unsigned checks. The
     * slots after the first populated one were checked with it true - because the mandatory flag is
     * answered once per key and per language - so a value the key's own regex refuses stored clean
     * at a higher position or under a named variant. Its length constraint is NOT gated on the flag,
     * which is why {@see self::testTheInnerTypeValidatesEveryDeclaredSlotAndNotOnlyTheFirst()} passed
     * over this.
     */
    public function testTheDelegatedRegexRefusesAValueBeyondTheFirstPosition(): void
    {
        $group = $this->createGroup('validity_delegated_regex');
        $listKey = $this->createKey('validity_delegated_regex', true, $this->regexMultiValueDefinition());

        $this->assignKey($group, $listKey, 0);

        $object = new Csstorelocalized();
        $object->setParentId(1);
        $object->setKey('validity-delegated-regex');
        $object->setPublished(true);
        $object->getCsstore()->setActiveGroups([$group->getId() => true]);
        $object->getCsstore()->setKeyValues($group->getId(), $listKey->getId(), [
            new MultiValueEntry('AB-1', null, null, 0),
            new MultiValueEntry('nope', null, null, 1),
            new MultiValueEntry('also-nope', null, self::VARIANT),
        ]);

        $failures = $this->validationFailuresContaining($object, "doesn't match input validation");

        $this->assertCount(2, $failures, 'the higher position and the variant are both refused');
        $this->assertNotEmpty(
            array_filter($failures, static fn (string $message): bool => str_contains($message, '/1')),
            'the failure names the position it was found at'
        );
        $this->assertNotEmpty(
            array_filter($failures, static fn (string $message): bool => str_contains($message, self::VARIANT)),
            'and the variant it was found in'
        );
    }

    /**
     * And a `numeric`'s range, which is gated on the same flag together with its integer and its
     * unsigned checks - so a second delegated type is pinned rather than one type's one constraint.
     */
    public function testTheDelegatedNumericRangeRefusesAValueBeyondTheFirstPosition(): void
    {
        $group = $this->createGroup('validity_delegated_range');
        $listKey = $this->createKey('validity_delegated_range', true, $this->numericMultiValueDefinition());

        $this->assignKey($group, $listKey, 0);

        $object = new Csstorelocalized();
        $object->setParentId(1);
        $object->setKey('validity-delegated-range');
        $object->setPublished(true);
        $object->getCsstore()->setActiveGroups([$group->getId() => true]);
        $object->getCsstore()->setKeyValues($group->getId(), $listKey->getId(), [
            new MultiValueEntry(5, null, null, 0),
            new MultiValueEntry(50, null, null, 1),
            new MultiValueEntry(99, null, self::VARIANT),
        ]);

        $failures = $this->validationFailuresContaining($object, 'is bigger than');

        $this->assertCount(2, $failures, 'the higher position and the variant are both out of range');
    }

    /**
     * A child whose list starts above position 0 is not validated against the value position 0 would
     * have resolved to, because that value is an ancestor's and the child does not hold it.
     *
     * The resolved value is prepended as the default variant's position 0, which is how every caller
     * read the key before a value could carry qualifiers. But
     * {@see \Pimcore\Model\DataObject\Classificationstore::getQualifiedItems()} deliberately writes
     * no position 0 for a selected list that starts above it - the list came from a nearer store
     * than the resolved value did, and merging the two would put two objects' values into one list.
     * Validation added it back, so tightening the inner type's constraint refused the CHILD's own
     * valid list over a value the child does not inherit.
     */
    public function testAChildsSparseListIsNotValidatedAgainstThePositionItDoesNotInherit(): void
    {
        [$group, $listKey, $parent, $child] = $this->createParentHoldingAFirstValueThatIsAboutToBeRefused(
            'validity_sparse_list'
        );

        // the child holds position 1 alone, and it is a value the inner type accepts
        foreach ($this->getValidLanguages() as $language) {
            $child->getCsstore()->setKeyValues($group->getId(), $listKey->getId(), [
                new MultiValueEntry('ok', $language, null, 1),
            ]);
        }

        $this->tightenTheInnerConstraint($listKey, $this->constrainedMultiValueDefinition());

        $child->getClass()->getFieldDefinition(self::FIELDNAME)->checkValidity($child->getCsstore());

        // the parent itself does hold position 0, so the legacy resolution stands there: this must
        // not pass by validating nothing at all
        $this->assertNotEmpty(
            $this->validationFailuresContaining($parent, 'is longer than'),
            'the parent holds position 0 itself and is refused for it'
        );

        // and the child's OWN list is still validated
        foreach ($this->getValidLanguages() as $language) {
            $child->getCsstore()->setKeyValues($group->getId(), $listKey->getId(), [
                new MultiValueEntry('far too long', $language, null, 1),
            ]);
        }

        $this->assertNotEmpty(
            $this->validationFailuresContaining($child, 'is longer than'),
            "suppressing the inherited position 0 must not stop the child's own value being checked"
        );
    }

    /**
     * And the legacy resolution is retained once the declaration no longer permits the list: a
     * narrowed key addresses position 0 alone, so the value resolved there is the one it answers -
     * an inherited one included - and it is validated as the value in force.
     */
    public function testANarrowedKeyIsStillValidatedAgainstTheValueItResolvesAtPositionZero(): void
    {
        [$group, $listKey, , $child] = $this->createParentHoldingAFirstValueThatIsAboutToBeRefused(
            'validity_sparse_narrowed'
        );

        foreach ($this->getValidLanguages() as $language) {
            $child->getCsstore()->setKeyValues($group->getId(), $listKey->getId(), [
                new MultiValueEntry('ok', $language, null, 1),
            ]);
        }

        // narrowed as well as tightened: no variants and no list, so position 1 is hidden and
        // position 0 - the parent's value - is all the key addresses
        $this->tightenTheInnerConstraint($listKey, $this->plainMultiValueDefinition());

        $this->assertNotEmpty(
            $this->validationFailuresContaining($child, 'is longer than'),
            'the inherited position 0 is the value in force once the list is no longer declared'
        );
    }

    /**
     * A parent holding a position 0 of its own, declared loosely enough for its own save to pass,
     * and an empty child under it - the shape both sparse-list cases are measured in. The constraint
     * is tightened afterwards by {@see self::tightenTheInnerConstraint()}, which is the finding's own
     * scenario: the value was legal when it was stored and the child is the one that pays for it.
     *
     * @return array{0: Classificationstore\GroupConfig, 1: Classificationstore\KeyConfig, 2: Csstorelocalized, 3: Csstorelocalized}
     */
    private function createParentHoldingAFirstValueThatIsAboutToBeRefused(string $label): array
    {
        $group = $this->createGroup($label);
        $listKey = $this->createKey($label, true, $this->constrainedMultiValueDefinition(self::LOOSE_MAX_LENGTH));

        $this->assignKey($group, $listKey, 0);

        $parent = new Csstorelocalized();
        $parent->setParentId(1);
        $parent->setKey(str_replace('_', '-', $label) . '-parent');
        $parent->setPublished(true);
        $parent->getCsstore()->setActiveGroups([$group->getId() => true]);

        foreach ($this->getValidLanguages() as $language) {
            $parent->getCsstore()->setKeyValues($group->getId(), $listKey->getId(), [
                new MultiValueEntry('far too long', $language, null, 0),
            ]);
        }

        $parent->save();

        $child = new Csstorelocalized();
        $child->setParentId((int) $parent->getId());
        $child->setKey(str_replace('_', '-', $label) . '-child');
        $child->setPublished(true);
        $child->getCsstore()->setActiveGroups([$group->getId() => true]);

        return [$group, $listKey, $parent, $child];
    }

    /**
     * Rewrites the key's declaration to one whose inner type refuses the value the parent already
     * holds, and drops every cache that would answer the old one.
     */
    private function tightenTheInnerConstraint(
        Classificationstore\KeyConfig $listKey,
        MultiValue $declaration
    ): void {
        $listKey->setDefinition(json_encode($declaration));
        $listKey->save();

        $this->clearDefinitionCaches();
    }

    /**
     * The per-key validation failures of one object whose message carries the given text, as the
     * plain strings a test asserts over.
     *
     * @return string[]
     */
    private function validationFailuresContaining(Concrete $object, string $text): array
    {
        try {
            $object->getClass()->getFieldDefinition(self::FIELDNAME)->checkValidity($object->getCsstore());

            return [];
        } catch (ValidationException $validationException) {
            $messages = array_map(
                static fn (ValidationException $subItem): string => $subItem->getMessage(),
                $validationException->getSubItems()
            );

            return array_values(array_filter(
                $messages,
                static fn (string $message): bool => str_contains($message, $text)
            ));
        }
    }

    private function multiValueDefinition(): MultiValue
    {
        $definition = $this->wrapperOf($this->innerType());
        $definition->setMultiValue(self::MAXIMUM);

        return $definition;
    }

    /**
     * The same key with an inner type whose REGEX refuses a value, rather than its length: the regex
     * is one of the constraints core gates on `$omitMandatoryCheck`, and the length is not.
     */
    private function regexMultiValueDefinition(): MultiValue
    {
        $inner = new Input();
        $inner->setName('mvInner');
        $inner->setRegex('^[A-Z]{2}-[0-9]$');

        $definition = $this->wrapperOf($inner);
        $definition->setVariants([['name' => self::VARIANT]]);
        $definition->setMultiValue(self::MAXIMUM);

        return $definition;
    }

    /**
     * And with a `numeric` inner type bounded above, whose range check is gated on the same flag.
     */
    private function numericMultiValueDefinition(): MultiValue
    {
        $inner = new Numeric();
        $inner->setName('mvInner');
        $inner->setMaxValue(10);

        $definition = new MultiValue();
        $definition->setDelegateDatatype($inner->getFieldType());
        $definition->setDelegate($inner);
        $definition->setVariants([['name' => self::VARIANT]]);
        $definition->setMultiValue(self::MAXIMUM);

        return $definition;
    }

    /**
     * The same key with an inner type that refuses a value of its own, and with a variant to put one
     * in, so that the slot a failure is found in is what the test is about.
     */
    /**
     * A `multiValue<time>` list, for the types whose `isEmpty()` answers about the SHAPE of a value
     * rather than about its absence: {@see \Pimcore\Model\DataObject\ClassDefinition\Data\Time::isEmpty()}
     * is a format check, so it calls `'25:99'` empty.
     */
    private function timeMultiValueDefinition(bool $mandatory = false): MultiValue
    {
        $inner = new Time();
        $inner->setName('mvInner');

        $definition = new MultiValue();
        $definition->setDelegateDatatype($inner->getFieldType());
        $definition->setDelegate($inner);
        $definition->setVariants([['name' => self::VARIANT]]);
        $definition->setMultiValue(self::MAXIMUM);
        $definition->setMandatory($mandatory);

        return $definition;
    }

    private function constrainedMultiValueDefinition(int $maxLength = self::INNER_MAX_LENGTH): MultiValue
    {
        $definition = $this->wrapperOf($this->innerType($maxLength));
        $definition->setVariants([['name' => self::VARIANT]]);
        $definition->setMultiValue(self::MAXIMUM);

        return $definition;
    }

    /**
     * The narrowed declaration of that key: no variants and no list, so every slot but the first is
     * one it no longer permits.
     */
    private function plainMultiValueDefinition(): MultiValue
    {
        return $this->wrapperOf($this->innerType(self::INNER_MAX_LENGTH));
    }

    private function wrapperOf(Input $inner): MultiValue
    {
        $definition = new MultiValue();
        $definition->setDelegateDatatype($inner->getFieldType());
        $definition->setDelegate($inner);

        return $definition;
    }

    private function innerType(?int $maxLength = null): Input
    {
        $inner = new Input();
        $inner->setName('mvInner');
        $inner->setColumnLength($maxLength);

        return $inner;
    }

    /**
     * Everything that answers a key definition without a statement, so that a rewritten declaration
     * is the one the next read resolves.
     */
    private function clearDefinitionCaches(): void
    {
        Classificationstore\DefinitionCache::clear(null);
        Classificationstore\Service::clearDefinitionsCache();
        Pimcore::getContainer()->get(Classificationstore\KeyGroupRelationCache::class)->reset();
    }
}

/**
 * A relation type a project adds, as a bundle extending a core one would declare it: it refuses
 * what the core constraints allow, from a `checkValidity()` of its own.
 */
class DocumentValidityProjectRelation extends ManyToManyObjectRelation
{
    public const FIELD_TYPE = 'csProjectManyToManyObjectRelation';

    /**
     * Whether this type refuses the value it is handed, which is what no check reproduced from
     * descriptors can know about it.
     */
    public static bool $refuse = false;

    /**
     * Every value its own validator was handed since the last reset.
     *
     * @var list<mixed>
     */
    public static array $received = [];

    public static function reset(): void
    {
        self::$refuse = false;
        self::$received = [];
    }

    public function getFieldType(): string
    {
        return self::FIELD_TYPE;
    }

    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = []): void
    {
        self::$received[] = $data;

        if (self::$refuse) {
            throw new ValidationException('the project relation type refuses this value');
        }

        parent::checkValidity($data, $omitMandatoryCheck, $params);
    }
}
