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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Pimcore;
use Pimcore\Db;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data\EncryptedField as EncryptedFieldDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Classificationstore\Engine\DocumentEngine;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineResolver;
use Pimcore\Model\DataObject\Csstore;
use Pimcore\Model\DataObject\Data\EncryptedField;
use ReflectionClass;

/**
 * The document engine: one row per object, field and group, the presence of that row being what
 * makes the group active, and a save that writes only the groups whose document changed.
 *
 * @group model.datatype.classificationstore
 */
class DocumentEngineTest extends AbstractBatchedRelationTest
{
    private const FIELDNAME = 'csstore';

    private const DOCUMENT_ENGINE = 'document';

    private const EMPTY_DOCUMENT = '{}';

    private const DEFAULT_LANGUAGE = 'default';

    private const BULK_KEY_COUNT = 300;

    private const BULK_LANGUAGES = [self::DEFAULT_LANGUAGE, 'en', 'de'];

    /**
     * 900 values in one group are one document, so the write is a single statement; the budget is
     * the one the EAV engine's diff-based save is held to, kept so the two are comparable.
     */
    private const WRITE_STATEMENT_BUDGET = 10;

    private const VALUE_1 = 'one';

    private const VALUE_2 = 'two';

    private const VALUE_3 = 'three';

    private const SECRET = 'a-secret-nobody-may-read';

    private Classificationstore\GroupConfig $groupA;

    private Classificationstore\GroupConfig $groupB;

    private Classificationstore\KeyConfig $key1;

    private Classificationstore\KeyConfig $key2;

    private Classificationstore\KeyConfig $key3;

    private ?Classificationstore\CollectionConfig $collection = null;

    private Csstore $object;

    protected function getStoreEngine(): ?string
    {
        return self::DOCUMENT_ENGINE;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->groupA = $this->createGroup('doc_a');
        $this->groupB = $this->createGroup('doc_b');
        $this->key1 = $this->createKey('doc_k1');
        $this->key2 = $this->createKey('doc_k2');
        $this->key3 = $this->createKey('doc_k3');

        $this->assignKey($this->groupA, $this->key1, 0);
        $this->assignKey($this->groupA, $this->key2, 1);
        $this->assignKey($this->groupB, $this->key3, 0);

        $this->collection = new Classificationstore\CollectionConfig();
        $this->collection->setStoreId($this->getStoreId());
        $this->collection->setName('doc_collection_' . uniqid());
        $this->collection->save();

        $collectionRelation = new Classificationstore\CollectionGroupRelation();
        $collectionRelation->setColId($this->collection->getId());
        $collectionRelation->setGroupId($this->groupA->getId());
        $collectionRelation->save();

        $this->object = new Csstore();
        $this->object->setParentId(1);
        $this->object->setKey('document-engine');
        $this->object->setPublished(true);

        $store = $this->object->getCsstore();
        $store->setLocalizedKeyValue($this->groupA->getId(), $this->key1->getId(), self::VALUE_1);
        $store->setLocalizedKeyValue($this->groupA->getId(), $this->key2->getId(), self::VALUE_2);
        $this->object->save();
    }

    protected function tearDown(): void
    {
        $this->collection?->delete();
        $this->collection = null;

        parent::tearDown();
    }

    /**
     * The store resolves to the document engine, which is the precondition of everything below -
     * otherwise the counts and the rows asserted here would be the EAV engine's.
     */
    public function testTheStoreRunsOnTheDocumentEngine(): void
    {
        $this->assertInstanceOf(
            DocumentEngine::class,
            Pimcore::getContainer()->get(EngineResolver::class)->resolveForStore($this->getStoreId())
        );

        $this->assertFalse(
            (new ReflectionClass(Classificationstore\Dao::class))->hasMethod('persistDocuments'),
            'the Dao holds no write path of its own, so the statements counted came from the engine'
        );
    }

    /**
     * The presence of a row is the active group: a group activated without a value stores the
     * empty document, and deactivating it deletes the row. No group table is involved.
     */
    public function testActivatingAndDeactivatingAGroupIsTheRowItself(): void
    {
        $this->saveStore(function (Classificationstore $store): void {
            $store->setActiveGroups($store->getActiveGroups() + [$this->groupB->getId() => true]);
        });

        $this->assertSame(self::EMPTY_DOCUMENT, $this->storedDocument($this->groupB->getId()));
        $this->assertSame(
            [$this->groupA->getId(), $this->groupB->getId()],
            $this->storedGroupIds()
        );

        $this->assertArrayHasKey(
            $this->groupB->getId(),
            $this->reloadStore()->getActiveGroups(),
            'a group whose document is empty is still active'
        );

        $this->saveStore(function (Classificationstore $store): void {
            $store->setActiveGroups([$this->groupA->getId() => true]);
        });

        $this->assertSame([$this->groupA->getId()], $this->storedGroupIds());
        $this->assertNull($this->storedDocument($this->groupB->getId()));
    }

