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
use Pimcore\Bundle\CoreBundle\Migrations\Version20261105100000;
use Pimcore\Cache;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Db;
use Pimcore\Model\DataObject\Classificationstore\Engine\DocumentEngine;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineResolver;
use Pimcore\Model\DataObject\Csstore;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Tests\Support\Util\TestHelper;
use Psr\Log\NullLogger;

/**
 * What a relation-typed key of a document store actually puts on disk: the normalized descriptor
 * rather than a resolved element, the per-relation column values of the advanced pair in the
 * per-class metadata table, and the dirty marking that makes a changed or a dead descriptor reach
 * the next save.
 *
 * @group model.datatype.classificationstore
 */
class DocumentRelationTest extends AbstractClassificationStoreTest
{
    use RelationFixtureTrait;

    private const FIELDNAME = 'csstore';

    private const CLASS_NAME = 'csstore';

    private const DOCUMENT_TABLE = 'object_classificationstore_doc_csstore';

    private const METADATA_TABLE = 'object_classificationstore_meta_csstore';

    private const KEY_PREFIX = 'cs-document-relation-';

    private const DEFAULT_LANGUAGE = 'default';

    protected function getStoreEngine(): ?string
    {
        return 'document';
    }

    protected function setUp(): void
    {
        parent::setUp();

        Pimcore::setAdminMode();
        Cache::disable();
        TestHelper::cleanUp();
    }

    protected function tearDown(): void
    {
        $this->deleteRelationFixture();
        TestHelper::cleanUp();

        parent::tearDown();
    }

    public function testTheDocumentHoldsTheNormalizedDescriptorAndNotAResolvedElement(): void
    {
        $group = $this->createRelationFixture(['csManyToManyObject']);
        $keyId = $this->getRelationKeyId('csManyToManyObject');
        $target = TestHelper::createEmptyObject(self::KEY_PREFIX);

        $object = $this->createCsObject();
        $object->getCsstore()->setLocalizedKeyValue((int) $group->getId(), $keyId, [$target]);
        $object->save();

        $this->assertSame(
            json_encode([['type' => 'object', 'id' => $target->getId()]]),
            json_encode($this->readStoredValue($object->getId(), (int) $group->getId(), $keyId))
        );
    }

    /**
     * The save path writes the descriptor it loaded back as it is, so the document is unchanged and
     * no target was instantiated to produce it.
     */
    public function testSavingAnUnreadRelationKeyRewritesNothingAndInstantiatesNoTarget(): void
    {
        $group = $this->createRelationFixture(['csManyToManyObject']);
        $keyId = $this->getRelationKeyId('csManyToManyObject');
        $target = TestHelper::createEmptyObject(self::KEY_PREFIX);

        $object = $this->createCsObject();
        $object->getCsstore()->setLocalizedKeyValue((int) $group->getId(), $keyId, [$target]);
        $object->save();

        $before = $this->readDocument($object->getId(), (int) $group->getId());

        RuntimeCache::clear();
        $reloaded = Csstore::getById($object->getId(), ['force' => true]);
        $reloaded->getCsstore()->getItems();
        $reloaded->save();

        $this->assertSame($before, $this->readDocument($object->getId(), (int) $group->getId()));
        $this->assertFalse(
            RuntimeCache::isRegistered('object_' . $target->getId()),
            'no target may be instantiated by a save that never read the key'
        );
    }

    public function testClearingARelationKeyTakesItOutOfTheDocument(): void
    {
        $group = $this->createRelationFixture(['csManyToManyObject']);
        $keyId = $this->getRelationKeyId('csManyToManyObject');
        $target = TestHelper::createEmptyObject(self::KEY_PREFIX);

        $object = $this->createCsObject();
        $object->getCsstore()->setLocalizedKeyValue((int) $group->getId(), $keyId, [$target]);
        $object->save();

        $this->assertNotNull($this->readStoredValue($object->getId(), (int) $group->getId(), $keyId));

        $reloaded = Csstore::getById($object->getId(), ['force' => true]);
        $reloaded->getCsstore()->setLocalizedKeyValue((int) $group->getId(), $keyId, null);
        $reloaded->save();

        $this->assertNull($this->readStoredValue($object->getId(), (int) $group->getId(), $keyId));
    }

