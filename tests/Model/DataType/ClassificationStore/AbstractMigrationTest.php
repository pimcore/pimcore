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
use Pimcore;
use Pimcore\Db;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineResolver;
use Pimcore\Model\DataObject\Csstore;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * The fixture every test of the flip/drain/verify/cleanup route shares: the shipped test store and
 * the `csstore` class, which start on the EAV engine so that a test can write values the way an
 * installation holds them today and then flip the store over them.
 *
 * The store is shared across the suite, so the engine is put back on EAV in tearDown rather than
 * being left wherever the last test needed it.
 */
abstract class AbstractMigrationTest extends AbstractClassificationStoreTest
{
    use MigrationServiceTrait;

    protected const STORE_NAME = 'teststore';

    protected const CLASS_NAME = 'csstore';

    protected const FIELDNAME = 'csstore';

    protected const DOCUMENT_ENGINE = 'document';

    protected const DOCUMENT_TABLE_PREFIX = 'object_classificationstore_doc_';

    protected const DATA_TABLE_PREFIX = 'object_classificationstore_data_';

    protected const GROUPS_TABLE_PREFIX = 'object_classificationstore_groups_';

    /**
     * The group the shipped fixture puts the `input` key in - the keys of
     * {@see AbstractClassificationStoreTest::configureStore()} after the first three.
     */
    protected const SECOND_GROUP = 'testgroup2';

    protected const FIRST_GROUP = 'testgroup1';

    /**
     * Two keys of the second group, used wherever a test only needs a value it can recognise again.
     */
    protected const INPUT_KEY = 'input';

    protected const TEXTAREA_KEY = 'textarea';

    protected function setUp(): void
    {
        parent::setUp();

        Pimcore::setAdminMode();
        TestHelper::cleanUp();
    }

    protected function tearDown(): void
    {
        $this->putStoreBackOnEav();

        TestHelper::cleanUp();

        parent::tearDown();
    }

    /**
     * The store starts on the EAV engine: a migration test writes the values an installation holds
     * today and flips the store over them.
     */
    protected function getStoreEngine(): ?string
    {
        return null;
    }

    protected function getStore(): Classificationstore\StoreConfig
    {
        $store = Classificationstore\StoreConfig::getByName(self::STORE_NAME);
        $this->assertInstanceOf(Classificationstore\StoreConfig::class, $store);

        return $store;
    }

    protected function getStoreId(): int
    {
        return (int) $this->getStore()->getId();
    }

    protected function getClass(): ClassDefinition
    {
        $class = ClassDefinition::getByName(self::CLASS_NAME);
        $this->assertInstanceOf(ClassDefinition::class, $class);

        return $class;
    }

    protected function getClassId(): string
    {
        return $this->getClass()->getId();
    }

    /**
     * Flips the shared store the way the command does, through the service.
     */
    protected function flipStore(): void
    {
        $this->getMigrationService()->flip($this->getStore());
    }

    /**
     * Writes the engine column directly, the way a hand edit or another process does, and drops the
     * memo the resolver holds.
     */
    protected function setStoredEngine(?string $engine): void
    {
        Db::get()->update('classificationstore_stores', ['engine' => $engine], ['id' => $this->getStoreId()]);

        Pimcore::getContainer()->get(EngineResolver::class)->reset();
    }

    protected function getStoredEngine(): ?string
    {
        $engine = Db::get()->fetchOne(
            'SELECT engine FROM classificationstore_stores WHERE id = ?',
            [$this->getStoreId()]
        );

        return $engine === false || $engine === null ? null : (string) $engine;
    }

    protected function getGroupId(string $name = self::SECOND_GROUP): int
    {
        $group = Classificationstore\GroupConfig::getByName($name);
        $this->assertInstanceOf(Classificationstore\GroupConfig::class, $group);

        return (int) $group->getId();
    }

    protected function getKeyId(string $name): int
    {
        $key = Classificationstore\KeyConfig::getByName($name, $this->getStoreId());
        $this->assertInstanceOf(Classificationstore\KeyConfig::class, $key);

        return (int) $key->getId();
    }

