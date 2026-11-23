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

use Pimcore\Bundle\CoreBundle\Migrations\Version20261109100000;
use Pimcore\Db;
use Pimcore\Model\DataObject\Classificationstore\CollectionConfig;
use Pimcore\Model\DataObject\Classificationstore\DefinitionCache;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig;
use Pimcore\Model\DataObject\Classificationstore\Traits\CustomMetadataDaoTrait;
use Pimcore\Model\DataObject\Csstore;
use Psr\Log\NullLogger;

/**
 * The customMetadata column of the four classification store configuration entities: that it exists,
 * that it round-trips as JSON text through every load path, that an instance built with `new` does
 * not clear it, that a column holding anything but a JSON object reads as absent, and that nothing
 * in core reads it.
 *
 * A value compared here came out of a `json` column, whose member order MySQL normalises, so the
 * pairs are compared with {@see AbstractBatchedRelationTest::assertSameJsonData()} rather than with
 * assertSame(), which would also compare key order.
 *
 * @group model.datatype.classificationstore
 */
class ConfigMetadataTest extends AbstractBatchedRelationTest
{
    private const COLUMN = 'customMetadata';

    private const COLLECTION = 'collection';

    private const GROUP = 'group';

    private const SUB_GROUP = 'subgroup';

    private const KEY = 'key';

    private const TABLES = [
        self::COLLECTION => CollectionConfig\Dao::TABLE_NAME_COLLECTIONS,
        self::GROUP => GroupConfig\Dao::TABLE_NAME_GROUPS,
        self::SUB_GROUP => SubGroupConfig\Dao::TABLE_NAME_SUBGROUPS,
        self::KEY => KeyConfig\Dao::TABLE_NAME_KEYS,
    ];

    private const PAIRS = ['title.de' => 'Gehäuse', 'etim-id' => 'EF000001', 'nested' => ['a' => 1]];

    /**
     * @var CollectionConfig[]
     */
    private array $createdCollections = [];

    /**
     * @var SubGroupConfig[]
     */
    private array $createdSubGroups = [];

