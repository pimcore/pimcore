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

namespace Pimcore\Tests\Model\DataObject;

use Pimcore;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Db;
use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Pimcore\Model\DataObject\Inheritance;
use Pimcore\Model\DataObject\Service;
use Pimcore\Model\DataObject\Unittest;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * Resolving the dependencies of an object reads the ids of its related elements. For a lazy loaded relation that
 * was not loaded, the stored relation rows tell them without loading every related element. The result must be
 * the same as with the loaded relation, whatever the getter would have left out or added.
 *
 * @group model.dataobject.lazyrelation
 */
class LazyRelationDependenciesTest extends ModelTestCase
{
    private bool $inheritedValues = false;

    private bool $hideUnpublished = false;

    protected function setUp(): void
    {
        $this->inheritedValues = DataObject::getGetInheritedValues();
        $this->hideUnpublished = DataObject::getHideUnpublished();

        parent::setUp();

        TestHelper::cleanUp();
    }

    protected function tearDown(): void
    {
        DataObject::setGetInheritedValues($this->inheritedValues);
        DataObject::setHideUnpublished($this->hideUnpublished);

        TestHelper::cleanUp();

        parent::tearDown();
    }

    public function testResolveDependenciesDoesNotLoadUntouchedRelations(): void
    {
        $owner = $this->createOwnerWithAllRelationTypes();
        $owner = $this->reload($owner);

        $loadedIds = [];
        $count = static function (DataObjectEvent $event) use (&$loadedIds): void {
            $loadedIds[] = $event->getObject()->getId();
        };
        $dispatcher = Pimcore::getEventDispatcher();
        $dispatcher->addListener(DataObjectEvents::POST_LOAD, $count);

        try {
            RuntimeCache::clear();
            $dependencies = $owner->resolveDependencies();
        } finally {
            $dispatcher->removeListener(DataObjectEvents::POST_LOAD, $count);
        }

        $this->assertEquals($this->loadedDependencies($owner), $dependencies);

        // the properties of the object load its parents, only the related objects must not be loaded
        $relatedObjectIds = array_column(array_filter(
            $dependencies,
            static fn (array $dependency) => $dependency['type'] === 'object'
        ), 'id');
        $this->assertNotEmpty($relatedObjectIds);
        $this->assertSame([], array_values(array_intersect($loadedIds, $relatedObjectIds)), 'no related object is loaded');
    }

    public function testResolveDependenciesOfUntouchedRelationsMatchesLoadedRelations(): void
    {
        $owner = $this->createOwnerWithAllRelationTypes();

        $this->assertDependenciesMatchLoadedRelations($owner);
    }

    public function testResolveDependenciesOmitsUnpublishedElementsLikeTheGetter(): void
    {
        $unpublished = TestHelper::createEmptyObject('lazy-dependencies-unpublished-', true, false);
        $owner = $this->createOwnerWithAllRelationTypes([$unpublished]);

        DataObject::setHideUnpublished(true);

        $dependencies = $this->assertDependenciesMatchLoadedRelations($owner);
        $this->assertArrayNotHasKey('object_' . $unpublished->getId(), $dependencies);
    }

    public function testResolveDependenciesOmitsDeletedElementsLikeTheGetter(): void
    {
        $deleted = TestHelper::createEmptyObject('lazy-dependencies-deleted-');
        $owner = $this->createOwnerWithAllRelationTypes([$deleted]);
        $deletedId = $deleted->getId();
        $deleted->delete();

        $dependencies = $this->assertDependenciesMatchLoadedRelations($owner);
        $this->assertArrayNotHasKey('object_' . $deletedId, $dependencies);
    }