    /**
     * The group is already active, so setActiveGroups() sees no change and the store is dirty only
     * because the relation value was replaced.
     */
    public function testReplacingTheTargetOfAnAlreadyActiveGroupReachesTheNextSave(): void
    {
        $group = $this->createRelationFixture(['csManyToManyObject']);
        $keyId = $this->getRelationKeyId('csManyToManyObject');
        $first = TestHelper::createEmptyObject(self::KEY_PREFIX . 'first');
        $second = TestHelper::createEmptyObject(self::KEY_PREFIX . 'second');

        $object = $this->createCsObject();
        $object->getCsstore()->setLocalizedKeyValue((int) $group->getId(), $keyId, [$first]);
        $object->save();

        $loaded = Csstore::getById($object->getId(), ['force' => true]);
        $loaded->getCsstore()->setLocalizedKeyValue((int) $group->getId(), $keyId, [$second]);
        $loaded->save();

        $read = Csstore::getById($object->getId(), ['force' => true])
            ->getCsstore()
            ->getLocalizedKeyValue((int) $group->getId(), $keyId);

        $this->assertSame([$second->getId()], $this->idsOf($read));
    }

    /**
     * The read answers without the dead descriptor and writes nothing; the store is dirty, so the
     * next ordinary save is what takes the descriptor out of the document.
     */
    public function testADescriptorWhoseTargetIsGoneIsDroppedOnReadAndRewrittenOnTheNextSave(): void
    {
        $group = $this->createRelationFixture(['csManyToManyObject']);
        $keyId = $this->getRelationKeyId('csManyToManyObject');
        $kept = TestHelper::createEmptyObject(self::KEY_PREFIX . 'kept');
        $removed = TestHelper::createEmptyObject(self::KEY_PREFIX . 'removed');

        $object = $this->createCsObject();
        $object->getCsstore()->setLocalizedKeyValue((int) $group->getId(), $keyId, [$kept, $removed]);
        $object->save();

        $storedBefore = $this->readDocument($object->getId(), (int) $group->getId());
        $removed->delete();

        $loaded = Csstore::getById($object->getId(), ['force' => true]);
        $store = $loaded->getCsstore();
        $read = $store->getLocalizedKeyValue((int) $group->getId(), $keyId);

        $this->assertSame([$kept->getId()], $this->idsOf($read));
        $this->assertSame(
            $storedBefore,
            $this->readDocument($object->getId(), (int) $group->getId()),
            'the read itself must write nothing'
        );
        $this->assertTrue($store->isFieldDirty('_self'), 'a dropped descriptor marks the store dirty');

        $loaded->save();

        $this->assertSame(
            [['type' => 'object', 'id' => $kept->getId()]],
            $this->readStoredValue($object->getId(), (int) $group->getId(), $keyId)
        );
    }

