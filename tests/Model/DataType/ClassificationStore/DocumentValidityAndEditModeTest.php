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
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Csstore;
use Pimcore\Model\DataObject\Csstorelocalized;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Tests\Support\Util\TestHelper;

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