    /**
     * The getter of an object relation only returns concrete objects, a stored relation to a folder is left out.
     */
    public function testResolveDependenciesOmitsAFolderInAnObjectRelationLikeTheGetter(): void
    {
        $folder = TestHelper::createObjectFolder('lazy-dependencies-folder-');
        $owner = $this->createOwnerWithAllRelationTypes();

        $db = Db::get();
        $db->insert('object_relations_' . $owner->getClassId(), Db\Helper::quoteDataIdentifiers($db, [
            'src_id' => $owner->getId(),
            'dest_id' => $folder->getId(),
            'type' => 'object',
            'fieldname' => 'objects',
            'index' => 99,
            'ownertype' => 'object',
            'ownername' => '',
            'position' => '0',
        ]));

        $dependencies = $this->assertDependenciesMatchLoadedRelations($owner);
        $this->assertArrayNotHasKey('object_' . $folder->getId(), $dependencies);
    }

    /**
     * With inherited values, an object without own relations has the relations of its parent.
     */
    public function testResolveDependenciesWithInheritedValuesMatchesLoadedRelations(): void
    {
        $target = TestHelper::createEmptyObject('lazy-dependencies-inherited-target-');

        $parent = $this->createInheritance('lazy-dependencies-parent-', 1);
        $parent->setRelationobjects([$target]);
        $parent->save();

        $child = $this->createInheritance('lazy-dependencies-child-', $parent->getId());
        $child->save();

        foreach ([true, false] as $inheritedValues) {
            DataObject::setGetInheritedValues($inheritedValues);

            $unloaded = Inheritance::getById($child->getId(), ['force' => true]);
            $dependencies = $unloaded->resolveDependencies();

            $this->assertEquals($this->loadedDependencies($child), $dependencies);
            $this->assertSame(
                $inheritedValues,
                isset($dependencies['object_' . $target->getId()]),
                'the inherited relation is a dependency only with inherited values'
            );
        }
    }

    /**
     * @param list<Concrete> $additionalTargets
     */
    private function createOwnerWithAllRelationTypes(array $additionalTargets = []): Unittest
    {
        $objectTarget = TestHelper::createEmptyObject('lazy-dependencies-object-');
        $metadataTarget = TestHelper::createEmptyObject('lazy-dependencies-metadata-');
        $asset = TestHelper::createImageAsset('lazy-dependencies-asset-');
        $document = TestHelper::createEmptyDocumentPage('lazy-dependencies-document-');
        $objectFolder = TestHelper::createObjectFolder('lazy-dependencies-multihref-folder-');

        $owner = TestHelper::createEmptyObject('lazy-dependencies-owner-', false);
        $owner->setObjects(array_merge([$objectTarget], $additionalTargets));
        $owner->setMultihref(array_merge([$asset, $document, $objectFolder, $objectTarget], $additionalTargets));
        $owner->setHref($document);
        $owner->setObjectswithmetadata(array_map(
            static fn (Concrete $target) => new ObjectMetadata('objectswithmetadata', ['meta1', 'meta2'], $target),
            array_merge([$metadataTarget], $additionalTargets)
        ));
        $owner->save();

        return $owner;
    }

    private function createInheritance(string $keyPrefix, int $parentId): Inheritance
    {
        $object = new Inheritance();
        $object->setOmitMandatoryCheck(true);
        $object->setParentId($parentId);
        $object->setKey($keyPrefix . uniqid());
        $object->setPublished(true);

        return $object;
    }

    /**
     * @return array<string, array{id: int, type: string}>
     */
    private function assertDependenciesMatchLoadedRelations(Concrete $object): array
    {
        $unloaded = $this->reload($object);
        $this->assertFalse($unloaded->isLazyKeyLoaded('objects'), 'precondition: the relation is not loaded');
        $dependencies = $unloaded->resolveDependencies();

        $this->assertEquals($this->loadedDependencies($object), $dependencies);

        return $dependencies;
    }

    /**
     * The dependencies as resolved from the loaded relations, the way they were resolved before.
     *
     * @return array<string, array{id: int, type: string}>
     */
    private function loadedDependencies(Concrete $object): array
    {
        $loaded = $this->reload($object);
        Service::loadAllObjectFields($loaded);

        return $loaded->resolveDependencies();
    }

    private function reload(Concrete $object): Concrete
    {
        $reloaded = Concrete::getById($object->getId(), ['force' => true]);
        $this->assertInstanceOf(Concrete::class, $reloaded);

        return $reloaded;
    }
}