    /**
     * The per-relation column values are addressed by (objectId, fieldname, groupId, keyId,
     * language, index, column), and the value the read answers with is the one the table holds -
     * the two predictors are asserted over the same fixture so that they cannot drift apart.
     */
    public function testThePerRelationColumnValuesAreStoredInThePerClassMetadataTableAndReadBack(): void
    {
        $group = $this->createRelationFixture(['csAdvancedManyToManyObject']);
        $keyId = $this->getRelationKeyId('csAdvancedManyToManyObject');
        $targets = [
            TestHelper::createEmptyObject(self::KEY_PREFIX . 'a'),
            TestHelper::createEmptyObject(self::KEY_PREFIX . 'b'),
        ];

        $items = [];
        foreach ($targets as $index => $target) {
            $item = new ObjectMetadata('csAdvancedManyToManyObject', [self::RELATION_COLUMN], $target);
            $item->setData([self::RELATION_COLUMN => 'column-' . $index]);
            $items[] = $item;
        }

        $object = $this->createCsObject();
        $object->getCsstore()->setLocalizedKeyValue((int) $group->getId(), $keyId, $items);
        $object->save();

        $this->assertSame(
            [
                ['index' => 0, 'column' => self::RELATION_COLUMN, 'data' => 'column-0'],
                ['index' => 1, 'column' => self::RELATION_COLUMN, 'data' => 'column-1'],
            ],
            $this->readMetadata($object->getId(), (int) $group->getId(), $keyId)
        );

        $read = Csstore::getById($object->getId(), ['force' => true])
            ->getCsstore()
            ->getLocalizedKeyValue((int) $group->getId(), $keyId);

        $this->assertCount(2, $read);

        foreach ($read as $index => $item) {
            $this->assertSame('column-' . $index, $item->getData()[self::RELATION_COLUMN]);
        }
    }

    /**
     * The document addresses a value by the alias of its language, and the primary key of the
     * metadata table equates two spellings the same way. The per-relation column values therefore
     * have to be written under the alias as well: under the raw spelling the two spellings collide
     * on one row, and the document and its metadata disagree about which language the value is in.
     */
    public function testTwoSpellingsOfOneLanguageAddressOneSetOfPerRelationColumnValues(): void
    {
        $group = $this->createRelationFixture(['csAdvancedManyToManyObject']);
        $keyId = $this->getRelationKeyId('csAdvancedManyToManyObject');
        $target = TestHelper::createEmptyObject(self::KEY_PREFIX);

        $object = $this->createCsObject();
        $store = $object->getCsstore();
        $store->setLocalizedKeyValue((int) $group->getId(), $keyId, [$this->metadataItem($target, 'uppercase')], 'EN');
        $store->setLocalizedKeyValue((int) $group->getId(), $keyId, [$this->metadataItem($target, 'lowercase')], 'en');
        $object->save();

        $this->assertSame(
            ['en'],
            $this->storedMetadataLanguages($object->getId(), (int) $group->getId(), $keyId),
            'the two spellings are one coordinate, held under the alias the document uses'
        );

        $read = Csstore::getById($object->getId(), ['force' => true])
            ->getCsstore()
            ->getLocalizedKeyValue((int) $group->getId(), $keyId, 'en');

        $this->assertSame('lowercase', $read[0]->getData()[self::RELATION_COLUMN]);
    }

    /**
     * The read path treats an absent metadata table as a class whose documents predate it and holds
     * its per-relation column values in the document alone. The write path has to agree: a save
     * that found the table gone would otherwise take the documents it had already written down with
     * it, and the object could be loaded but never saved again.
     */
    public function testASaveSurvivesAnAbsentMetadataTableAndKeepsTheValuesInTheDocument(): void
    {
        $group = $this->createRelationFixture(['csAdvancedManyToManyObject']);
        $keyId = $this->getRelationKeyId('csAdvancedManyToManyObject');
        $target = TestHelper::createEmptyObject(self::KEY_PREFIX . 'no-table');

        Db::get()->executeStatement('DROP TABLE ' . Db::get()->quoteIdentifier(self::METADATA_TABLE));

        try {
            $object = $this->createCsObject();
            $object->getCsstore()->setLocalizedKeyValue(
                (int) $group->getId(),
                $keyId,
                [$this->metadataItem($target, 'in-the-document')]
            );
            $object->save();

            $read = Csstore::getById($object->getId(), ['force' => true])
                ->getCsstore()
                ->getLocalizedKeyValue((int) $group->getId(), $keyId);

            $this->assertCount(1, $read);
            $this->assertSame($target->getId(), $read[0]->getElement()->getId());
            $this->assertSame(
                'in-the-document',
                $read[0]->getData()[self::RELATION_COLUMN],
                'with no table to overlay, the column values the document carries are the value'
            );
        } finally {
            $this->getDocumentEngine()->createMetadataTable(self::CLASS_NAME);
        }
    }

