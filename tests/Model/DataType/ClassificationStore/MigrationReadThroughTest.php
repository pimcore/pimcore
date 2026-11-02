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

use Pimcore\Db;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Csstore;

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

    private ?Classificationstore\CollectionConfig $collection = null;

    protected function tearDown(): void
    {
        $this->collection?->delete();
        $this->collection = null;

        parent::tearDown();
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

    private function createObjectWithValueAndCollection(): Csstore
    {
        $groupId = $this->getGroupId();

        $this->collection = new Classificationstore\CollectionConfig();
        $this->collection->setStoreId($this->getStoreId());
        $this->collection->setName(self::COLLECTION_NAME_PREFIX . uniqid());
        $this->collection->save();

        $relation = new Classificationstore\CollectionGroupRelation();
        $relation->setColId($this->collection->getId());
        $relation->setGroupId($groupId);
        $relation->save();

        $object = new Csstore();
        $object->setParentId(1);
        $object->setKey('read-through');
        $object->setPublished(true);

        $store = $object->getCsstore();
        $store->setLocalizedKeyValue($groupId, $this->getKeyId(self::INPUT_KEY), self::VALUE);
        $store->setGroupCollectionMapping($groupId, (int) $this->collection->getId());
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