    public function testChangingTheCollectionMapping(): void
    {
        $collectionId = $this->collection->getId();

        // a mapping change alone does not mark the store dirty, so the save has to run regardless
        $this->saveStore(function (Classificationstore $store) use ($collectionId): void {
            $store->setGroupCollectionMapping($this->groupA->getId(), $collectionId);
        }, true);

        $this->assertSame($collectionId, $this->storedCollectionId($this->groupA->getId()));
        $this->assertSame(
            [$this->groupA->getId() => $collectionId],
            $this->reloadStore()->getGroupCollectionMappings()
        );

        $this->saveStore(function (Classificationstore $store): void {
            $store->setGroupCollectionMappings([]);
        }, true);

        $this->assertNull($this->storedCollectionId($this->groupA->getId()));
        $this->assertSame([], $this->reloadStore()->getGroupCollectionMappings());
    }

    /**
     * A group that belongs to a collection needs no placeholder value of any kind - the row is
     * already there - and a mapping that an inheritable parent answers is not written on the child.
     */
    public function testAnInheritedCollectionMappingIsNotWrittenOnTheChild(): void
    {
        $collectionId = $this->collection->getId();
        $groupId = $this->groupA->getId();

        $parent = new DataObject\Csstorelocalized();
        $parent->setParentId(1);
        $parent->setKey('document-engine-parent');
        $parent->setPublished(true);
        $parent->getCsstore()->setLocalizedKeyValue($groupId, $this->key1->getId(), self::VALUE_1);
        $parent->getCsstore()->setGroupCollectionMapping($groupId, $collectionId);
        $parent->save();

        $child = new DataObject\Csstorelocalized();
        $child->setParentId($parent->getId());
        $child->setKey('document-engine-child');
        $child->setPublished(true);
        $child->getCsstore()->setLocalizedKeyValue($groupId, $this->key1->getId(), self::VALUE_2);
        $child->getCsstore()->setGroupCollectionMapping($groupId, $collectionId);
        $child->save();

        $table = 'object_classificationstore_doc_' . $child->getClass()->getId();
        $storedOnTheChild = Db::get()->fetchOne(
            'SELECT `collectionId` FROM `' . $table . '` WHERE `id` = ? AND `fieldname` = ? AND `groupId` = ?',
            [$child->getId(), self::FIELDNAME, $groupId]
        );

        $this->assertNull($storedOnTheChild, 'the mapping the parent already answers is not stored on the child');
        $this->assertSame(
            $collectionId,
            (int) Db::get()->fetchOne(
                'SELECT `collectionId` FROM `' . $table . '` WHERE `id` = ? AND `fieldname` = ? AND `groupId` = ?',
                [$parent->getId(), self::FIELDNAME, $groupId]
            ),
            'the parent, which has no inheritable parent of its own, stores it'
        );

        $reloaded = DataObject\Csstorelocalized::getById($child->getId(), ['force' => true]);

        $this->assertSame(
            $collectionId,
            DataObject\Service::useInheritedValues(
                true,
                static fn (): ?int => $reloaded->getCsstore()->getGroupCollectionMapping($groupId)
            ),
            'the child still reports the mapping, through the parent'
        );
    }

    public function testASaveWithoutDirtyFieldsIssuesNoStatement(): void
    {
        $store = $this->reloadStore();

        $this->startMeasurement();
        $store->save();

        $this->assertSame(
            0,
            $this->countStatements(),
            'the dirty-field early return skips the whole save, including the re-read'
        );
    }

    public function testAnUnchangedSaveWithoutDirtyDetectionWritesNothing(): void
    {
        $store = $this->reloadStore();

        DataObject::disableDirtyDetection();

        try {
            $this->startMeasurement();
            $store->save();
        } finally {
            DataObject::enableDirtyDetection();
        }

        $this->assertGreaterThan(0, $this->countReadStatements(), 'the diff reads the stored documents back');
        $this->assertSame(0, $this->countWriteStatements(), 'an unchanged store writes nothing');
    }