    public function testTheMetadataTableIsCreatedBesideTheDocumentTable(): void
    {
        $this->assertNotFalse(
            Db::get()->fetchOne('SHOW TABLES LIKE ?', [self::METADATA_TABLE]),
            'the class save has to create the metadata table of a document store'
        );

        $this->assertSame(
            ['id', 'fieldname', 'groupId', 'keyId', 'language', 'index', 'column'],
            Db::get()->fetchFirstColumn(
                'SELECT COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE'
                . " WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = 'PRIMARY'"
                . ' ORDER BY ORDINAL_POSITION',
                [self::METADATA_TABLE]
            )
        );
    }

    /**
     * The migration is what gives the table to a class whose document table already exists, and it
     * declares the same schema {@see DocumentEngine::createMetadataTable()} does - so it is asserted
     * to produce the same columns and the same primary key as the test above.
     *
     * Only the statements addressing this class's table are run. The migration covers every class
     * holding documents, and executing its `down()` wholesale would drop the tables of the classes
     * the rest of the suite is using.
     */
    public function testTheMigrationAddsTheMetadataTableAndTakesItBack(): void
    {
        $db = Db::get();
        $guarded = new Version20261105100000($db, new NullLogger());
        $guarded->up($db->createSchemaManager()->introspectSchema());

        $this->assertSame(
            [],
            $this->statementsForThisClass($guarded),
            'a class that already has the table must be left alone by the hasTable() guard'
        );

        $db->executeStatement('DROP TABLE ' . $db->quoteIdentifier(self::METADATA_TABLE));

        try {
            $up = new Version20261105100000($db, new NullLogger());
            $up->up($db->createSchemaManager()->introspectSchema());
            $this->runStatements($up);

            $this->assertNotFalse(
                $db->fetchOne('SHOW TABLES LIKE ?', [self::METADATA_TABLE]),
                'up() has to create the table of a class that already holds documents'
            );
            $this->assertSame(
                ['id', 'fieldname', 'groupId', 'keyId', 'language', 'index', 'column', 'data'],
                $db->fetchFirstColumn(
                    'SELECT COLUMN_NAME FROM information_schema.COLUMNS'
                    . ' WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION',
                    [self::METADATA_TABLE]
                ),
                'the migration and the engine have to declare the same columns'
            );
            $this->assertSame(
                ['id', 'fieldname', 'groupId', 'keyId', 'language', 'index', 'column'],
                $db->fetchFirstColumn(
                    'SELECT COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE'
                    . " WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = 'PRIMARY'"
                    . ' ORDER BY ORDINAL_POSITION',
                    [self::METADATA_TABLE]
                ),
                'and the same primary key'
            );

            $down = new Version20261105100000($db, new NullLogger());
            $down->down($db->createSchemaManager()->introspectSchema());
            $this->runStatements($down);

            $this->assertFalse(
                $db->fetchOne('SHOW TABLES LIKE ?', [self::METADATA_TABLE]),
                'down() has to take the table back'
            );
        } finally {
            $this->getDocumentEngine()->createMetadataTable(self::CLASS_NAME);
        }
    }

    /**
     * The statements of the migration that address the metadata table of this class, matched on the
     * quoted name so that the table of a class whose id merely starts with this one is not taken
     * for it.
     *
     * @return string[]
     */
    private function statementsForThisClass(Version20261105100000 $migration): array
    {
        $statements = [];

        foreach ($migration->getSql() as $query) {
            if (str_contains($query->getStatement(), '`' . self::METADATA_TABLE . '`')) {
                $statements[] = $query->getStatement();
            }
        }

        return $statements;
    }

