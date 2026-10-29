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

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Pimcore\Bundle\CoreBundle\Migrations\Version20261028100000;
use Pimcore\Db;
use Pimcore\Model\DataObject\Classificationstore\CollectionConfig;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig\Listing as GroupConfigListing;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig\Listing as KeyConfigListing;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig;
use Pimcore\Model\Exception\InvalidConfigException;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Psr\Log\NullLogger;

/**
 * @group model.classificationstore.identifier
 */
final class IdentifierTest extends ModelTestCase
{
    use ClassificationStoreAutoIncrementTrait;

    private const TABLE_STORES = 'classificationstore_stores';

    private const TABLE_COLLECTIONS = 'classificationstore_collections';

    private const TABLE_GROUPS = 'classificationstore_groups';

    private const TABLE_KEYS = 'classificationstore_keys';

    private const STORE_SCOPED_TABLES = [self::TABLE_COLLECTIONS, self::TABLE_GROUPS, self::TABLE_KEYS];

    private const COLUMN_IDENTIFIER = 'identifier';

    private const NAME_HEIGHT = 'height';

    private array $createdStoreIds = [];

    private array $createdRows = [];

    protected function tearDown(): void
    {
        $db = Db::get();

        foreach ($this->createdRows as [$table, $id]) {
            $db->delete($table, ['id' => $id]);
        }

        foreach ($this->createdStoreIds as $storeId) {
            foreach (self::STORE_SCOPED_TABLES as $table) {
                $db->delete($table, ['storeId' => $storeId]);
            }
            $db->delete(self::TABLE_STORES, ['id' => $storeId]);
        }

        $this->createdRows = [];
        $this->createdStoreIds = [];
        $this->restoreClassificationStoreAutoIncrement();

        parent::tearDown();
    }

    public function testIdentifierColumnIsNullableOnEveryTable(): void
    {
        $schemaManager = Db::get()->createSchemaManager();

        foreach ([self::TABLE_STORES, ...self::STORE_SCOPED_TABLES] as $table) {
            $columns = $schemaManager->listTableColumns($table);

            $this->assertArrayHasKey(self::COLUMN_IDENTIFIER, $columns, $table . ' must carry an identifier column');
            $this->assertFalse(
                $columns[self::COLUMN_IDENTIFIER]->getNotnull(),
                $table . '.identifier must be nullable'
            );
            $this->assertSame(190, $columns[self::COLUMN_IDENTIFIER]->getLength());
        }

        $this->assertContains(
            self::COLUMN_IDENTIFIER,
            (new KeyConfig())->getDao()->getValidTableColumns(self::TABLE_KEYS),
            'The valid table columns cache must know the identifier column'
        );
    }

    public function testMigrationDownDropsEveryIdentifierColumn(): void
    {
        $db = Db::get();
        $migration = new Version20261028100000($db, new NullLogger());
        $migration->down($db->createSchemaManager()->introspectSchema());

        $planned = array_map(static fn ($query) => $query->getStatement(), $migration->getSql());

        foreach ([self::TABLE_STORES, ...self::STORE_SCOPED_TABLES] as $table) {
            $this->assertContains(
                'ALTER TABLE `' . $table . '` DROP INDEX `'
                . ($table === self::TABLE_STORES ? 'identifier' : 'storeId_identifier')
                . '`, DROP COLUMN `identifier`;',
                $planned,
                $table . ' must be reverted by down()'
            );
        }
    }

    public function testUniquenessIsEnforcedInTheDatabase(): void
    {
        $schemaManager = Db::get()->createSchemaManager();

        $storeIndexes = $schemaManager->listTableIndexes(self::TABLE_STORES);
        $this->assertTrue($storeIndexes[self::COLUMN_IDENTIFIER]->isUnique());
        $this->assertSame([self::COLUMN_IDENTIFIER], $storeIndexes[self::COLUMN_IDENTIFIER]->getColumns());

        foreach (self::STORE_SCOPED_TABLES as $table) {
            $indexes = $schemaManager->listTableIndexes($table);
            $this->assertTrue($indexes['storeid_identifier']->isUnique(), $table . ' must scope uniqueness per store');
            $this->assertSame(['storeId', self::COLUMN_IDENTIFIER], $indexes['storeid_identifier']->getColumns());
        }
    }

