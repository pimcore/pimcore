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

    private const METADATA = ['indexed' => false, 'externalCode' => 'EF000001', 'custom' => ['x' => 1]];

    private const OVERRIDE = ['defaultUnit' => 'cm'];

    private const SUB_GROUP = 'EFG00007';

    public function testThePerAssignmentColumnsAreNullableAndSubGroupIsIndexed(): void
    {
        $schemaManager = Db::get()->createSchemaManager();
        $columns = $schemaManager->listTableColumns(self::TABLE);

        foreach (self::PER_ASSIGNMENT_COLUMNS as $column) {
            $this->assertArrayHasKey(strtolower($column), $columns, self::TABLE . ' must carry ' . $column);
            $this->assertFalse($columns[strtolower($column)]->getNotnull(), $column . ' must be nullable');
        }

        $this->assertSame(190, $columns[strtolower(self::COLUMN_SUB_GROUP)]->getLength());

        $indexes = $schemaManager->listTableIndexes(self::TABLE);
        $this->assertArrayHasKey(strtolower(self::COLUMN_SUB_GROUP), $indexes, 'subGroup must be indexable');
        $this->assertSame(
            [self::COLUMN_SUB_GROUP],
            $indexes[strtolower(self::COLUMN_SUB_GROUP)]->getColumns()
        );

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
            $previous->getTable(self::TABLE)->dropIndex(self::COLUMN_SUB_GROUP);
            foreach (self::PER_ASSIGNMENT_COLUMNS as $column) {
                $previous->getTable(self::TABLE)->dropColumn($column);
            }
            $db->executeStatement(
                'ALTER TABLE `' . $copy . '` DROP INDEX `subGroup`, DROP COLUMN `metadata`,'
                . ' DROP COLUMN `definitionOverride`, DROP COLUMN `subGroup`'
            );

            $up = new Version20261029100000($db, new NullLogger());
            $up->up($previous);
            $this->executeOnCopy($up, $copy);

            $columns = $schemaManager->listTableColumns($copy);
            foreach (self::PER_ASSIGNMENT_COLUMNS as $column) {
                $this->assertArrayHasKey(strtolower($column), $columns, 'up() must add ' . $column);
                $this->assertFalse($columns[strtolower($column)]->getNotnull(), $column . ' must be nullable');
            }
            $this->assertArrayHasKey(
                strtolower(self::COLUMN_SUB_GROUP),
                $schemaManager->listTableIndexes($copy),
                'up() must add the subGroup index'
            );

            $down = new Version20261029100000($db, new NullLogger());
            $down->down($schemaManager->introspectSchema());
            $this->executeOnCopy($down, $copy);

            $columns = $schemaManager->listTableColumns($copy);
            foreach (self::PER_ASSIGNMENT_COLUMNS as $column) {
                $this->assertArrayNotHasKey(strtolower($column), $columns, 'down() must take back ' . $column);
            }
            $this->assertArrayHasKey('sorter', $columns, 'down() must leave sorter alone');
            $this->assertArrayHasKey('mandatory', $columns, 'down() must leave mandatory alone');
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
        $this->assertStringContainsString('INDEX `subGroup` (`subGroup`)', $matches[1]);
    }

    public function testThePerAssignmentDataRoundTripsThroughBothLoadPaths(): void
    {
        $relation = $this->createRelationWithPerAssignmentData();

        $reloaded = KeyGroupRelation::getByGroupAndKeyId($relation->getGroupId(), $relation->getKeyId());
        $this->assertPerAssignmentData($reloaded);

        $this->assertPerAssignmentData($this->loadThroughListing($relation));
    }

    public function testTheJsonColumnsHoldJsonText(): void
    {
        $relation = $this->createRelationWithPerAssignmentData();
        $row = $this->readRow($relation);

        $this->assertSame(self::METADATA, json_decode($row[self::COLUMN_METADATA], true));
        $this->assertSame(self::OVERRIDE, json_decode($row[self::COLUMN_DEFINITION_OVERRIDE], true));
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

        $this->assertSame(self::METADATA, json_decode($row[self::COLUMN_METADATA], true));
        $this->assertSame(self::OVERRIDE, json_decode($row[self::COLUMN_DEFINITION_OVERRIDE], true));
        $this->assertSame(self::SUB_GROUP, $row[self::COLUMN_SUB_GROUP]);
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

        $this->assertSame(
            self::OVERRIDE,
            json_decode($this->readRow($relation)[self::COLUMN_DEFINITION_OVERRIDE], true)
        );
    }

    public function testSubGroupRoundTrips(): void
    {
        $without = $this->assignKey($this->createGroup('nosub'), $this->createKey('nosub'));
        $this->assertNull($this->readRow($without)[self::COLUMN_SUB_GROUP], 'no sub-group means NULL');

        $long = str_repeat('a', 190);
        $relation = $this->assignKey($this->createGroup('longsub'), $this->createKey('longsub'));
        $relation->setSubGroup($long);
        $relation->save();

        $this->assertSame($long, $this->readRow($relation)[self::COLUMN_SUB_GROUP]);
        $this->assertSame(
            $long,
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

        $this->assertSame(
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
        $relation->setSubGroup(self::SUB_GROUP);
        $relation->save();

        return $relation;
    }

    private function assertPerAssignmentData(KeyGroupRelation $relation): void
    {
        $this->assertSame(self::METADATA, $relation->getMetadata());
        $this->assertSame(self::OVERRIDE, $relation->getDefinitionOverride());
        $this->assertSame(self::SUB_GROUP, $relation->getSubGroup());
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
