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

use Pimcore\Bundle\CoreBundle\Migrations\Version20261029100000;
use Pimcore\Db;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig;
use Pimcore\Model\DataObject\Csstore;
use Pimcore\Model\Exception\InvalidConfigException;
use Psr\Log\NullLogger;

/**
 * The per assignment columns of a key/group relation: that they exist, that they round-trip as
 * JSON text rather than as a serialization payload, that a relation built with `new` does not
 * clear them, and that nothing in core reads them.
 *
 * @group model.datatype.classificationstore
 */
class RelationMetadataTest extends AbstractBatchedRelationTest
{
    private const TABLE = KeyGroupRelation\Dao::TABLE_NAME_RELATIONS;

    private const COLUMN_METADATA = 'metadata';

    private const COLUMN_DEFINITION_OVERRIDE = 'definitionOverride';

    private const COLUMN_SUB_GROUP = 'subGroup';

    private const PER_ASSIGNMENT_COLUMNS = [
        self::COLUMN_METADATA,
        self::COLUMN_DEFINITION_OVERRIDE,
        self::COLUMN_SUB_GROUP,
    ];

    /**
     * The columns Version20261029100000 adds. `subGroup` is added by Version20261029120000, together
     * with the sub group vocabulary it names and the index it is queried by, and its schema is
     * asserted by SubGroupTest.
     */
    private const MIGRATED_COLUMNS = [
        self::COLUMN_METADATA,
        self::COLUMN_DEFINITION_OVERRIDE,
    ];

    private const METADATA = ['indexed' => false, 'externalCode' => 'EF000001', 'custom' => ['x' => 1]];

    private const OVERRIDE = ['defaultUnit' => 'cm'];

    /**
     * @var SubGroupConfig[]
     */
    private array $createdSubGroups = [];

    private ?string $subGroup = null;

    protected function tearDown(): void
    {
        $db = Db::get();
        foreach ($this->createdSubGroups as $subGroup) {
            $db->delete(SubGroupConfig\Dao::TABLE_NAME_SUBGROUPS, ['id' => $subGroup->getId()]);
        }

        $this->createdSubGroups = [];
        $this->subGroup = null;

        parent::tearDown();
    }

    public function testThePerAssignmentColumnsAreNullable(): void
    {
        $schemaManager = Db::get()->createSchemaManager();
        $columns = $schemaManager->listTableColumns(self::TABLE);

        foreach (self::PER_ASSIGNMENT_COLUMNS as $column) {
            $this->assertArrayHasKey(strtolower($column), $columns, self::TABLE . ' must carry ' . $column);
            $this->assertFalse($columns[strtolower($column)]->getNotnull(), $column . ' must be nullable');
        }

        $this->assertSame(190, $columns[strtolower(self::COLUMN_SUB_GROUP)]->getLength());

        $this->assertContains(
            self::COLUMN_SUB_GROUP,
            (new KeyGroupRelation())->getDao()->getValidTableColumns(self::TABLE),
            'The valid table columns cache must know the new columns'
        );
    }

    public function testTheMigrationAddsThePerAssignmentColumnsAndTakesThemBack(): void
    {
        $db = Db::get();
        $schemaManager = $db->createSchemaManager();
        $copy = self::TABLE . '_premigration';

        $db->executeStatement('DROP TABLE IF EXISTS `' . $copy . '`');
        $db->executeStatement('CREATE TABLE `' . $copy . '` LIKE `' . self::TABLE . '`');

        try {
            // the table as a database created from the previous dump carries it
            $previous = $schemaManager->introspectSchema();
            foreach (self::MIGRATED_COLUMNS as $column) {
                $previous->getTable(self::TABLE)->dropColumn($column);
            }
            $db->executeStatement(
                'ALTER TABLE `' . $copy . '` DROP COLUMN `metadata`, DROP COLUMN `definitionOverride`'
            );

            $up = new Version20261029100000($db, new NullLogger());
            $up->up($previous);
            $this->executeOnCopy($up, $copy);

            $columns = $schemaManager->listTableColumns($copy);
            foreach (self::MIGRATED_COLUMNS as $column) {
                $this->assertArrayHasKey(strtolower($column), $columns, 'up() must add ' . $column);
                $this->assertFalse($columns[strtolower($column)]->getNotnull(), $column . ' must be nullable');
            }

            $down = new Version20261029100000($db, new NullLogger());
            $down->down($schemaManager->introspectSchema());
            $this->executeOnCopy($down, $copy);

            $columns = $schemaManager->listTableColumns($copy);
            foreach (self::MIGRATED_COLUMNS as $column) {
                $this->assertArrayNotHasKey(strtolower($column), $columns, 'down() must take back ' . $column);
            }
            $this->assertArrayHasKey('sorter', $columns, 'down() must leave sorter alone');
            $this->assertArrayHasKey('mandatory', $columns, 'down() must leave mandatory alone');
            $this->assertArrayHasKey(
                strtolower(self::COLUMN_SUB_GROUP),
                $columns,
                'down() must leave the sub group of Version20261029120000 alone'
            );
        } finally {
            $db->executeStatement('DROP TABLE IF EXISTS `' . $copy . '`');
        }
    }