    protected function tearDown(): void
    {
        foreach ($this->createdCollections as $collection) {
            $collection->delete();
        }
        foreach ($this->createdSubGroups as $subGroup) {
            $subGroup->delete();
        }

        $this->createdCollections = [];
        $this->createdSubGroups = [];

        parent::tearDown();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function kinds(): array
    {
        return [
            self::COLLECTION => [self::COLLECTION],
            self::GROUP => [self::GROUP],
            self::SUB_GROUP => [self::SUB_GROUP],
            self::KEY => [self::KEY],
        ];
    }

    public function testTheColumnIsNullableOnAllFourTables(): void
    {
        $schemaManager = Db::get()->createSchemaManager();

        foreach (self::TABLES as $table) {
            $columns = $schemaManager->listTableColumns($table);

            $this->assertArrayHasKey(strtolower(self::COLUMN), $columns, $table . ' must carry the column');
            $this->assertFalse($columns[strtolower(self::COLUMN)]->getNotnull(), $table . ' must be nullable');
        }
    }

    public function testTheMigrationAddsTheColumnsAndTakesThemBack(): void
    {
        $db = Db::get();
        $schemaManager = $db->createSchemaManager();
        $copies = [];

        try {
            $previous = $schemaManager->introspectSchema();
            foreach (self::TABLES as $table) {
                $copies[$table] = $table . '_premigration';
                $db->executeStatement('DROP TABLE IF EXISTS `' . $copies[$table] . '`');
                $db->executeStatement('CREATE TABLE `' . $copies[$table] . '` LIKE `' . $table . '`');
                $db->executeStatement('ALTER TABLE `' . $copies[$table] . '` DROP COLUMN `' . self::COLUMN . '`');
                $previous->getTable($table)->dropColumn(self::COLUMN);
            }

            $up = new Version20261109100000($db, new NullLogger());
            $up->up($previous);
            $this->assertCount(4, $up->getSql(), 'up() adds one column per table');
            $this->executeOnCopies($up, $copies);

            foreach ($copies as $copy) {
                $columns = $schemaManager->listTableColumns($copy);
                $this->assertArrayHasKey(strtolower(self::COLUMN), $columns, 'up() must add the column');
                $this->assertFalse($columns[strtolower(self::COLUMN)]->getNotnull());
                $this->assertArrayHasKey('identifier', $columns, 'up() must leave the other columns alone');
            }

            // a second run finds the columns and does nothing
            $again = new Version20261109100000($db, new NullLogger());
            $again->up($schemaManager->introspectSchema());
            $this->assertSame([], $again->getSql());

            $down = new Version20261109100000($db, new NullLogger());
            $down->down($schemaManager->introspectSchema());
            $this->assertCount(4, $down->getSql(), 'down() takes back one column per table');
            $this->executeOnCopies($down, $copies);

            foreach ($copies as $copy) {
                $columns = $schemaManager->listTableColumns($copy);
                $this->assertArrayNotHasKey(strtolower(self::COLUMN), $columns, 'down() must take the column back');
                $this->assertArrayHasKey('identifier', $columns, 'down() must leave the other columns alone');
            }
        } finally {
            foreach ($copies as $copy) {
                $db->executeStatement('DROP TABLE IF EXISTS `' . $copy . '`');
            }
        }
    }

    public function testTheInstallerDumpCarriesTheSameColumns(): void
    {
        $dump = file_get_contents(dirname(__DIR__, 4) . '/bundles/InstallBundle/dump/install.sql');

        foreach (self::TABLES as $table) {
            preg_match('/CREATE TABLE `' . $table . '` \((.*?)\n\)/s', $dump, $matches);

            $this->assertNotEmpty($matches, 'the dump must create ' . $table);
            $this->assertStringContainsString('`customMetadata` json NULL', $matches[1]);
        }
    }

    /**
     * @dataProvider kinds
     */
    public function testThePairsRoundTripThroughEveryLoadPathAsJsonText(string $kind): void
    {
        $entity = $this->createEntity($kind);
        $entity->setCustomMetadata(self::PAIRS);
        $entity->save();

        $this->assertSameJsonData(self::PAIRS, json_decode($this->readColumn($kind, $entity), true));

        foreach ($this->loadThroughEveryPath($kind, $entity) as $path => $loaded) {
            $this->assertSameJsonData(self::PAIRS, $loaded->getCustomMetadata(), $path);
        }
    }

    public function testAKeyOfAnotherTypeStoresJsonTextToo(): void
    {
        $key = $this->createKey('text');
        $key->setType('input');
        $key->setCustomMetadata(self::PAIRS);
        $key->save();

        $this->assertSameJsonData(self::PAIRS, json_decode($this->readColumn(self::KEY, $key), true));
    }

    public function testAKeyKeepsItsPairsThroughTheDefinitionCache(): void
    {
        $key = $this->createKey('cache');
        $key->setCustomMetadata(self::PAIRS);
        $key->save();

        // load() is the batch hydrator; get() alone would fall back to KeyConfig::getById() and
        // pass whatever that hydrator does
        DefinitionCache::clear(null);
        DefinitionCache::load([$key->getId()]);

        $this->assertArrayHasKey(
            'key' . $key->getId(),
            DefinitionCache::$cache,
            'load() must hold the key, or the assertion below reads the getById() fallback'
        );
        $this->assertSameJsonData(self::PAIRS, DefinitionCache::get($key->getId())->getCustomMetadata());
    }

    /**
     * @dataProvider kinds
     */
    public function testAFreshInstanceKeepsTheStoredPairs(string $kind): void
    {
        $entity = $this->createEntity($kind);
        $entity->setCustomMetadata(self::PAIRS);
        $entity->save();

        $fresh = $this->newEntity($kind);
        $fresh->setId($entity->getId());
        $fresh->setName($entity->getName());
        $fresh->setDescription('changed');
        if ($fresh instanceof KeyConfig) {
            $fresh->setEnabled(true);
        }
        $fresh->save();

        $this->assertSameJsonData(self::PAIRS, json_decode($this->readColumn($kind, $entity), true));
        $this->assertSame('changed', $this->loadThroughEveryPath($kind, $entity)['getById']->getDescription());
    }

    /**
     * @dataProvider kinds
     */
    public function testAReloadAfterTheColumnWasClearedDropsThePairs(string $kind): void
    {
        $entity = $this->createEntity($kind);
        $entity->setCustomMetadata(self::PAIRS);
        $entity->save();

        Db::get()->update(self::TABLES[$kind], [self::COLUMN => null], ['id' => $entity->getId()]);
        $this->assertSame(self::PAIRS, $entity->getCustomMetadata());

        $entity->getDao()->getById();
        $this->assertNull($entity->getCustomMetadata());

        $entity->setDescription('after');
        $entity->save();

        $this->assertNull($this->readColumn($kind, $entity), 'a save after the reload must not write the pairs back');
    }

    /**
     * @dataProvider kinds
     */
    public function testAbsentAndEmptyAreTheSameOnRead(string $kind): void
    {
        $entity = $this->createEntity($kind);
        $this->assertNull($this->readColumn($kind, $entity));

        Db::get()->update(self::TABLES[$kind], [self::COLUMN => '{}'], ['id' => $entity->getId()]);
        foreach ($this->loadThroughEveryPath($kind, $entity) as $path => $loaded) {
            $this->assertNull($loaded->getCustomMetadata(), $path);
        }

        $entity->setCustomMetadata(self::PAIRS);
        $entity->save();
        $entity->setCustomMetadata([]);
        $entity->save();

        $this->assertNull($this->readColumn($kind, $entity), 'an empty map is stored as NULL');
    }

    public function testARowWithoutTheColumnHydratesToNull(): void
    {
        $key = new KeyConfig();
        $key->setValues(['name' => 'nocolumn', 'enabled' => true, 'type' => 'input'], true);

        $this->assertNull($key->getCustomMetadata());
        $this->assertFalse($key->isCustomMetadataInitialized());
    }

    /**
     * The decoder answers NULL for everything that is not a JSON object: text that is not JSON at
     * all, and the JSON scalars a `json` column accepts as valid content.
     *
     * @dataProvider notAJsonObject
     */
    public function testTheDecoderAnswersNullForAnythingThatIsNotAMap(mixed $stored): void
    {
        $this->assertNull($this->hydrationSeam()->decode($stored));
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function notAJsonObject(): array
    {
        return [
            'text that is not json' => ['not json at all'],
            'truncated json' => ['{"etim-id": "EF000001"'],
            'json number' => ['123'],
            'json string' => ['"EF000001"'],
            'json true' => ['true'],
            'json null' => ['null'],
            'empty text' => [''],
            'sql null' => [null],
        ];
    }

    /**
     * The hydrator distinguishes a column that holds nothing usable - which clears the pairs and is
     * written back as NULL - from a row that does not carry the column at all, which it leaves alone
     * so that a save against the upgraded schema does not write NULL over pairs stored meanwhile.
     */
    public function testTheHydratorOnlyTouchesTheFieldWhenTheRowCarriesTheColumn(): void
    {
        $seam = $this->hydrationSeam();

        $absent = new KeyConfig();
        $absent->setCustomMetadata(self::PAIRS);
        $row = $seam->hydrate($absent, ['id' => 1, 'name' => 'nocolumn']);
        // in memory and never through the column, so compared strictly, key order included
        $this->assertSame(self::PAIRS, $absent->getCustomMetadata(), 'an absent column must not clear');
        $this->assertSame(['id' => 1, 'name' => 'nocolumn'], $row);

        $untouched = new KeyConfig();
        $seam->hydrate($untouched, ['id' => 1]);
        $this->assertFalse($untouched->isCustomMetadataInitialized(), 'an absent column must not initialise');

        foreach ([null, 'not json at all', '123', '"EF000001"', '{}'] as $stored) {
            $loaded = new KeyConfig();
            $loaded->setCustomMetadata(self::PAIRS);
            $row = $seam->hydrate($loaded, ['id' => 1, self::COLUMN => $stored]);

            $this->assertNull($loaded->getCustomMetadata(), var_export($stored, true));
            $this->assertTrue($loaded->isCustomMetadataInitialized(), var_export($stored, true));
            $this->assertArrayNotHasKey(self::COLUMN, $row, var_export($stored, true));
        }
    }

    /**
     * A row whose column holds a JSON scalar rather than an object: valid for the column, nothing
     * this feature can hand out. It reads as absent, and the next save stores that.
     *
     * @dataProvider kinds
     */
    public function testAScalarRowReadsAsAbsentAndIsWrittenBackAsNull(string $kind): void
    {
        $entity = $this->createEntity($kind);
        $entity->setCustomMetadata(self::PAIRS);
        $entity->save();

        Db::get()->update(self::TABLES[$kind], [self::COLUMN => '123'], ['id' => $entity->getId()]);
        $this->assertSame('123', $this->readColumn($kind, $entity), 'the column accepts a JSON scalar');

        foreach ($this->loadThroughEveryPath($kind, $entity) as $path => $loaded) {
            $this->assertNull($loaded->getCustomMetadata(), $path);
        }

        $entity->getDao()->getById();
        $entity->setDescription('after the scalar');
        $entity->save();

        $this->assertNull($this->readColumn($kind, $entity), 'a loaded scalar row is written back as SQL NULL');
    }

    public function testThePairsAreOpaqueToCore(): void
    {
        $group = $this->createGroup('opaque');
        $key = $this->createKey('opaque');
        $this->assignKey($group, $key);
        $object = $this->createObject('opaque', [$group->getId() => true]);

        $without = $this->enrich($object);

        $collection = $this->createEntity(self::COLLECTION);
        $subGroup = $this->createEntity(self::SUB_GROUP);
        foreach ([$collection, $subGroup, $group, $key] as $entity) {
            $entity->setCustomMetadata(self::PAIRS);
            $entity->save();
        }
        $this->clearConfigCaches();

        $this->assertSame($without, $this->enrich($object), 'the enriched layout is unaffected by the pairs');
    }

    public function testAKeyWithoutAGroupHoldsPairs(): void
    {
        $key = $this->createKey('ungrouped');
        $key->setCustomMetadata(self::PAIRS);
        $key->save();

        $this->assertSameJsonData(self::PAIRS, KeyConfig::getById($key->getId(), true)->getCustomMetadata());
        $this->assertSame(
            0,
            (int) Db::get()->fetchOne(
                'SELECT COUNT(*) FROM `' . KeyGroupRelation\Dao::TABLE_NAME_RELATIONS . '` WHERE `keyId` = ?',
                [$key->getId()]
            )
        );

        $key->setCustomMetadata(null);
        $key->save();
        $this->assertNull($this->readColumn(self::KEY, $key));
    }

    public function testAKeysPairsDoNotDisturbTheAssignment(): void
    {
        $key = $this->createKey('assigned');
        $key->setCustomMetadata(self::PAIRS);
        $key->save();

        $metadata = ['indexed' => false, 'externalCode' => 'ASSIGNMENT'];
        $relation = $this->assignKey($this->createGroup('assigned'), $key);
        $relation->setMetadata($metadata);
        $relation->setDefinitionOverride(['defaultUnit' => 'cm']);
        $relation->save();

        $loaded = [
            'getByGroupAndKeyId' => KeyGroupRelation::getByGroupAndKeyId($relation->getGroupId(), $key->getId()),
            'listing' => $this->loadThroughListing($relation),
        ];

        foreach ($loaded as $path => $reloaded) {
            $this->assertSameJsonData($metadata, $reloaded->getMetadata(), $path);
            $this->assertSameJsonData(['defaultUnit' => 'cm'], $reloaded->getDefinitionOverride(), $path);
            $this->assertNull($reloaded->getSubGroup(), $path);
        }
    }

    /**
     * The DAO trait's own hydration seam. Text that is not JSON cannot be put into a `json` column,
     * so the fallback for it is reachable only by handing the trait a row directly - which is also
     * the only way to hand it a row that does not carry the column at all.
     */
    private function hydrationSeam(): object
    {
        return new class() {
            use CustomMetadataDaoTrait;

            /**
             * @param array<string, mixed> $row
             *
             * @return array<string, mixed>
             */
            public function hydrate(KeyConfig $config, array $row): array
            {
                return self::hydrateCustomMetadata($config, $row);
            }

            /**
             * @return array<string, mixed>|null
             */
            public function decode(mixed $stored): ?array
            {
                return self::decodeCustomMetadata($stored);
            }
        };
    }

    private function loadThroughListing(KeyGroupRelation $relation): KeyGroupRelation
    {
        $list = new KeyGroupRelation\Listing();
        $list->setCondition('`groupId` = ? AND `keyId` = ?', [$relation->getGroupId(), $relation->getKeyId()]);

        return $list->load()[0];
    }

    private function newEntity(string $kind): CollectionConfig|GroupConfig|SubGroupConfig|KeyConfig
    {
        return match ($kind) {
            self::COLLECTION => new CollectionConfig(),
            self::GROUP => new GroupConfig(),
            self::SUB_GROUP => new SubGroupConfig(),
            self::KEY => new KeyConfig(),
        };
    }

    private function createEntity(string $kind): CollectionConfig|GroupConfig|SubGroupConfig|KeyConfig
    {
        if ($kind === self::GROUP) {
            return $this->createGroup('meta');
        }
        if ($kind === self::KEY) {
            return $this->createKey('meta');
        }

        $entity = $this->newEntity($kind);
        $entity->setStoreId($this->getStoreId());
        $entity->setName('meta_' . $kind . '_' . uniqid());
        $entity->save();

        if ($entity instanceof CollectionConfig) {
            $this->createdCollections[] = $entity;
        } else {
            $this->createdSubGroups[] = $entity;
        }

        return $entity;
    }

    /**
     * @return array<string, CollectionConfig|GroupConfig|SubGroupConfig|KeyConfig>
     */
    private function loadThroughEveryPath(string $kind, CollectionConfig|GroupConfig|SubGroupConfig|KeyConfig $entity): array
    {
        $class = $entity::class;
        $storeId = $entity->getStoreId();
        $loaded = [];

        $byId = new $class();
        $byId->getDao()->getById($entity->getId());
        $loaded['getById'] = $byId;

        $byName = new $class();
        $byName->setStoreId($storeId);
        $byName->getDao()->getByName($entity->getName());
        $loaded['getByName'] = $byName;

        $byIdentifier = new $class();
        $byIdentifier->setStoreId($storeId);
        $byIdentifier->getDao()->getByIdentifier($entity->getIdentifier());
        $loaded['getByIdentifier'] = $byIdentifier;

        $listing = new ($class . '\\Listing')();
        $listing->setCondition('`id` = ?', [$entity->getId()]);
        $loaded['listing'] = $listing->getList()[0];

        if ($kind === self::KEY) {
            $loaded['KeyConfig::getById'] = KeyConfig::getById($entity->getId(), true);

            DefinitionCache::clear(null);
            DefinitionCache::load([$entity->getId()]);
            $this->assertArrayHasKey(
                'key' . $entity->getId(),
                DefinitionCache::$cache,
                'the batch hydrator must hold the key, or get() below reads the getById() fallback'
            );
            $loaded['DefinitionCache'] = DefinitionCache::get($entity->getId());
        }

        return $loaded;
    }

    private function readColumn(string $kind, CollectionConfig|GroupConfig|SubGroupConfig|KeyConfig $entity): ?string
    {
        $value = Db::get()->fetchOne(
            'SELECT `' . self::COLUMN . '` FROM `' . self::TABLES[$kind] . '` WHERE `id` = ?',
            [$entity->getId()]
        );

        return $value === false ? null : $value;
    }

    /**
     * @param array<string, string> $copies
     */
    private function executeOnCopies(Version20261109100000 $migration, array $copies): void
    {
        foreach ($migration->getSql() as $query) {
            $statement = $query->getStatement();
            foreach ($copies as $table => $copy) {
                $statement = str_replace('`' . $table . '`', '`' . $copy . '`', $statement);
            }

            Db::get()->executeStatement($statement);
        }
    }

    /**
     * @param array<int, bool> $activeGroups
     */
    private function createObject(string $key, array $activeGroups): Csstore
    {
        $object = new Csstore();
        $object->setParentId(1);
        $object->setKey($key);
        $object->setPublished(true);
        $object->getCsstore()->setActiveGroups($activeGroups);
        $object->save();

        return $object;
    }

    private function enrich(Csstore $object): string
    {
        $fieldDefinition = $object->getClass()->getFieldDefinition('csstore');
        $fieldDefinition->enrichLayoutDefinition($object);

        return json_encode($fieldDefinition->activeGroupDefinitions);
    }
}
