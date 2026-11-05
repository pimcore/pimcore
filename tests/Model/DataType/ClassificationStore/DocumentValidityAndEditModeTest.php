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

use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Csstore;
use Pimcore\Model\DataObject\Csstorelocalized;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * ValidityAndEditModeTest against a store on the document engine: validation and the edit mode data
 * resolve in PHP at read time, so they have to come out the same whichever engine stored them.
 *
 * The relation cases cover the two shapes the validity check takes: a class with inheritance reads
 * its values through getLocalizedKeyValue(), one without it reads the raw items - which hold the
 * descriptor until the key is read.
 *
 * @group model.datatype.classificationstore
 */
class DocumentValidityAndEditModeTest extends ValidityAndEditModeTest
{
    private const RELATION_KEY_PREFIX = 'cs-validity-relation-';

    private const FIELDNAME = 'csstore';

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
     * @return array{0: int, 1: int}
     */
    private function relationCoordinatesOf(Concrete $object): array
    {
        $items = $object->getCsstore()->getItems();
        $groupId = (int) array_key_first($items);

        return [$groupId, (int) array_key_first($items[$groupId])];
    }
}