    public function testTheInstallerDumpCarriesTheSameColumns(): void
    {
        $dump = file_get_contents(dirname(__DIR__, 4) . '/bundles/InstallBundle/dump/install.sql');
        preg_match('/CREATE TABLE `' . self::TABLE . '` \((.*?)\n\)/s', $dump, $matches);

        $this->assertNotEmpty($matches, 'the dump must create ' . self::TABLE);

        $this->assertStringContainsString('`metadata` json NULL', $matches[1]);
        $this->assertStringContainsString('`definitionOverride` json NULL', $matches[1]);
        $this->assertStringContainsString('`subGroup` VARCHAR(190) NULL DEFAULT NULL', $matches[1]);
    }

    public function testThePerAssignmentDataRoundTripsThroughBothLoadPaths(): void
    {
        $relation = $this->createRelationWithPerAssignmentData();

        $reloaded = KeyGroupRelation::getByGroupAndKeyId($relation->getGroupId(), $relation->getKeyId());
        $this->assertPerAssignmentData($reloaded);

        $this->assertPerAssignmentData($this->loadThroughListing($relation));
    }

    public function testAReloadAfterThePerAssignmentDataWasClearedDropsIt(): void
    {
        $relation = $this->createRelationWithPerAssignmentData();

        $relation->setMetadata(null);
        $relation->setDefinitionOverride(null);
        $relation->setSubGroup(null);
        $relation->save();

        // the instance still holds what it was given, and the cleared row has to take it off again
        $relation->getDao()->getById($relation->getKeyId(), $relation->getGroupId());
        $this->assertNoPerAssignmentData($relation);

        $relation->setSorter(7);
        $relation->save();

        $row = $this->readRow($relation);
        foreach (self::PER_ASSIGNMENT_COLUMNS as $column) {
            $this->assertNull($row[$column], 'a save after the reload must not write ' . $column . ' back');
        }
        $this->assertSame(7, (int) $row['sorter']);
    }

    public function testARowWithoutPerAssignmentDataLoadsAsNullThroughBothLoadPaths(): void
    {
        $relation = $this->assignKey($this->createGroup('nodata'), $this->createKey('nodata'));

        $this->assertNoPerAssignmentData(
            KeyGroupRelation::getByGroupAndKeyId($relation->getGroupId(), $relation->getKeyId())
        );
        $this->assertNoPerAssignmentData($this->loadThroughListing($relation));
    }

    public function testTheJsonColumnsHoldJsonText(): void
    {
        $relation = $this->createRelationWithPerAssignmentData();
        $row = $this->readRow($relation);

        $this->assertSameJsonData(self::METADATA, json_decode($row[self::COLUMN_METADATA], true));
        $this->assertSameJsonData(self::OVERRIDE, json_decode($row[self::COLUMN_DEFINITION_OVERRIDE], true));
    }

    public function testASavedFreshRelationKeepsStoredPerAssignmentData(): void
    {
        $relation = $this->createRelationWithPerAssignmentData();

        // the documented way of assigning a key to a group, carrying nothing but the assignment
        $fresh = new KeyGroupRelation();
        $fresh->setGroupId($relation->getGroupId());
        $fresh->setKeyId($relation->getKeyId());
        $fresh->setSorter(42);
        $fresh->save();

        $row = $this->readRow($relation);

        $this->assertSameJsonData(self::METADATA, json_decode($row[self::COLUMN_METADATA], true));
        $this->assertSameJsonData(self::OVERRIDE, json_decode($row[self::COLUMN_DEFINITION_OVERRIDE], true));
        $this->assertSame($this->subGroup(), $row[self::COLUMN_SUB_GROUP]);
        $this->assertSame(42, (int) $row['sorter'], 'the sorter the fresh relation carries is written');
    }

    public function testARefusedOverrideWritesNothing(): void
    {
        $relation = $this->createRelationWithPerAssignmentData();

        try {
            $relation->setDefinitionOverride(['fieldtype' => 'input']);
            $this->fail('An out of scope override must be refused');
        } catch (InvalidConfigException $e) {
            $this->addToAssertionCount(1);
        }

        $this->assertSameJsonData(
            self::OVERRIDE,
            json_decode($this->readRow($relation)[self::COLUMN_DEFINITION_OVERRIDE], true)
        );
    }

    public function testSubGroupRoundTrips(): void
    {
        $without = $this->assignKey($this->createGroup('nosub'), $this->createKey('nosub'));
        $this->assertNull($this->readRow($without)[self::COLUMN_SUB_GROUP], 'no sub-group means NULL');

        $relation = $this->assignKey($this->createGroup('withsub'), $this->createKey('withsub'));
        $relation->setSubGroup($this->subGroup());
        $relation->save();

        $this->assertSame($this->subGroup(), $this->readRow($relation)[self::COLUMN_SUB_GROUP]);
        $this->assertSame(
            $this->subGroup(),
            KeyGroupRelation::getByGroupAndKeyId($relation->getGroupId(), $relation->getKeyId())->getSubGroup()
        );
    }