    /**
     * One changed value rewrites its own group's document - that is what one document per group
     * means - and leaves the document of the other group byte for byte as it was.
     */
    public function testChangingOneValueWritesOneGroupAndLeavesTheOtherUntouched(): void
    {
        $keyConfigs = $this->seedBulkKeys();
        $untouchedBefore = $this->storedDocument($this->groupA->getId());

        $store = $this->reloadStore();
        $store->setLocalizedKeyValue($this->groupB->getId(), $keyConfigs[0]->getId(), 'second');

        $this->startMeasurement();
        $store->save();

        $this->assertLessThan(
            self::WRITE_STATEMENT_BUDGET,
            $this->countWriteStatements(),
            'one changed value out of 900 must stay inside the budget the EAV engine is held to'
        );

        $this->assertSame(
            $untouchedBefore,
            $this->storedDocument($this->groupA->getId()),
            'the document of the group that did not change is not rewritten'
        );
    }

    /**
     * The document is canonical, which is what makes the comparison above possible: the same items
     * reached in another order serialize to the very same string.
     */
    public function testTheSameItemsSerializeToTheSameDocumentWhateverTheirOrder(): void
    {
        $groupId = $this->groupA->getId();

        $this->saveStore(function (Classificationstore $store) use ($groupId): void {
            $store->setItems([]);
            $store->setLocalizedKeyValue($groupId, $this->key1->getId(), self::VALUE_1, 'en');
            $store->setLocalizedKeyValue($groupId, $this->key1->getId(), self::VALUE_1, self::DEFAULT_LANGUAGE);
            $store->setLocalizedKeyValue($groupId, $this->key2->getId(), self::VALUE_2);
        }, true);

        $oneOrder = $this->storedDocument($groupId);

        // the second round has to be a write, not a save the diff skips, or the two strings would
        // be equal because nothing was written rather than because they serialize alike
        $this->saveStore(function (Classificationstore $store) use ($groupId): void {
            $store->setItems([]);
            $store->setLocalizedKeyValue($groupId, $this->key2->getId(), self::VALUE_3);
        }, true);

        $this->assertNotSame($oneOrder, $this->storedDocument($groupId));

        $this->saveStore(function (Classificationstore $store) use ($groupId): void {
            $store->setItems([]);
            $store->setLocalizedKeyValue($groupId, $this->key2->getId(), self::VALUE_2);
            $store->setLocalizedKeyValue($groupId, $this->key1->getId(), self::VALUE_1, self::DEFAULT_LANGUAGE);
            $store->setLocalizedKeyValue($groupId, $this->key1->getId(), self::VALUE_1, 'en');
        }, true);

        $this->assertSame($oneOrder, $this->storedDocument($groupId));
        $this->assertStringNotContainsString(' ', (string) $oneOrder, 'the document carries no whitespace');
    }

    /**
     * The save decides against what the table holds rather than against the snapshot its
     * transaction opened with, so a document another connection committed in between is overwritten
     * rather than mistaken for the one this save is about to write.
     */
    public function testAValueCommittedAfterTheSnapshotIsStillOverwritten(): void
    {
        $groupId = $this->groupA->getId();
        $connection = Db::get();
        $otherConnection = $this->openSecondConnection();

        $store = $this->reloadStore();
        $store->setLocalizedKeyValue($groupId, $this->key2->getId(), 'changed');

        $connection->beginTransaction();

        try {
            // establishes the snapshot of this transaction
            $ownDocument = $this->grabDocument($connection, $groupId);
            $this->assertStringContainsString(self::VALUE_1, (string) $ownDocument);

            $otherConnection->update(
                $this->getDocumentTableName(),
                ['`values`' => self::EMPTY_DOCUMENT],
                ['id' => $this->object->getId(), 'fieldname' => self::FIELDNAME, 'groupId' => $groupId]
            );

            $this->assertSame(self::EMPTY_DOCUMENT, $this->grabDocument($otherConnection, $groupId));
            $this->assertSame(
                $ownDocument,
                $this->grabDocument($connection, $groupId),
                'the snapshot of this transaction still reports the document it read first'
            );

            $store->save();

            $connection->commit();
        } finally {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }
            $otherConnection->close();
        }

        $reloaded = $this->reloadStore();

