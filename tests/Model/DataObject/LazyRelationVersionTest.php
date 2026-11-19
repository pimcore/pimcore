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
use Pimcore\Event\SystemEvents;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Pimcore\Model\DataObject\Inheritance;
use Pimcore\Model\DataObject\Service;
use Pimcore\Model\DataObject\Unittest;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Service as ElementService;
use Pimcore\Model\Version;
use Pimcore\Tests\Support\Model\HookedUnittest;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use Pimcore\Tool\Serialize;
use ReflectionProperty;

/**
 * A version stores the relations of an object as the types and ids of the related elements. For a lazy loaded
 * relation that was not loaded, the stored relation rows tell them without loading every related element. The
 * version must be the same as the one written with the loaded relation, so restoring and comparing versions does
 * not change.
 *
 * @group model.dataobject.lazyrelation
 */
class LazyRelationVersionTest extends ModelTestCase
{
    private const RELATION_FIELDS = ['objects', 'multihref', 'href', 'objectswithmetadata'];

    private bool $inheritedValues = false;

    private bool $hideUnpublished = false;

    protected function setUp(): void
    {
        $this->inheritedValues = DataObject::getGetInheritedValues();
        $this->hideUnpublished = DataObject::getHideUnpublished();
        Version::enable();

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

    public function testSavingAnObjectDoesNotLoadUntouchedRelations(): void
    {
        $owner = $this->createOwnerWithAllRelationTypes();
        $relatedObjectIds = array_map(
            static fn (ElementInterface $element) => $element->getId(),
            $owner->getObjects()
        );

        $unloaded = $this->reload($owner);
        $unloaded->setInput('changed');

        $loadedIds = $this->recordLoadedObjectIds(static function () use ($unloaded): void {
            $unloaded->save();
        });

        $this->assertSame(
            [],
            array_values(array_intersect($loadedIds, $relatedObjectIds)),
            'no related object is loaded'
        );
        $this->assertFalse($unloaded->isLazyKeyLoaded('objects'), 'the relation is still not loaded');
        $this->assertFalse($unloaded->isLazyKeyLoaded('multihref'), 'the relation is still not loaded');

        $restored = $this->latestVersion($unloaded)->loadData();
        $this->assertSame('changed', $restored->getInput());
        $this->assertSame($this->relationValues($this->loadedCopy($owner)), $this->relationValues($restored));
    }

    public function testVersionOfUntouchedRelationsMatchesLoadedRelations(): void
    {
        $owner = $this->createOwnerWithAllRelationTypes();

        $this->assertVersionMatchesLoadedRelations($owner, ['objects', 'multihref']);
    }

    /**
     * The relation keeps unpublished elements, only the getter leaves them out. A version keeps them too.
     */
    public function testVersionKeepsUnpublishedElementsLikeTheLoadedRelation(): void
    {
        $unpublished = TestHelper::createEmptyObject('lazy-version-unpublished-', true, false);
        $owner = $this->createOwnerWithAllRelationTypes([$unpublished]);

        DataObject::setHideUnpublished(true);

        $restored = $this->assertVersionMatchesLoadedRelations($owner, ['objects', 'multihref']);
        $this->assertContains('object_' . $unpublished->getId(), $this->relationValues($restored)['objects']);
    }

    public function testVersionOmitsDeletedElementsLikeTheLoadedRelation(): void
    {
        $deleted = TestHelper::createEmptyObject('lazy-version-deleted-');
        $owner = $this->createOwnerWithAllRelationTypes([$deleted]);
        $deletedId = $deleted->getId();
        $deleted->delete();

        $restored = $this->assertVersionMatchesLoadedRelations($owner, []);
        $this->assertNotContains('object_' . $deletedId, $this->relationValues($restored)['objects']);
    }

    /**
     * An object relation only loads concrete objects, a stored relation to a folder is left out.
     */
    public function testVersionOmitsAFolderInAnObjectRelationLikeTheLoadedRelation(): void
    {
        $folder = TestHelper::createObjectFolder('lazy-version-folder-');
        $owner = $this->createOwnerWithAllRelationTypes();
        $this->insertObjectsRelationRow($owner, $folder->getId());

        $restored = $this->assertVersionMatchesLoadedRelations($owner, ['multihref']);
        $this->assertNotContains('object_' . $folder->getId(), $this->relationValues($restored)['objects']);
    }

    /**
     * A relation of the object to itself is stored as the object itself, not as a reference.
     */
    public function testVersionOfARelationToTheObjectItselfMatchesTheLoadedRelation(): void
    {
        $owner = $this->createOwnerWithAllRelationTypes();
        $owner->setMultihref(array_merge($owner->getMultihref(), [$owner]));
        $owner->save();

        $this->assertVersionMatchesLoadedRelations($owner, ['objects']);
    }

    /**
     * A version stores the own relations of an object, never the ones it inherits.
     */
    public function testVersionWithInheritedValuesMatchesLoadedRelations(): void
    {
        $target = TestHelper::createEmptyObject('lazy-version-inherited-target-');

        $parent = $this->createInheritance('lazy-version-parent-', 1);
        $parent->setRelationobjects([$target]);
        $parent->save();

        $child = $this->createInheritance('lazy-version-child-', $parent->getId());
        $child->save();

        foreach ([true, false] as $inheritedValues) {
            DataObject::setGetInheritedValues($inheritedValues);

            $restored = $this->assertVersionMatchesLoadedRelations(
                $child,
                ['relationobjects'],
                ['relationobjects']
            );
            $this->assertSame([], $restored->getObjectVar('relationobjects'));
        }
    }

    /**
     * A pre get value hook may return a value for the relation instead of loading it, the version stores what it
     * stored before.
     */
    public function testVersionWithAPreGetValueHookMatchesTheLoadedRelations(): void
    {
        $owner = $this->createOwnerWithAllRelationTypes();
        $hookedTarget = TestHelper::createEmptyObject('lazy-version-hooked-');
        $this->assertFalse(Pimcore::inAdmin(), 'precondition: the hook is only called outside of the admin');

        HookedUnittest::setHookedValue('objects', [$hookedTarget]);

        try {
            $hooked = HookedUnittest::fromStored($owner);
            $hooked->setModificationDate($owner->getModificationDate());
            $hooked->setUserModification($owner->getUserModification() ?? 0);
            $loaded = HookedUnittest::fromStored($owner);
            $loaded->setModificationDate($owner->getModificationDate());
            $loaded->setUserModification($owner->getUserModification() ?? 0);
            Service::loadAllObjectFields($loaded);

            $this->assertSame(
                $this->normalizedPayload($loaded->saveVersion(false)),
                $this->normalizedPayload($hooked->saveVersion(false))
            );
        } finally {
            HookedUnittest::resetHookedValues();
        }
    }

    /**
     * A listener of the deep copy may change how relations are copied into the version, they are loaded for it.
     */
    public function testVersionLoadsTheRelationsForAListenerOfTheDeepCopy(): void
    {
        $owner = $this->createOwnerWithAllRelationTypes();
        $listener = static function (): void {
        };
        $dispatcher = Pimcore::getEventDispatcher();
        $dispatcher->addListener(SystemEvents::SERVICE_PRE_GET_DEEP_COPY, $listener);

        try {
            $unloaded = $this->reload($owner);
            $unloaded->saveVersion(false);
        } finally {
            $dispatcher->removeListener(SystemEvents::SERVICE_PRE_GET_DEEP_COPY, $listener);
        }

        $this->assertTrue($unloaded->isLazyKeyLoaded('objects'), 'the relation is loaded like before');
    }

    /**
     * Writing into an existing version (e.g. when it is published) stores the relations the same way.
     */
    public function testWritingIntoAnExistingVersionKeepsTheRelations(): void
    {
        $owner = $this->createOwnerWithAllRelationTypes();
        $expected = $this->relationValues($this->loadedCopy($owner));

        $draft = $this->reload($owner)->saveVersion(false);
        $this->assertInstanceOf(Version::class, $draft);

        $unloaded = $this->reload($owner);
        $unloaded->setInput('published');
        $unloaded->save(['existingVersion' => $draft]);
        $this->assertFalse($unloaded->isLazyKeyLoaded('objects'), 'precondition: the relation is not loaded');

        $restored = Version::getById($draft->getId())->loadData();
        $this->assertSame('published', $restored->getInput());
        $this->assertSame($expected, $this->relationValues($restored));
    }

    /**
     * Writes a version of the object with its relations not loaded and one with its relations loaded, they must be
     * the same, and so must be what restoring them returns.
     *
     * @param list<string> $storedUnloaded the relations stored without loading them, the others are loaded
     * @param list<string> $relationFields
     */
    private function assertVersionMatchesLoadedRelations(
        Concrete $object,
        array $storedUnloaded,
        array $relationFields = self::RELATION_FIELDS
    ): Concrete {
        $unloaded = $this->reload($object);
        foreach ($relationFields as $field) {
            $this->assertFalse($unloaded->isLazyKeyLoaded($field), 'precondition: the relation is not loaded');
        }
        $unloadedVersion = $unloaded->saveVersion(false);
        foreach (array_intersect($relationFields, ['objects', 'multihref', 'relationobjects']) as $field) {
            $this->assertSame(
                !in_array($field, $storedUnloaded, true),
                $unloaded->isLazyKeyLoaded($field),
                $field . ' is stored ' . (in_array($field, $storedUnloaded, true) ? 'without loading it' : 'loaded')
            );
        }

        $loaded = $this->loadedCopy($object);
        $loadedVersion = $loaded->saveVersion(false);

        $this->assertSame($this->normalizedPayload($loadedVersion), $this->normalizedPayload($unloadedVersion));

        $restored = Version::getById($unloadedVersion->getId())->loadData();
        $restoredFromLoaded = Version::getById($loadedVersion->getId())->loadData();
        $this->assertSame(
            $this->relationValues($restoredFromLoaded, $relationFields),
            $this->relationValues($restored, $relationFields)
        );

        return $restored;
    }

    /**
     * The stored version data, serialized again with the bookkeeping of the loaded lazy keys sorted: the order in
     * which they were loaded depends on what was read before, a restored version marks all of them as loaded.
     */
    private function normalizedPayload(?Version $version): string
    {
        $this->assertInstanceOf(Version::class, $version);
        $payload = Serialize::unserialize(stream_get_contents($version->getFileStream()), true);
        $this->assertInstanceOf(Concrete::class, $payload);

        $reflection = new ReflectionProperty(Concrete::class, 'loadedLazyKeys');
        $loadedLazyKeys = $reflection->getValue($payload);
        ksort($loadedLazyKeys);
        $reflection->setValue($payload, $loadedLazyKeys);
        $payload->setInDumpState(true);

        return serialize($payload);
    }

    /**
     * The stored value of each relation field as a list of element type and id, the way a version keeps it.
     *
     * @param list<string> $relationFields
     *
     * @return array<string, list<string>|string|null>
     */
    private function relationValues(Concrete $object, array $relationFields = self::RELATION_FIELDS): array
    {
        $describe = static function (mixed $item) use ($object): ?string {
            if ($item instanceof ObjectMetadata) {
                return 'object_' . $item->getObjectId();
            }
            if ($item instanceof ElementInterface) {
                return ElementService::getElementType($item) . '_' . $item->getId()
                    . ($item === $object ? '_self' : '');
            }

            return $item === null ? null : get_debug_type($item);
        };

        $values = [];
        foreach ($relationFields as $field) {
            $value = $object->getObjectVar($field);
            $values[$field] = is_array($value) ? array_map($describe, $value) : $describe($value);
        }

        return $values;
    }

    /**
     * @param callable(): void $callback
     *
     * @return list<int>
     */
    private function recordLoadedObjectIds(callable $callback): array
    {
        $loadedIds = [];
        $record = static function (DataObjectEvent $event) use (&$loadedIds): void {
            $loadedIds[] = $event->getObject()->getId();
        };
        $dispatcher = Pimcore::getEventDispatcher();
        $dispatcher->addListener(DataObjectEvents::POST_LOAD, $record);

        try {
            RuntimeCache::clear();
            $callback();
        } finally {
            $dispatcher->removeListener(DataObjectEvents::POST_LOAD, $record);
        }

        return $loadedIds;
    }

    /**
     * @param list<Concrete> $additionalTargets
     */
    private function createOwnerWithAllRelationTypes(array $additionalTargets = []): Unittest
    {
        $objectTargets = TestHelper::createEmptyObjects('lazy-version-object-', true, 3);
        $metadataTarget = TestHelper::createEmptyObject('lazy-version-metadata-');
        $asset = TestHelper::createImageAsset('lazy-version-asset-');
        $document = TestHelper::createEmptyDocumentPage('lazy-version-document-');
        $objectFolder = TestHelper::createObjectFolder('lazy-version-multihref-folder-');

        $owner = TestHelper::createEmptyObject('lazy-version-owner-', false);
        $owner->setObjects(array_merge($objectTargets, $additionalTargets));
        $owner->setMultihref(array_merge([$asset, $document, $objectFolder], $objectTargets, $additionalTargets));
        $owner->setHref($document);
        $owner->setObjectswithmetadata(array_map(
            static fn (Concrete $target) => new ObjectMetadata('objectswithmetadata', ['meta1', 'meta2'], $target),
            array_merge([$metadataTarget], $additionalTargets)
        ));
        $owner->save();

        return $owner;
    }

    private function insertObjectsRelationRow(Concrete $owner, int $destinationId): void
    {
        $db = Db::get();
        $db->insert('object_relations_' . $owner->getClassId(), Db\Helper::quoteDataIdentifiers($db, [
            'src_id' => $owner->getId(),
            'dest_id' => $destinationId,
            'type' => 'object',
            'fieldname' => 'objects',
            'index' => 99,
            'ownertype' => 'object',
            'ownername' => '',
            'position' => '0',
        ]));
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

    private function latestVersion(Concrete $object): Version
    {
        $version = $object->getLatestVersion(null, true);
        $this->assertInstanceOf(Version::class, $version);

        return $version;
    }

    private function loadedCopy(Concrete $object): Concrete
    {
        $loaded = $this->reload($object);
        Service::loadAllObjectFields($loaded);

        return $loaded;
    }

    private function reload(Concrete $object): Concrete
    {
        RuntimeCache::clear();
        $reloaded = Concrete::getById($object->getId(), ['force' => true]);
        $this->assertInstanceOf(Concrete::class, $reloaded);

        return $reloaded;
    }
}