    public function testMetadataIsOpaqueToCore(): void
    {
        $group = $this->createGroup('opaque');
        $relation = $this->assignKey($group, $this->createKey('opaque'));
        $object = $this->createObject('opaque', [$group->getId() => true]);

        $without = $this->enrich($object);

        $relation->setMetadata(self::METADATA);
        $relation->save();
        $this->clearConfigCaches();

        $this->assertSameJsonData(
            self::METADATA,
            KeyGroupRelation::getByGroupAndKeyId($group->getId(), $relation->getKeyId())->getMetadata(),
            'the metadata is stored and returned verbatim'
        );
        $this->assertSame($without, $this->enrich($object), 'the enriched layout is unaffected by metadata');
    }

    /**
     * Executes what the migration planned against a structural copy, so the live table is left alone.
     */
    private function executeOnCopy(Version20261029100000 $migration, string $table): void
    {
        foreach ($migration->getSql() as $query) {
            Db::get()->executeStatement(
                str_replace('`' . self::TABLE . '`', '`' . $table . '`', $query->getStatement())
            );
        }
    }

    private function createRelationWithPerAssignmentData(): KeyGroupRelation
    {
        $relation = $this->assignKey($this->createGroup('perassignment'), $this->createKey('perassignment'));
        $relation->setMetadata(self::METADATA);
        $relation->setDefinitionOverride(self::OVERRIDE);
        $relation->setSubGroup($this->subGroup());
        $relation->save();

        return $relation;
    }

    /**
     * A tag names an entry of the vocabulary of its store, so the entry has to exist before a
     * relation can carry it. One entry serves every relation here: they all live in `teststore`.
     */
    private function subGroup(): string
    {
        if ($this->subGroup === null) {
            $entry = new SubGroupConfig();
            $entry->setStoreId($this->getStoreId());
            $entry->setName('batched_perassignment_' . uniqid());
            $entry->save();

            $this->createdSubGroups[] = $entry;
            $this->subGroup = $entry->getIdentifier();
        }

        return $this->subGroup;
    }

    private function assertPerAssignmentData(KeyGroupRelation $relation): void
    {
        $this->assertSameJsonData(self::METADATA, $relation->getMetadata());
        $this->assertSameJsonData(self::OVERRIDE, $relation->getDefinitionOverride());
        $this->assertSame($this->subGroup(), $relation->getSubGroup());
    }

    private function assertNoPerAssignmentData(KeyGroupRelation $relation): void
    {
        $this->assertNull($relation->getMetadata());
        $this->assertNull($relation->getDefinitionOverride());
        $this->assertNull($relation->getSubGroup());
    }

    /**
     * MySQL does not preserve the key order of a JSON object, so a value that came out of one of the
     * two JSON columns is compared by key, with every key and every value still matching exactly.
     *
     * @param array<array-key, mixed> $expected
     */
    private function assertSameJsonData(array $expected, mixed $actual, string $message = ''): void
    {
        $this->assertSame(
            $this->sortedByKey($expected),
            is_array($actual) ? $this->sortedByKey($actual) : $actual,
            $message
        );
    }

    /**
     * @param array<array-key, mixed> $value
     *
     * @return array<array-key, mixed>
     */
    private function sortedByKey(array $value): array
    {
        $sorted = [];
        foreach ($value as $key => $entry) {
            $sorted[$key] = is_array($entry) ? $this->sortedByKey($entry) : $entry;
        }
        ksort($sorted);

        return $sorted;
    }

    private function loadThroughListing(KeyGroupRelation $relation): KeyGroupRelation
    {
        $list = new KeyGroupRelation\Listing();
        $list->setCondition('`groupId` = ? AND `keyId` = ?', [$relation->getGroupId(), $relation->getKeyId()]);

        return $list->load()[0];
    }

    /**
     * @return array<string, mixed>
     */
    private function readRow(KeyGroupRelation $relation): array
    {
        return Db::get()->fetchAssociative(
            'SELECT `metadata`, `definitionOverride`, `subGroup`, `sorter` FROM `' . self::TABLE
            . '` WHERE `groupId` = ? AND `keyId` = ?',
            [$relation->getGroupId(), $relation->getKeyId()]
        );
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

    /**
     * The enriched layout as the editor receives it, as a string, so that two runs can be compared
     * byte for byte.
     */
    private function enrich(Csstore $object): string
    {
        $fieldDefinition = $object->getClass()->getFieldDefinition('csstore');
        $fieldDefinition->enrichLayoutDefinition($object);

        return json_encode($fieldDefinition->activeGroupDefinitions);
    }
}
