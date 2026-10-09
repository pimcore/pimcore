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

use Pimcore;
use Pimcore\Db;
use Pimcore\Event\Model\VersionEvent;
use Pimcore\Event\VersionEvents;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\LazyLoading;
use Pimcore\Model\DataObject\Objectbrick\Data\LazyLoadingLocalizedTest;
use Pimcore\Model\DataObject\Objectbrick\Data\LazyLoadingTest;
use Pimcore\Model\DataObject\RelationTest;
use Pimcore\Model\DataObject\Service;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * Relation rows of all relation types and containers have to be saved against the current database state, not
 * against rows read before a concurrent save or copied from another object
 * (https://github.com/pimcore/pimcore/issues/17604).
 *
 * @group model.relations.outdated
 */
class OutdatedRelationDataContainerTest extends ModelTestCase
{
    /** @var RelationTest[] */
    private array $relationObjects = [];

    /** @var array<array{string, callable}> */
    private array $registeredListeners = [];

    public function setUp(): void
    {
        parent::setUp();
        TestHelper::cleanUp();

        for ($i = 0; $i < 3; $i++) {
            $object = new RelationTest();
            $object->setParent(Service::createFolderByPath('__test/relationobjects'));
            $object->setKey("relation-$i");
            $object->setPublished(true);
            $object->save();
            $this->relationObjects[] = $object;
        }
    }

    public function tearDown(): void
    {
        foreach ($this->registeredListeners as [$eventName, $listener]) {
            Pimcore::getEventDispatcher()->removeListener($eventName, $listener);
        }
        $this->registeredListeners = [];

        TestHelper::cleanUp();
        parent::tearDown();
    }

    protected function setUpTestClasses(): void
    {
        $this->tester->setupPimcoreClass_RelationTest();
        $this->tester->setupFieldcollection_LazyLoadingTest();
        $this->tester->setupFieldcollection_LazyLoadingLocalizedTest();
        $this->tester->setupPimcoreClass_LazyLoading();
        $this->tester->setupObjectbrick_LazyLoadingTest();
        $this->tester->setupObjectbrick_LazyLoadingLocalizedTest();
    }

    public function testCopyContentsReplacesRelationsInAllContainers(): void
    {
        [$x, $y, $z] = $this->relationObjects;
        $source = $this->createObject('source', [$x, $y]);
        $target = $this->createObject('target', [$x, $z]);

        (new Service())->copyContents(
            LazyLoading::getById($target->getId(), ['force' => true]),
            LazyLoading::getById($source->getId(), ['force' => true])
        );

        $this->assertSame($this->storedRelations($source->getId()), $this->storedRelations($target->getId()));
        $this->assertSame(
            $x->getId(),
            LazyLoading::getById($target->getId(), ['force' => true])->getRelation()?->getId()
        );

        // the target can be saved again
        $reloaded = LazyLoading::getById($target->getId(), ['force' => true]);
        $reloaded->setKey('target-renamed');
        $reloaded->save();
        $this->assertSame($this->storedRelations($source->getId()), $this->storedRelations($target->getId()));
    }

    public function testSavingOutdatedInstanceDoesNotDuplicateRelationsInContainers(): void
    {
        [$x, $y] = $this->relationObjects;
        $object = $this->createObject('outdated', [$x]);

        $outdated = LazyLoading::getById($object->getId(), ['force' => true]);
        $this->setRelations($outdated, [$x]);

        $current = LazyLoading::getById($object->getId(), ['force' => true]);
        $this->setRelations($current, [$x, $y]);
        $current->save();
        $expected = $this->storedRelations($object->getId());

        $this->setRelations($outdated, [$x, $y]);
        $outdated->save();

        $this->assertSame($expected, $this->storedRelations($object->getId()));
        $this->assertSame($expected, array_unique($expected));
    }

    public function testSavingObjectAgainWithinItsOwnSaveDoesNotDuplicateRelations(): void
    {
        [$x, $y] = $this->relationObjects;
        $object = $this->createObject('nested', [$x]);
        $expectedObject = $this->createObject('expected', [$x, $y]);

        $saved = false;
        $this->addListener(VersionEvents::POST_SAVE, function (VersionEvent $event) use ($object, &$saved): void {
            $data = $event->getVersion()->getData();
            if (!$saved && $data instanceof LazyLoading && $data->getId() === $object->getId()) {
                $saved = true;
                $data->save();
            }
        });

        $instance = LazyLoading::getById($object->getId(), ['force' => true]);
        $this->setRelations($instance, [$x, $y]);
        $instance->save();

        $this->assertTrue($saved);
        $this->assertSame($this->storedRelations($expectedObject->getId()), $this->storedRelations($object->getId()));
    }

    /**
     * @param RelationTest[] $relations
     */
    private function createObject(string $key, array $relations): LazyLoading
    {
        $object = new LazyLoading();
        $object->setParent(Service::createFolderByPath('/outdated-relations'));
        $object->setKey($key);
        $object->setPublished(true);
        $this->setRelations($object, $relations);
        $object->save();

        return $object;
    }

    /**
     * Sets the relations on the object level, in localized fields, in an object brick, in a localized object brick
     * and in a field collection.
     *
     * @param RelationTest[] $relations
     */
    private function setRelations(LazyLoading $object, array $relations): void
    {
        $object->setObjects($relations);
        $object->setRelation($relations[array_key_last($relations)]);
        $object->setRelations($relations);
        $object->setLobjects($relations, 'en');

        $brick = $object->getBricks()->getLazyLoadingTest() ?? new LazyLoadingTest($object);
        $brick->setObjects($relations);
        $object->getBricks()->setLazyLoadingTest($brick);

        $localizedBrick = $object->getBricks()->getLazyLoadingLocalizedTest() ?? new LazyLoadingLocalizedTest($object);
        $localizedBrick->setLObjects($relations, 'en');
        $object->getBricks()->setLazyLoadingLocalizedTest($localizedBrick);

        $item = new Fieldcollection\Data\LazyLoadingTest();
        $item->setObjects($relations);
        $items = new Fieldcollection();
        $items->add($item);
        $object->setFieldcollection($items);
    }

    /**
     * @return string[] all relation rows of the object, without the source id
     */
    private function storedRelations(int $objectId): array
    {
        $rows = Db::get()->fetchAllAssociative(
            'SELECT dest_id, type, fieldname, ownertype, ownername, position FROM object_relations_'
            . LazyLoading::classId() . ' WHERE src_id = ?',
            [$objectId]
        );
        $rows = array_map(static fn (array $row) => implode('|', $row), $rows);
        sort($rows);

        return $rows;
    }

    private function addListener(string $eventName, callable $listener): void
    {
        Pimcore::getEventDispatcher()->addListener($eventName, $listener);
        $this->registeredListeners[] = [$eventName, $listener];
    }
}
