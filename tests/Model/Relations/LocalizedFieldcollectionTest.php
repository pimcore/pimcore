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

namespace Pimcore\Tests\Model\Relations;

use Pimcore\Db;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\CollectionWithoutMetadata;
use Pimcore\Model\DataObject\Data\ElementMetadata;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\LazyLoading;
use Pimcore\Model\DataObject\RelationTest;
use Pimcore\Model\DataObject\Service;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * Relation metadata of localized fields inside field collection items.
 *
 * @see https://github.com/pimcore/pimcore/issues/9701
 *
 * @group model.relations.fieldcollection
 */
class LocalizedFieldcollectionTest extends ModelTestCase
{
    /** @var RelationTest[] */
    private array $targets = [];

    public function setUp(): void
    {
        parent::setUp();
        TestHelper::cleanUp();

        $this->targets = [];
        for ($i = 0; $i < 3; $i++) {
            $target = new RelationTest();
            $target->setParent(Service::createFolderByPath('__test/relationobjects'));
            $target->setKey('target-' . $i);
            $target->setPublished(true);
            $target->save();
            $this->targets[] = $target;
        }
    }

    public function tearDown(): void
    {
        TestHelper::cleanUp();
        parent::tearDown();
    }

    protected function setUpTestClasses(): void
    {
        $this->tester->setupPimcoreClass_RelationTest();
        $this->tester->setupFieldcollection_LazyLoadingTest();
        $this->tester->setupFieldcollection_LazyLoadingLocalizedTest();
        $this->tester->setupPimcoreClass_LazyLoading();
        $this->tester->setupFieldcollection_LocalizedPlainRelations();
        $this->tester->setupPimcoreClass_CollectionWithoutMetadata();
    }

    public function testRemovingAnItemKeepsTheDataOfTheRemainingItem(): void
    {
        [$t0, $t1] = $this->targets;

        $object = $this->createDataObject();
        $items = new Fieldcollection();
        foreach (['first' => $t0, 'second' => $t1] as $tag => $target) {
            $item = new Fieldcollection\Data\LazyLoadingLocalizedTest();
            $item->setLadvancedObjects([$this->objectMetadata($target, $tag)], 'en');
            $item->setLadvancedRelations([$this->elementMetadata($target, $tag)], 'en');
            $items->add($item);
        }
        $object->setFieldcollection($items);
        $object->save();

        // the second item moves to index 0
        $object = LazyLoading::getById($object->getId(), ['force' => true]);
        $object->getFieldcollection()->remove(0);
        $object->save();

        $object = LazyLoading::getById($object->getId(), ['force' => true]);
        $items = $object->getFieldcollection()->getItems();
        $this->assertCount(1, $items);
        $this->assertMetadata([$t1->getId() => 'second'], $items[0]->getLadvancedObjects('en'));
        $this->assertMetadata([$t1->getId() => 'second'], $items[0]->getLadvancedRelations('en'));
        $this->assertSame(2, $this->countMetadataRows($object->getId(), 0));
    }

    public function testSavingTheObjectKeepsTheLocalizedMetadataOfItems(): void
    {
        [$t0, $t1] = $this->targets;

        $object = $this->createDataObject();
        $items = new Fieldcollection();
        $item = new Fieldcollection\Data\LazyLoadingLocalizedTest();
        $item->setLadvancedObjects([$this->objectMetadata($t0, 'en')], 'en');
        $item->setLadvancedObjects([$this->objectMetadata($t1, 'de')], 'de');
        $item->setLadvancedRelations([$this->elementMetadata($t0, 'en')], 'en');
        $items->add($item);
        $object->setFieldcollection($items);
        $object->save();

        foreach ([false, true] as $disableDirtyDetection) {
            // change a field outside of the field collection
            $object = LazyLoading::getById($object->getId(), ['force' => true]);
            $object->setInput('changed-' . (int) $disableDirtyDetection);
            if ($disableDirtyDetection) {
                DataObject::disableDirtyDetection();
            }

            try {
                $object->save();
            } finally {
                DataObject::enableDirtyDetection();
            }

            $object = LazyLoading::getById($object->getId(), ['force' => true]);
            $item = $object->getFieldcollection()->get(0);
            $this->assertMetadata([$t0->getId() => 'en'], $item->getLadvancedObjects('en'));
            $this->assertMetadata([$t1->getId() => 'de'], $item->getLadvancedObjects('de'));
            $this->assertMetadata([$t0->getId() => 'en'], $item->getLadvancedRelations('en'));
            $this->assertSame(3, $this->countMetadataRows($object->getId(), 0));
        }
    }