    public function testAnyNumberOfNullIdentifiersIsLegal(): void
    {
        $db = Db::get();
        $store = $this->createStore();

        foreach (self::STORE_SCOPED_TABLES as $table) {
            for ($i = 0; $i < 3; $i++) {
                $db->insert($table, ['storeId' => $store->getId(), 'name' => 'nullid' . $i]);
            }

            $this->assertSame(
                3,
                (int) $db->fetchOne(
                    'SELECT COUNT(*) FROM ' . $table . ' WHERE storeId = ? AND identifier IS NULL',
                    [$store->getId()]
                ),
                $table . ' must accept any number of NULL identifiers'
            );
        }

        for ($i = 0; $i < 3; $i++) {
            $db->insert(self::TABLE_STORES, ['name' => uniqid('nullidstore', true)]);
            $this->createdStoreIds[] = (int) $db->lastInsertId();
        }

        $this->assertSame(
            3,
            (int) $db->fetchOne(
                'SELECT COUNT(*) FROM ' . self::TABLE_STORES . ' WHERE id IN (?, ?, ?) AND identifier IS NULL',
                array_slice($this->createdStoreIds, -3)
            )
        );
    }

    public function testDuplicateIdentifierIsRejectedPerScope(): void
    {
        $db = Db::get();
        $store = $this->createStore();
        $otherStore = $this->createStore();

        foreach (self::STORE_SCOPED_TABLES as $table) {
            $db->insert($table, ['storeId' => $store->getId(), 'name' => 'first', self::COLUMN_IDENTIFIER => 'shared']);

            try {
                $db->insert(
                    $table,
                    ['storeId' => $store->getId(), 'name' => 'second', self::COLUMN_IDENTIFIER => 'shared']
                );
                $this->fail($table . ' must reject a duplicate identifier in the same store');
            } catch (UniqueConstraintViolationException $e) {
                $this->addToAssertionCount(1);
            }

            $db->insert(
                $table,
                ['storeId' => $otherStore->getId(), 'name' => 'third', self::COLUMN_IDENTIFIER => 'shared']
            );
            $this->assertSame(
                2,
                (int) $db->fetchOne('SELECT COUNT(*) FROM ' . $table . ' WHERE identifier = ?', ['shared']),
                $table . ' must allow the same identifier in a different store'
            );
        }

        $this->createStore('sharedstore');

        try {
            $db->insert(self::TABLE_STORES, ['name' => uniqid('dup', true), self::COLUMN_IDENTIFIER => 'sharedstore']);
            $this->fail('The stores table must reject a duplicate identifier');
        } catch (UniqueConstraintViolationException $e) {
            $this->addToAssertionCount(1);
        }
    }

    public static function rejectedIdentifierProvider(): array
    {
        return [['a.b'], ['-lead'], ['_lead'], [''], ['ä'], ["valid-id\n"], [str_repeat('a', 191)]];
    }