        $this->assertSame(
            self::VALUE_1,
            $reloaded->getLocalizedKeyValue($groupId, $this->key1->getId()),
            'the concurrently emptied document was overwritten, not skipped'
        );
        $this->assertSame('changed', $reloaded->getLocalizedKeyValue($groupId, $this->key2->getId()));
    }

    /**
     * The primary key of the EAV value table reads the language under a case-insensitive collation,
     * so two spellings of one language are one value there. The document reproduces that: the last
     * of them is kept, with its own spelling.
     */
    public function testLanguageAliasesKeepTheLastEntry(): void
    {
        $groupId = $this->groupA->getId();

        $this->saveStore(function (Classificationstore $store) use ($groupId): void {
            $store->setItems([$groupId => [$this->key1->getId() => ['EN' => 'uppercase', 'en' => 'lowercase']]]);
        });

        $reloaded = $this->reloadStore();

        $this->assertSame('lowercase', $reloaded->getLocalizedKeyValue($groupId, $this->key1->getId(), 'en'));
        $this->assertStringNotContainsString('uppercase', (string) $this->storedDocument($groupId));

        $this->saveStore(function (Classificationstore $store) use ($groupId): void {
            $store->setItems([$groupId => [$this->key1->getId() => ['EN' => 'uppercase']]]);
        });

        $this->assertStringContainsString('"EN"', (string) $this->storedDocument($groupId));
        $this->assertStringNotContainsString('lowercase', (string) $this->storedDocument($groupId));
    }

    public function testDeletingTheObjectRemovesEveryDocumentOfIt(): void
    {
        $this->saveStore(function (Classificationstore $store): void {
            $store->setLocalizedKeyValue($this->groupB->getId(), $this->key3->getId(), self::VALUE_3);
        });

        $this->assertCount(2, $this->storedGroupIds());

        $objectId = $this->object->getId();
        $this->object->delete();

        $this->assertSame(
            0,
            (int) Db::get()->fetchOne(
                'SELECT COUNT(*) FROM `' . $this->getDocumentTableName() . '` WHERE `id` = ?',
                [$objectId]
            )
        );
    }

    /**
     * Deleting a store removes the documents of every class whose group belongs to it, and leaves
     * the documents of another store where they are.
     */
    public function testDeletingAStoreRemovesOnlyItsOwnDocuments(): void
    {
        $otherStore = new Classificationstore\StoreConfig();
        $otherStore->setName('doc_other_store_' . uniqid());
        $otherStore->setEngine(self::DOCUMENT_ENGINE);
        $otherStore->save();

        try {
            $engine = Pimcore::getContainer()->get(EngineResolver::class)->getEngines()[self::DOCUMENT_ENGINE];
            $engine->deleteStoreValues((int) $otherStore->getId());

            $this->assertCount(
                1,
                $this->storedGroupIds(),
                'the documents of another store are not touched'
            );

            $engine->deleteStoreValues($this->getStoreId());

            $this->assertSame([], $this->storedGroupIds());
        } finally {
            $otherStore->delete();
        }
    }

    /**
     * The sweep removes the documents of a field name the class definition no longer carries, and
     * leaves the ones it does.
     */
    public function testCleanupRemovesTheDocumentsOfFieldsThatNoLongerExist(): void
    {
        $table = $this->getDocumentTableName();

        Db::get()->insert($table, [
            'id' => $this->object->getId(),
            'fieldname' => 'csgone',
            'groupId' => $this->groupB->getId(),
            '`values`' => self::EMPTY_DOCUMENT,
        ]);

        Pimcore::getContainer()->get(EngineResolver::class)->getEngines()[self::DOCUMENT_ENGINE]->cleanup();

        $this->assertSame(
            0,
            (int) Db::get()->fetchOne('SELECT COUNT(*) FROM `' . $table . '` WHERE `fieldname` = ?', ['csgone'])
        );
        $this->assertCount(1, $this->storedGroupIds(), 'the documents of the field that exists stay');
    }

    /**
     * Deleting a group configuration destroys its stored values, through the cascading foreign key
     * the document table declares - the same outcome the EAV engine's group table produces.
     */
    public function testDeletingAGroupConfigRemovesItsDocuments(): void
    {
        $this->saveStore(function (Classificationstore $store): void {
            $store->setLocalizedKeyValue($this->groupB->getId(), $this->key3->getId(), self::VALUE_3);
        });

        $this->assertCount(2, $this->storedGroupIds());

        $groupBId = $this->groupB->getId();
        $this->groupB->delete();

        $this->assertSame([$this->groupA->getId()], $this->storedGroupIds());
        $this->assertNull($this->storedDocument($groupBId));
    }

    /**
     * The `encryptedField` marshaller is the one carve-out from storing normalized data: it
     * encrypts, so skipping it would write the secret into the document in cleartext.
     */
    public function testAnEncryptedValueIsNotReadableInTheDocument(): void
    {
        $delegate = new Input();
        $delegate->setName('doc_secret');

        $definition = new EncryptedFieldDefinition();
        $definition->setDelegateDatatype('input');
        $definition->setDelegate($delegate);

        $encryptedKey = $this->createKey('doc_secret', true, $definition);
        $this->assignKey($this->groupA, $encryptedKey, 2);

        $groupId = $this->groupA->getId();

        $this->saveStore(function (Classificationstore $store) use ($groupId, $encryptedKey, $delegate): void {
            $store->setLocalizedKeyValue(
                $groupId,
                $encryptedKey->getId(),
                new EncryptedField($delegate, self::SECRET)
            );
        });

        $this->assertStringNotContainsString(
            self::SECRET,
            (string) $this->storedDocument($groupId),
            'the cleartext must not be readable in the stored document'
        );

        $loaded = $this->reloadStore()->getLocalizedKeyValue($groupId, $encryptedKey->getId());

        $this->assertInstanceOf(EncryptedField::class, $loaded);
        $this->assertSame(self::SECRET, $loaded->getPlain());
    }

    /**
     * 300 keys in 3 languages in one group, beside the group the fixture already populated.
     *
     * @return Classificationstore\KeyConfig[]
     */
    private function seedBulkKeys(): array
    {
        $keyConfigs = [];

        $store = $this->reloadStore();
        for ($index = 0; $index < self::BULK_KEY_COUNT; $index++) {
            $keyConfig = $this->createKey('doc_bulk_' . $index);
            $this->assignKey($this->groupB, $keyConfig, $index);
            $keyConfigs[] = $keyConfig;

            foreach (self::BULK_LANGUAGES as $language) {
                $store->setLocalizedKeyValue($this->groupB->getId(), $keyConfig->getId(), 'first', $language);
            }
        }
        $store->save();

        return $keyConfigs;
    }

    private function saveStore(callable $change, bool $withoutDirtyDetection = false): void
    {
        $store = $this->reloadStore();
        $change($store);

        if (!$withoutDirtyDetection) {
            $store->save();

            return;
        }

        DataObject::disableDirtyDetection();

        try {
            $store->save();
        } finally {
            DataObject::enableDirtyDetection();
        }
    }

    private function reloadStore(): Classificationstore
    {
        $this->object = Csstore::getById($this->object->getId(), ['force' => true]);

        return $this->object->getCsstore();
    }

    private function openSecondConnection(): Connection
    {
        return DriverManager::getConnection(Db::get()->getParams());
    }

    private function grabDocument(Connection $connection, int $groupId): ?string
    {
        $values = $connection->fetchOne(
            'SELECT `values` FROM `' . $this->getDocumentTableName() . '`'
            . ' WHERE `id` = ? AND `fieldname` = ? AND `groupId` = ?',
            [$this->object->getId(), self::FIELDNAME, $groupId]
        );

        return $values === false ? null : (string) $values;
    }

    private function storedDocument(int $groupId): ?string
    {
        return $this->grabDocument(Db::get(), $groupId);
    }

    private function storedCollectionId(int $groupId): ?int
    {
        $collectionId = Db::get()->fetchOne(
            'SELECT `collectionId` FROM `' . $this->getDocumentTableName() . '`'
            . ' WHERE `id` = ? AND `fieldname` = ? AND `groupId` = ?',
            [$this->object->getId(), self::FIELDNAME, $groupId]
        );

        return $collectionId === false || $collectionId === null ? null : (int) $collectionId;
    }

    /**
     * @return int[]
     */
    private function storedGroupIds(): array
    {
        return array_map('intval', Db::get()->fetchFirstColumn(
            'SELECT `groupId` FROM `' . $this->getDocumentTableName() . '`'
            . ' WHERE `id` = ? AND `fieldname` = ? ORDER BY `groupId`',
            [$this->object->getId(), self::FIELDNAME]
        ));
    }

    private function getDocumentTableName(): string
    {
        return 'object_classificationstore_doc_' . $this->object->getClass()->getId();
    }
}