    public function testChangingOneItemOfAnUnpublishedObjectKeepsTheMetadataOfTheOthers(): void
    {
        [$t0, $t1, $t2] = $this->targets;

        // unpublished objects skip the validation, which otherwise loads the localized data of all items
        $object = $this->createDataObject();
        $object->setPublished(false);
        $items = new Fieldcollection();
        foreach (['first' => $t0, 'second' => $t1] as $tag => $target) {
            $item = new Fieldcollection\Data\LazyLoadingLocalizedTest();
            $item->setLadvancedObjects([$this->objectMetadata($target, $tag)], 'en');
            $item->setLadvancedRelations([$this->elementMetadata($target, $tag)], 'en');
            $items->add($item);
        }
        $object->setFieldcollection($items);
        $object->save();

        $object = LazyLoading::getById($object->getId(), ['force' => true]);
        $object->getFieldcollection()->get(1)->setLadvancedObjects([$this->objectMetadata($t2, 'changed')], 'en');
        $object->save();

        $object = LazyLoading::getById($object->getId(), ['force' => true]);
        $first = $object->getFieldcollection()->get(0);
        $this->assertMetadata([$t0->getId() => 'first'], $first->getLadvancedObjects('en'));
        $this->assertMetadata([$t0->getId() => 'first'], $first->getLadvancedRelations('en'));
        $this->assertMetadata([$t2->getId() => 'changed'], $object->getFieldcollection()->get(1)->getLadvancedObjects('en'));
    }

    public function testClassWithoutMetadataTableSavesAndDeletesFieldcollections(): void
    {
        [$t0, $t1] = $this->targets;

        $object = new CollectionWithoutMetadata();
        $object->setParentId(1);
        $object->setKey('collection-without-metadata');
        $object->setPublished(true);
        $items = new Fieldcollection();
        foreach ([$t0, $t1] as $target) {
            $item = new Fieldcollection\Data\LocalizedPlainRelations();
            $item->setLobjects([$target], 'en');
            $items->add($item);
        }
        $object->setItems($items);
        $object->save();

        $object = CollectionWithoutMetadata::getById($object->getId(), ['force' => true]);
        $object->getItems()->remove(0);
        $object->save();

        $object = CollectionWithoutMetadata::getById($object->getId(), ['force' => true]);
        $this->assertSame(
            [$t1->getId()],
            array_map(static fn ($element) => $element->getId(), $object->getItems()->get(0)->getLobjects('en'))
        );

        $id = $object->getId();
        $object->delete();
        $this->assertNull(CollectionWithoutMetadata::getById($id, ['force' => true]));
    }

    private function createDataObject(): LazyLoading
    {
        $object = new LazyLoading();
        $object->setParentId(1);
        $object->setKey('localized-fieldcollection-relations');
        $object->setPublished(true);

        return $object;
    }

    private function objectMetadata(DataObject\Concrete $target, string $value): ObjectMetadata
    {
        $metadata = new ObjectMetadata('ladvancedObjects', ['metadata'], $target);
        $metadata->setMetadata($value);

        return $metadata;
    }

    private function elementMetadata(DataObject\Concrete $target, string $value): ElementMetadata
    {
        $metadata = new ElementMetadata('ladvancedRelations', ['metadata'], $target);
        $metadata->setMetadata($value);

        return $metadata;
    }

    private function countMetadataRows(int $objectId, int $index): int
    {
        return (int) Db::get()->fetchOne(
            'SELECT COUNT(*) FROM object_metadata_' . LazyLoading::classId()
            . " WHERE id = ? AND ownertype = 'localizedfield' AND ownername = ?",
            [$objectId, '/fieldcollection~fieldcollection/' . $index . '/localizedfield~localizedfield']
        );
    }

    /**
     * @param array<int, string> $expected element id => metadata value
     * @param array<ObjectMetadata|ElementMetadata> $relations
     */
    private function assertMetadata(array $expected, array $relations): void
    {
        $actual = [];
        foreach ($relations as $relation) {
            $actual[$relation->getElement()->getId()] = $relation->getMetadata();
        }
        $this->assertSame($expected, $actual);
    }
}