    private function runStatements(Version20261105100000 $migration): void
    {
        $statements = $this->statementsForThisClass($migration);

        $this->assertNotSame([], $statements, 'the migration has to address the table of this class');

        foreach ($statements as $statement) {
            Db::get()->executeStatement($statement);
        }
    }

    private function getDocumentEngine(): DocumentEngine
    {
        $resolver = Pimcore::getContainer()->get(EngineResolver::class);
        $this->assertInstanceOf(EngineResolver::class, $resolver);

        $engine = $resolver->getEngines()['document'] ?? null;
        $this->assertInstanceOf(DocumentEngine::class, $engine);

        return $engine;
    }

    private function createCsObject(): Csstore
    {
        $object = new Csstore();
        $object->setParentId(1);
        $object->setKey(self::KEY_PREFIX . 'owner');
        $object->setPublished(true);
        $object->save();

        return $object;
    }

    /**
     * The decoded value one key holds in the default language, or null when the document does not
     * carry that key at all.
     */
    private function readStoredValue(int $objectId, int $groupId, int $keyId): mixed
    {
        $document = json_decode($this->readDocument($objectId, $groupId), true);
        $entries = $document['k'][$keyId] ?? null;

        return $entries === null ? null : ($entries[0]['d'] ?? null);
    }

    private function readDocument(int $objectId, int $groupId): string
    {
        return (string) Db::get()->fetchOne(
            'SELECT `values` FROM ' . Db::get()->quoteIdentifier(self::DOCUMENT_TABLE)
            . ' WHERE `id` = ? AND `fieldname` = ? AND `groupId` = ?',
            [$objectId, self::FIELDNAME, $groupId]
        );
    }

    /**
     * @return array<array{index: int, column: string, data: string}>
     */
    private function readMetadata(int $objectId, int $groupId, int $keyId): array
    {
        $rows = Db::get()->fetchAllAssociative(
            'SELECT `index`, `column`, `data` FROM ' . Db::get()->quoteIdentifier(self::METADATA_TABLE)
            . ' WHERE `id` = ? AND `fieldname` = ? AND `groupId` = ? AND `keyId` = ? AND `language` = ?'
            . ' ORDER BY `index`, `column`',
            [$objectId, self::FIELDNAME, $groupId, $keyId, self::DEFAULT_LANGUAGE]
        );

        return array_map(static fn (array $row): array => [
            'index' => (int) $row['index'],
            'column' => (string) $row['column'],
            'data' => (string) $row['data'],
        ], $rows);
    }

    private function metadataItem(ElementInterface $target, string $data): ObjectMetadata
    {
        $item = new ObjectMetadata('csAdvancedManyToManyObject', [self::RELATION_COLUMN], $target);
        $item->setData([self::RELATION_COLUMN => $data]);

        return $item;
    }

    /**
     * The spellings the stored rows of one key hold. One row per column of one entry is written, so
     * a key carrying one column and one entry has to come out as exactly one spelling: two of them
     * would be two spellings of one language, which the primary key of the table cannot tell apart.
     *
     * @return string[]
     */
    private function storedMetadataLanguages(int $objectId, int $groupId, int $keyId): array
    {
        return Db::get()->fetchFirstColumn(
            'SELECT `language` FROM ' . Db::get()->quoteIdentifier(self::METADATA_TABLE)
            . ' WHERE `id` = ? AND `fieldname` = ? AND `groupId` = ? AND `keyId` = ?'
            . ' ORDER BY `index`, `column`',
            [$objectId, self::FIELDNAME, $groupId, $keyId]
        );
    }

    /**
     * @param ElementInterface[] $elements
     *
     * @return int[]
     */
    private function idsOf(array $elements): array
    {
        return array_map(static fn (ElementInterface $element): int => $element->getId(), $elements);
    }
}
