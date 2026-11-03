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

use Closure;
use Pimcore\Db;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Csstore;
use Pimcore\Model\DataObject\Csstorelocalized;
use Pimcore\Model\DataObject\Service as DataObjectService;

/**
 * Read-through: the values of a flipped store are still the EAV rows until the drain reaches them,
 * and a pair that holds a document is read from the document alone. Derived, never merged, and
 * nothing records which of the two answered.
 *
 * @group model.datatype.classificationstore
 */
class MigrationReadThroughTest extends AbstractMigrationTest
{
    private const VALUE = 'written while the store was on eav';

    private const COLLECTION_NAME_PREFIX = 'read_through_collection_';

    /**
     * The class that allows inheritance, which is what the three-generation case below needs; the
     * shipped `csstore` class does not.
     */
    private const INHERITING_CLASS_NAME = 'csstorelocalized';

    private ?Classificationstore\CollectionConfig $collection = null;

    protected function tearDown(): void
    {
        $this->collection?->delete();
        $this->collection = null;

        parent::tearDown();
    }

    /**
     * The inheriting class is set up here as well: the store starts on EAV, so this is where its
     * EAV tables come from, and the flip creates its document table.
     */
    protected function setUpTestClasses(): void
    {
        parent::setUpTestClasses();

        $store = Classificationstore\StoreConfig::getByName(self::STORE_NAME);
        if (!$store) {
            return;
        }

        $this->tester->setupPimcoreClass_CsstoreLocalized(['storeId' => $store->getId()]);
        $this->createValueTables($store, self::INHERITING_CLASS_NAME);
    }

    /**
     * A pair with no document row answers its values, its active groups and its collection mapping
     * from the EAV tables.
     */
    public function testAPairWithNoDocumentReadsTheEavTables(): void
    {
        $object = $this->createObjectWithValueAndCollection();
        $groupId = $this->getGroupId();

        $this->flipStore();
        $this->assertSame([], $this->readDocumentRows((int) $object->getId()));

        $store = $this->reload((int) $object->getId())->getCsstore();

        $this->assertSame(self::VALUE, $store->getLocalizedKeyValue($groupId, $this->getKeyId(self::INPUT_KEY)));
        $this->assertSame([$groupId => true], $store->getActiveGroups());
        $this->assertEquals(
            [$groupId => $this->collection?->getId()],
            $store->getGroupCollectionMappings()
        );
    }

    /**
     * Once the pair holds a document it is read from the document alone: an EAV row that appears
     * beside it afterwards is not merged in, and no column records that the pair ever read through.
     */
    public function testAPairWithADocumentNeverFallsBackToTheEavTables(): void
    {
        $object = $this->createObjectWithValueAndCollection();
        $objectId = (int) $object->getId();
        $groupId = $this->getGroupId();

        $this->flipStore();

        // the save writes the pair's documents, which is what takes it off the read-through
        $reloaded = $this->reload($objectId);
        $reloaded->getCsstore()->setLocalizedKeyValue($groupId, $this->getKeyId(self::INPUT_KEY), 'now a document');
        $reloaded->save();

        $this->assertNotSame([], $this->readDocumentRows($objectId));
        $this->assertSame(0, $this->countRows($this->getDataTable(), self::FIELDNAME));
        $this->assertSame(0, $this->countRows($this->getGroupsTable(), self::FIELDNAME));

        // an EAV row written out of band after the pair was converted is simply not read
        $otherGroupId = $this->getGroupId(self::FIRST_GROUP);
        $this->seedEavRow($objectId, $otherGroupId, $this->getKeyId('date'), 'out of band');

        $store = $this->reload($objectId)->getCsstore();

        $this->assertSame([$groupId => true], $store->getActiveGroups());
        $this->assertSame('now a document', $store->getLocalizedKeyValue($groupId, $this->getKeyId(self::INPUT_KEY)));
        $this->assertNull($store->getLocalizedKeyValue($otherGroupId, $this->getKeyId('date')));
    }

    /**
     * The fallback is derived rather than stored: the document table carries no column that says
     * where a pair's values came from.
     */
    public function testNothingRecordsThatAPairReadsThroughToTheEavTables(): void
    {
        $this->flipStore();
        $this->getMigrationService()->createDocumentTable($this->getClass());

        $columns = Db::get()->fetchFirstColumn(
            'SELECT COLUMN_NAME FROM information_schema.COLUMNS'
            . ' WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION',
            [$this->getDocumentTable()]
        );

        $this->assertSame(
            ['id', 'fieldname', 'groupId', 'collectionId', 'values', 'checksum', 'migratedAt'],
            array_map(strval(...), $columns)
        );
    }

    /**
     * A store put on the document engine by hand is on it before any class carrying it has been
     * saved, so the table its reads address does not exist yet. That reads as an empty store
     * instead of raising table-not-found.
     */
    public function testAnAbsentDocumentTableReadsAsAnEmptyStore(): void
    {
        $object = $this->createObjectWithoutValues();
        $objectId = (int) $object->getId();

        $this->dropDocumentTable();
        $this->setStoredEngine(self::DOCUMENT_ENGINE);

        $store = $this->reload($objectId)->getCsstore();

        $this->assertSame([], $store->getActiveGroups());
        $this->assertSame([], $store->getItems());
    }