    /**
     * @dataProvider rejectedIdentifierProvider
     */
    public function testSetIdentifierRejectsInvalidValues(string $identifier): void
    {
        foreach ($this->newConfigs() as $config) {
            try {
                $config->setIdentifier($identifier);
                $this->fail(sprintf('%s must reject the identifier "%s"', $config::class, $identifier));
            } catch (InvalidConfigException $e) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public static function acceptedIdentifierProvider(): array
    {
        return [['EF000001'], ['a'], ['A-b_C9']];
    }

    /**
     * @dataProvider acceptedIdentifierProvider
     */
    public function testSetIdentifierAcceptsValidValues(string $identifier): void
    {
        foreach ($this->newConfigs() as $config) {
            $config->setIdentifier($identifier);
            $this->assertSame($identifier, $config->getIdentifier());
        }
    }

    public function testIdentifierIsImmutableOnceSet(): void
    {
        $store = $this->createStore();

        $key = new KeyConfig();
        $key->setStoreId($store->getId());
        $key->setName('immutable');
        $key->setIdentifier('A');
        $key->save();

        $saved = KeyConfig::getById($key->getId(), true);
        $this->assertSame('A', $saved->getIdentifier());

        try {
            $saved->setIdentifier('B');
            $this->fail('A non-null identifier must not be changed');
        } catch (InvalidConfigException $e) {
            $this->addToAssertionCount(1);
        }

        $saved->setIdentifier('A');
        $this->assertSame('A', $saved->getIdentifier());

        $unresolvedId = $this->insertKeyRowWithoutIdentifier($store->getId(), 'unresolved');
        $unresolved = KeyConfig::getById($unresolvedId, true);
        $this->assertNull($unresolved->getIdentifier());

        $unresolved->setIdentifier('C');
        $unresolved->save();

        $this->assertSame('C', $this->fetchIdentifier(self::TABLE_KEYS, $unresolvedId));
    }

    public function testStaleInstanceNeitherClearsNorReplacesAnIdentifier(): void
    {
        $store = $this->createStore();

        foreach ([self::TABLE_STORES, ...self::STORE_SCOPED_TABLES] as $table) {
            $id = $this->insertRowWithoutIdentifier($table, $store->getId(), 'stale');

            // three instances of the same identifier-less row, all loaded before any of them saves
            $assigning = $this->loadConfig($table, $id);
            $clearing = $this->loadConfig($table, $id);
            $replacing = $this->loadConfig($table, $id);

            $assigning->setIdentifier('assigned-first');
            $assigning->save();
            $this->assertSame('assigned-first', $this->fetchIdentifier($table, $id));

            $this->assertNull($clearing->getIdentifier());
            $clearing->setDescription('touched');
            $clearing->save();
            $this->assertSame(
                'assigned-first',
                $this->fetchIdentifier($table, $id),
                $table . ': a stale instance must not clear the identifier assigned meanwhile'
            );

            $replacing->setIdentifier('assigned-third');

            try {
                $replacing->save();
                $this->fail($table . ': a stale instance must not replace the identifier assigned meanwhile');
            } catch (InvalidConfigException $e) {
                $this->addToAssertionCount(1);
            }

            $this->assertSame('assigned-first', $this->fetchIdentifier($table, $id));
        }
    }

    public function testIdentifierIsGeneratedOnCreation(): void
    {
        $store = $this->createStore();

        $first = new KeyConfig();
        $first->setStoreId($store->getId());
        $first->setName(self::NAME_HEIGHT);
        $first->save();

        $second = new KeyConfig();
        $second->setStoreId($store->getId());
        $second->setName(self::NAME_HEIGHT);
        $second->save();

        $this->assertGeneratedIdentifiersDiffer($first->getIdentifier(), $second->getIdentifier(), self::NAME_HEIGHT);

        $firstGroup = new GroupConfig();
        $firstGroup->setStoreId($store->getId());
        $firstGroup->setName(self::NAME_HEIGHT);
        $firstGroup->save();

        $secondGroup = new GroupConfig();
        $secondGroup->setStoreId($store->getId());
        $secondGroup->setName(self::NAME_HEIGHT);
        $secondGroup->save();

        $this->assertGeneratedIdentifiersDiffer(
            $firstGroup->getIdentifier(),
            $secondGroup->getIdentifier(),
            self::NAME_HEIGHT
        );

        $firstCollection = new CollectionConfig();
        $firstCollection->setStoreId($store->getId());
        $firstCollection->setName(self::NAME_HEIGHT);
        $firstCollection->save();

        $secondCollection = new CollectionConfig();
        $secondCollection->setStoreId($store->getId());
        $secondCollection->setName(self::NAME_HEIGHT);
        $secondCollection->save();

        $this->assertGeneratedIdentifiersDiffer(
            $firstCollection->getIdentifier(),
            $secondCollection->getIdentifier(),
            self::NAME_HEIGHT
        );

        $firstStore = $this->createStore();
        $secondStore = $this->createStore();
        $this->assertGeneratedIdentifiersDiffer(
            $firstStore->getIdentifier(),
            $secondStore->getIdentifier(),
            'teststore'
        );

        $createdStore = StoreConfig::create();
        $this->createdStoreIds[] = $createdStore->getId();
        $this->assertNotNull($createdStore->getIdentifier());

        foreach ([KeyConfig::create(), GroupConfig::create(), CollectionConfig::create()] as $created) {
            $this->createdRows[] = [self::tableFor($created), $created->getId()];
            $this->assertNotNull($created->getIdentifier());
        }
    }

    public function testUpdatingARowWithoutIdentifierLeavesItNull(): void
    {
        $store = $this->createStore();
        $id = $this->insertKeyRowWithoutIdentifier($store->getId(), 'stays-null');

        $key = KeyConfig::getById($id, true);
        $key->setDescription('x');
        $key->save();

        $this->assertNull($this->fetchIdentifier(self::TABLE_KEYS, $id));
        $this->assertSame(
            'x',
            Db::get()->fetchOne('SELECT description FROM ' . self::TABLE_KEYS . ' WHERE id = ?', [$id])
        );
    }

    public function testListingsLoadRowsWithoutIdentifier(): void
    {
        $db = Db::get();
        $store = $this->createStore();

        $keyId = $this->insertKeyRowWithoutIdentifier($store->getId(), 'listed');
        $db->update(self::TABLE_KEYS, ['enabled' => 1], ['id' => $keyId]);
        $db->insert(self::TABLE_GROUPS, ['storeId' => $store->getId(), 'name' => 'listed']);

        $keyListing = new KeyConfigListing();
        $keyListing->setCondition('storeId = ?', [$store->getId()]);
        $keys = $keyListing->load();

        $this->assertCount(1, $keys);
        $this->assertNull($keys[0]->getIdentifier());

        $groupListing = new GroupConfigListing();
        $groupListing->setCondition('storeId = ?', [$store->getId()]);
        $groups = $groupListing->load();

        $this->assertCount(1, $groups);
        $this->assertNull($groups[0]->getIdentifier());
    }

    public function testCollidingIdentifierIsRefused(): void
    {
        $store = $this->createStore();
        $otherStore = $this->createStore();

        $taken = new KeyConfig();
        $taken->setStoreId($store->getId());
        $taken->setName('taken');
        $taken->setIdentifier('EF1');
        $taken->save();

        $collidingId = $this->insertKeyRowWithoutIdentifier($store->getId(), 'colliding');
        $colliding = KeyConfig::getById($collidingId, true);
        $colliding->setIdentifier('EF1');

        try {
            $colliding->save();
            $this->fail('A colliding identifier must be refused');
        } catch (InvalidConfigException $e) {
            $this->addToAssertionCount(1);
        }

        $this->assertNull(
            $this->fetchIdentifier(self::TABLE_KEYS, $collidingId),
            'A refused save must not write anything'
        );

        $elsewhere = new KeyConfig();
        $elsewhere->setStoreId($otherStore->getId());
        $elsewhere->setName('elsewhere');
        $elsewhere->setIdentifier('EF1');
        $elsewhere->save();

        $this->assertSame('EF1', $this->fetchIdentifier(self::TABLE_KEYS, $elsewhere->getId()));
    }

    public function testGetByIdentifier(): void
    {
        $store = $this->createStore();
        $otherStore = $this->createStore();

        $key = new KeyConfig();
        $key->setStoreId($store->getId());
        $key->setName('lookup');
        $key->setIdentifier('lookup-key');
        $key->save();

        $this->assertSame($key->getId(), KeyConfig::getByIdentifier('lookup-key', $store->getId())?->getId());
        $this->assertNull(KeyConfig::getByIdentifier('unknown-key', $store->getId()));
        $this->assertNull(KeyConfig::getByIdentifier('lookup-key', $otherStore->getId()));

        $group = new GroupConfig();
        $group->setStoreId($store->getId());
        $group->setName('lookup');
        $group->setIdentifier('lookup-group');
        $group->save();

        $this->assertSame($group->getId(), GroupConfig::getByIdentifier('lookup-group', $store->getId())?->getId());
        $this->assertNull(GroupConfig::getByIdentifier('unknown-group', $store->getId()));
        $this->assertNull(GroupConfig::getByIdentifier('lookup-group', $otherStore->getId()));

        $collection = new CollectionConfig();
        $collection->setStoreId($store->getId());
        $collection->setName('lookup');
        $collection->setIdentifier('lookup-collection');
        $collection->save();

        $this->assertSame(
            $collection->getId(),
            CollectionConfig::getByIdentifier('lookup-collection', $store->getId())?->getId()
        );
        $this->assertNull(CollectionConfig::getByIdentifier('unknown-collection', $store->getId()));
        $this->assertNull(CollectionConfig::getByIdentifier('lookup-collection', $otherStore->getId()));

        $lookupStore = $this->createStore('lookup-store');

        $this->assertSame($lookupStore->getId(), StoreConfig::getByIdentifier('lookup-store')?->getId());
        $this->assertNull(StoreConfig::getByIdentifier('unknown-store'));
    }

    private function assertGeneratedIdentifiersDiffer(?string $first, ?string $second, string $name): void
    {
        $this->assertNotNull($first);
        $this->assertNotNull($second);
        $this->assertNotSame($first, $second);
        $this->assertStringNotContainsString($name, $first);
        $this->assertStringNotContainsString($name, $second);
    }

    private function newConfigs(): array
    {
        return [new StoreConfig(), new CollectionConfig(), new GroupConfig(), new KeyConfig()];
    }

    private static function tableFor(object $config): string
    {
        return match ($config::class) {
            KeyConfig::class => self::TABLE_KEYS,
            GroupConfig::class => self::TABLE_GROUPS,
            default => self::TABLE_COLLECTIONS,
        };
    }

    private function createStore(?string $identifier = null): StoreConfig
    {
        $store = new StoreConfig();
        $store->setName(uniqid('teststore', true));
        if ($identifier !== null) {
            $store->setIdentifier($identifier);
        }
        $store->save();

        $this->createdStoreIds[] = $store->getId();

        return $store;
    }

    private function insertKeyRowWithoutIdentifier(int $storeId, string $name): int
    {
        $db = Db::get();
        $db->insert(self::TABLE_KEYS, ['storeId' => $storeId, 'name' => $name, 'type' => 'input']);

        return (int) $db->lastInsertId();
    }

    private function insertRowWithoutIdentifier(string $table, int $storeId, string $name): int
    {
        if ($table === self::TABLE_KEYS) {
            return $this->insertKeyRowWithoutIdentifier($storeId, $name);
        }

        $db = Db::get();

        if ($table === self::TABLE_STORES) {
            $db->insert($table, ['name' => uniqid($name, true)]);
            $id = (int) $db->lastInsertId();
            $this->createdStoreIds[] = $id;

            return $id;
        }

        $db->insert($table, ['storeId' => $storeId, 'name' => $name]);

        return (int) $db->lastInsertId();
    }

    private function loadConfig(string $table, int $id): StoreConfig|CollectionConfig|GroupConfig|KeyConfig
    {
        return match ($table) {
            self::TABLE_STORES => StoreConfig::getById($id),
            self::TABLE_COLLECTIONS => CollectionConfig::getById($id, true),
            self::TABLE_GROUPS => GroupConfig::getById($id, true),
            default => KeyConfig::getById($id, true),
        };
    }

    private function fetchIdentifier(string $table, int $id): ?string
    {
        $identifier = Db::get()->fetchOne('SELECT identifier FROM ' . $table . ' WHERE id = ?', [$id]);

        return $identifier === false || $identifier === null ? null : (string) $identifier;
    }
}
