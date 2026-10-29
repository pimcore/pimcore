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

    private const METADATA = ['indexed' => false, 'externalCode' => 'EF000001', 'custom' => ['x' => 1]];

    private const OVERRIDE = ['title' => 'per group title'];

    private const SUB_GROUP = 'EFG00007';

    public function testThePerAssignmentColumnsAreNullableAndSubGroupIsIndexed(): void
    {
        $schemaManager = Db::get()->createSchemaManager();
        $columns = $schemaManager->listTableColumns(self::TABLE);

        foreach ([self::COLUMN_METADATA, self::COLUMN_DEFINITION_OVERRIDE, self::COLUMN_SUB_GROUP] as $column) {
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

    public function testMigrationDownDropsOnlyThePerAssignmentColumns(): void
    {
        $db = Db::get();
        $migration = new Version20261029100000($db, new NullLogger());
        $migration->down($db->createSchemaManager()->introspectSchema());

        $planned = array_map(static fn ($query) => $query->getStatement(), $migration->getSql());

        $this->assertSame(
            ['ALTER TABLE `' . self::TABLE . '` DROP INDEX `subGroup`, DROP COLUMN `metadata`,'
                . ' DROP COLUMN `definitionOverride`, DROP COLUMN `subGroup`;'],
            $planned,
            'down() must revert the three columns and their index, and nothing else'
        );
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