    /**
     * And neither does the read-through itself: a class whose EAV tables were never created, or
     * whose tables the cleanup has dropped, reads as an empty store as well.
     */
    public function testAnAbsentEavTableReadsAsAnEmptyStoreToo(): void
    {
        $object = $this->createObjectWithoutValues();
        $objectId = (int) $object->getId();

        $this->dropDocumentTable();
        $this->dropTable($this->getDataTable());
        $this->dropTable($this->getGroupsTable());
        $this->setStoredEngine(self::DOCUMENT_ENGINE);

        $store = $this->reload($objectId)->getCsstore();

        $this->assertSame([], $store->getActiveGroups());
        $this->assertSame([], $store->getItems());
    }

    /**
     * Deleting an object's classification store values takes the EAV rows of a pair the drain has
     * not converted yet along with the documents. A delete that took the documents alone would be
     * undone by the read-through on the very next load, which would hand back the values that were
     * just deleted.
     */
    public function testDeletingAnUnconvertedPairTakesItsEavRowsSoTheReadThroughDoesNotServeThemAgain(): void
    {
        $object = $this->createObjectWithValueAndCollection();
        $objectId = (int) $object->getId();
        $groupId = $this->getGroupId();

        $this->flipStore();
        $this->assertSame([], $this->readDocumentRows($objectId), 'precondition: the pair is not converted');
        $this->assertGreaterThan(0, $this->countRows($this->getDataTable(), self::FIELDNAME));

        $this->reload($objectId)->getCsstore()->getDao()->delete();

        $this->assertSame(0, $this->countRows($this->getDataTable(), self::FIELDNAME));
        $this->assertSame(0, $this->countRows($this->getGroupsTable(), self::FIELDNAME));

        $store = $this->reload($objectId)->getCsstore();
        $this->assertSame([], $store->getActiveGroups());
        $this->assertNull($store->getLocalizedKeyValue($groupId, $this->getKeyId(self::INPUT_KEY)));
    }

    /**
     * And the delete reaches a class whose EAV tables were never created - a store born on the
     * document engine - without raising table-not-found over the tables that are not there.
     */
    public function testDeletingSucceedsWhenTheEavTablesAreGone(): void
    {
        $object = $this->createObjectWithoutValues();
        $objectId = (int) $object->getId();

        $this->flipStore();
        $this->dropTable($this->getDataTable());
        $this->dropTable($this->getGroupsTable());

        $this->reload($objectId)->getCsstore()->getDao()->delete();

        $this->assertSame([], $this->readDocumentRows($objectId));
    }

    /**
     * Three generations across both engines: a grandparent whose pair is converted, a parent whose
     * pair still reads through its EAV rows, and a child mapping the group to the same collection
     * the grandparent maps it to.
     *
     * The parent's EAV rows map the group to nothing, which the EAV engine reads back as an entry
     * holding null - and an entry is what it is: it shadows the grandparent's collection, so the
     * child inherits nothing for that group and its mapping is its own to store. A walk of the
     * chain that dropped the parent's null would find the grandparent's collection behind it,
     * leave the child's mapping out of its document as inherited, and the child would then read
     * back the parent's null instead of the collection it was saved with.
     */
    public function testAnUndrainedParentMappedToNoCollectionShadowsTheGrandparent(): void
    {
        $groupId = $this->getGroupId();
        $keyId = $this->getKeyId(self::INPUT_KEY);
        $collectionId = $this->createCollection($groupId);

        $this->assertTrue(
            $this->getInheritingClass()->getAllowInherit(),
            'the case needs a class whose values are inheritable'
        );

        // the grandparent maps the group, and has no inheritable parent to answer the mapping for it
        $grandparent = $this->createInheritingObject(1, 'inherit-grandparent', function (
            Classificationstore $store
        ) use ($groupId, $keyId, $collectionId): void {
            $store->setLocalizedKeyValue($groupId, $keyId, 'grandparent');
            $store->setGroupCollectionMapping($groupId, $collectionId);
        });

        // the parent holds values in the group and no mapping of its own, which is the state the
        // EAV engine records as a collection of null
        $parent = $this->createInheritingObject((int) $grandparent->getId(), 'inherit-parent', function (
            Classificationstore $store
        ) use ($groupId, $keyId): void {
            $store->setLocalizedKeyValue($groupId, $keyId, 'parent');
        });
        $parentId = (int) $parent->getId();

        $this->flipStore();

        // the grandparent is saved once more, which is what takes its pair over to the documents;
        // the parent is left where it is, so the chain the child's save walks runs through both
        // engines
        $grandparentId = (int) $grandparent->getId();
        $resaved = $this->reloadInheriting($grandparentId);
        $resaved->getCsstore()->setLocalizedKeyValue($groupId, $keyId, 'grandparent, on documents');
        $resaved->save();

        $grandparentRows = $this->readInheritingDocumentRows($grandparentId);
        $this->assertCount(1, $grandparentRows, 'precondition: the grandparent holds its document');
        $this->assertSame(
            $collectionId,
            (int) $grandparentRows[0]['collectionId'],
            'precondition: and it is the one mapping the group, having no inheritable parent itself'
        );

        $this->assertSame(
            [],
            $this->readInheritingDocumentRows($parentId),
            'precondition: the parent holds no document and reads through'
        );
        $this->assertSame(
            [$groupId => null],
            $this->reloadInheriting($parentId)->getCsstore()->getGroupCollectionMappings(),
            'precondition: its EAV rows map the group to nothing'
        );

        $child = $this->createInheritingObject($parentId, 'inherit-child', function (
            Classificationstore $store
        ) use ($groupId, $keyId, $collectionId): void {
            $store->setLocalizedKeyValue($groupId, $keyId, 'child');
            $store->setGroupCollectionMapping($groupId, $collectionId);
        });
        $childId = (int) $child->getId();

        $rows = $this->readInheritingDocumentRows($childId);
        $this->assertCount(1, $rows);
        $this->assertSame(
            $collectionId,
            (int) $rows[0]['collectionId'],
            'the nearest ancestor holding the group answers null, so the mapping is the child\'s own to store'
        );

        $reloaded = $this->reloadInheriting($childId);
        $this->assertSame(
            $collectionId,
            DataObjectService::useInheritedValues(
                true,
                static fn (): ?int => $reloaded->getCsstore()->getGroupCollectionMapping($groupId)
            ),
            'and it reads back on the collection it was saved with'
        );
    }