    /**
     * An object of the test class carrying one value, written through whichever engine the store is
     * on at the time.
     */
    protected function createObjectWithValue(string $key, mixed $value, string $objectKey): Csstore
    {
        $object = new Csstore();
        $object->setParentId(1);
        $object->setKey($objectKey);
        $object->setPublished(true);
        $object->getCsstore()->setLocalizedKeyValue($this->getGroupId(), $this->getKeyId($key), $value);
        $object->save();

        return $object;
    }

    protected function reload(int $objectId): Csstore
    {
        $object = Csstore::getById($objectId, ['force' => true]);
        $this->assertInstanceOf(Csstore::class, $object);

        return $object;
    }

    /**
     * The pairs a class still has to convert. The service streams them in bounded batches, so what
     * a test wants - the whole of a backlog it seeded itself - is collected here.
     *
     * @return array<array{0: int, 1: string}>
     */
    protected function pendingPairs(ClassDefinition $class): array
    {
        return iterator_to_array($this->getMigrationService()->streamBacklog($class), false);
    }

    protected function getDocumentTable(): string
    {
        return self::DOCUMENT_TABLE_PREFIX . $this->getClassId();
    }

    protected function getDataTable(): string
    {
        return self::DATA_TABLE_PREFIX . $this->getClassId();
    }

    protected function getGroupsTable(): string
    {
        return self::GROUPS_TABLE_PREFIX . $this->getClassId();
    }

    /**
     * Inserts one row with its column names quoted, which a document row has to have: `values` is a
     * reserved word, and Doctrine's `insert()` puts the column names into the statement as they are
     * - so a document row written through that errors with a syntax error rather than being written.
     *
     * @param array<string, mixed> $row column => value
     */
    protected function insertRow(Connection $db, string $table, array $row): void
    {
        $columns = array_map($db->quoteIdentifier(...), array_keys($row));

        $db->executeStatement(
            'INSERT INTO ' . $db->quoteIdentifier($table) . ' (' . implode(', ', $columns) . ')'
            . ' VALUES (' . implode(', ', array_fill(0, count($row), '?')) . ')',
            array_values($row)
        );
    }

    protected function countRows(string $table, ?string $fieldname = null): int
    {
        if (!$this->tableExists($table)) {
            return 0;
        }

        $sql = 'SELECT COUNT(*) FROM ' . Db::get()->quoteIdentifier($table);
        $parameters = [];

        if ($fieldname !== null) {
            $sql .= ' WHERE fieldname = ?';
            $parameters[] = $fieldname;
        }

        return (int) Db::get()->fetchOne($sql, $parameters);
    }

    /**
     * @return array<array<string, mixed>>
     */
    protected function readDocumentRows(?int $objectId = null): array
    {
        if (!$this->tableExists($this->getDocumentTable())) {
            return [];
        }

        $sql = 'SELECT * FROM ' . Db::get()->quoteIdentifier($this->getDocumentTable());
        $parameters = [];

        if ($objectId !== null) {
            $sql .= ' WHERE id = ?';
            $parameters[] = $objectId;
        }

        return Db::get()->fetchAllAssociative($sql . ' ORDER BY id, fieldname, groupId', $parameters);
    }

    protected function tableExists(string $table): bool
    {
        return Db::get()->fetchOne('SHOW TABLES LIKE ?', [$table]) !== false;
    }

    protected function dropTable(string $table): void
    {
        Db::get()->executeStatement('DROP TABLE IF EXISTS ' . Db::get()->quoteIdentifier($table));
    }

    protected function dropDocumentTable(): void
    {
        $this->dropTable($this->getDocumentTable());
    }

    /**
     * Puts the shared store and the value tables of the test class back the way the next test class
     * expects them: on the EAV engine, with both EAV tables present.
     */
    private function putStoreBackOnEav(): void
    {
        $store = Classificationstore\StoreConfig::getByName(self::STORE_NAME);
        if (!$store) {
            return;
        }

        if ($store->getEngine() !== null) {
            $store->setEngine(null);
            $store->save();
        } else {
            $this->setStoredEngine(null);
        }

        $this->createValueTables($store, self::CLASS_NAME);
    }
}