    private function getInheritingClass(): ClassDefinition
    {
        $class = ClassDefinition::getByName(self::INHERITING_CLASS_NAME);
        $this->assertInstanceOf(ClassDefinition::class, $class);

        return $class;
    }

    /**
     * @param Closure(Classificationstore): void $configure
     */
    private function createInheritingObject(int $parentId, string $key, Closure $configure): Csstorelocalized
    {
        $object = new Csstorelocalized();
        $object->setParentId($parentId);
        $object->setKey($key);
        $object->setPublished(true);

        $configure($object->getCsstore());
        $object->save();

        return $object;
    }

    private function reloadInheriting(int $objectId): Csstorelocalized
    {
        $object = Csstorelocalized::getById($objectId, ['force' => true]);
        $this->assertInstanceOf(Csstorelocalized::class, $object);

        return $object;
    }

    /**
     * @return array<array<string, mixed>>
     */
    private function readInheritingDocumentRows(int $objectId): array
    {
        return Db::get()->fetchAllAssociative(
            'SELECT * FROM ' . Db::get()->quoteIdentifier(
                self::DOCUMENT_TABLE_PREFIX . $this->getInheritingClass()->getId()
            ) . ' WHERE id = ? AND fieldname = ? ORDER BY groupId',
            [$objectId, self::FIELDNAME]
        );
    }

    /**
     * A collection of the test store, related to the given group.
     */
    private function createCollection(int $groupId): int
    {
        $this->collection = new Classificationstore\CollectionConfig();
        $this->collection->setStoreId($this->getStoreId());
        $this->collection->setName(self::COLLECTION_NAME_PREFIX . uniqid());
        $this->collection->save();

        $relation = new Classificationstore\CollectionGroupRelation();
        $relation->setColId($this->collection->getId());
        $relation->setGroupId($groupId);
        $relation->save();

        return (int) $this->collection->getId();
    }

    private function createObjectWithValueAndCollection(): Csstore
    {
        $groupId = $this->getGroupId();
        $collectionId = $this->createCollection($groupId);

        $object = new Csstore();
        $object->setParentId(1);
        $object->setKey('read-through');
        $object->setPublished(true);

        $store = $object->getCsstore();
        $store->setLocalizedKeyValue($groupId, $this->getKeyId(self::INPUT_KEY), self::VALUE);
        $store->setGroupCollectionMapping($groupId, $collectionId);
        $object->save();

        return $object;
    }

    private function createObjectWithoutValues(): Csstore
    {
        $object = new Csstore();
        $object->setParentId(1);
        $object->setKey('read-through-empty');
        $object->setPublished(true);
        $object->save();

        return $object;
    }

    /**
     * Writes a group activation row and one value row for it, the way the EAV engine would.
     */
    private function seedEavRow(int $objectId, int $groupId, int $keyId, string $value): void
    {
        $db = Db::get();

        $db->insert($this->getGroupsTable(), [
            'id' => $objectId,
            'groupId' => $groupId,
            'fieldname' => self::FIELDNAME,
        ]);
        $db->insert($this->getDataTable(), [
            'id' => $objectId,
            'groupId' => $groupId,
            'keyId' => $keyId,
            'fieldname' => self::FIELDNAME,
            'language' => 'default',
            'type' => 'input',
            'value' => $value,
        ]);
    }
}
