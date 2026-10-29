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

use Doctrine\DBAL\Schema\Schema;
use Pimcore;
use Pimcore\Bundle\CoreBundle\Command\DeleteClassificationStoreCommand;
use Pimcore\Bundle\CoreBundle\Migrations\Version20261029100000;
use Pimcore\Db;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineResolver;
use Pimcore\Model\DataObject\Classificationstore\Label;
use Pimcore\Model\DataObject\Classificationstore\Label\Dao as LabelDao;
use Pimcore\Model\DataObject\Classificationstore\LabelEntityType;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * The label table, the model that writes it, and the cleanup a deleted entity performs.
 *
 * @group model.classificationstore.label
 */
final class LabelTest extends ModelTestCase
{
    use LabelFixtureTrait;

    private const TABLE_LABELS = LabelDao::TABLE_NAME_LABELS;

    private const MIGRATION_SCRATCH_TABLE = 'cs_labels_migration_check';

    private const ENTITY_TYPES = [
        LabelEntityType::Store,
        LabelEntityType::Collection,
        LabelEntityType::Group,
        LabelEntityType::Key,
    ];

    private const TITLE_DE = 'Höhe';

    private const LANGUAGE_DE = 'de';

    private const LANGUAGE_EN = 'en';

    protected function tearDown(): void
    {
        Db::get()->executeStatement('DROP TABLE IF EXISTS `' . self::MIGRATION_SCRATCH_TABLE . '`');

        $this->deleteLabelFixtures();

        parent::tearDown();
    }

    public function testTheTableCarriesTheDeclaredColumnsAndPrimaryKey(): void
    {
        $columns = [];
        foreach ($this->describeColumns(self::TABLE_LABELS) as $column) {
            $columns[$column['COLUMN_NAME']] = $column;
        }

        $this->assertSame(
            ['entityType', 'entityId', 'language', 'title', 'description'],
            array_keys($columns)
        );

        $this->assertSame('NO', $columns['entityId']['IS_NULLABLE']);
        $this->assertStringStartsWith('int', strtolower((string) $columns['entityId']['COLUMN_TYPE']));
        $this->assertStringNotContainsString(
            'unsigned',
            strtolower((string) $columns['entityId']['COLUMN_TYPE']),
            'entityId also addresses classificationstore_stores.id, which is signed'
        );

        $this->assertSame('varchar(10)', strtolower((string) $columns['language']['COLUMN_TYPE']));
        $this->assertSame('NO', $columns['language']['IS_NULLABLE']);

        $this->assertSame('varchar(255)', strtolower((string) $columns['title']['COLUMN_TYPE']));
        $this->assertSame('YES', $columns['title']['IS_NULLABLE']);

        $this->assertSame('text', strtolower((string) $columns['description']['COLUMN_TYPE']));
        $this->assertSame('YES', $columns['description']['IS_NULLABLE']);

        $this->assertSame(
            [['PRIMARY', 'entityType'], ['PRIMARY', 'entityId'], ['PRIMARY', 'language']],
            array_map(
                static fn (array $index): array => [$index['INDEX_NAME'], $index['COLUMN_NAME']],
                $this->describeIndexes(self::TABLE_LABELS)
            ),
            'the primary key is the only index the table carries'
        );
    }

    public function testTheEntityTypeEnumCarriesAllFiveValues(): void
    {
        $columnType = '';
        foreach ($this->describeColumns(self::TABLE_LABELS) as $column) {
            if ($column['COLUMN_NAME'] === 'entityType') {
                $columnType = strtolower((string) $column['COLUMN_TYPE']);
            }
        }

        $this->assertSame("enum('store','collection','group','subgroup','key')", $columnType);
    }

    /**
     * One fixture, both schema paths: the statement the migration plans has to build the table the
     * installer dump already created.
     */
    public function testTheMigrationCreatesTheSameTableAsTheInstallerDump(): void
    {
        $db = Db::get();
        $migration = new Version20261029100000($db, new NullLogger());

        // an empty schema is the fresh install the migration is written for
        $migration->up(new Schema());
        $planned = array_map(static fn ($query) => $query->getStatement(), $migration->getSql());

        $this->assertCount(1, $planned);

        $db->executeStatement(str_replace(self::TABLE_LABELS, self::MIGRATION_SCRATCH_TABLE, $planned[0]));

        $this->assertSame(
            $this->describeColumns(self::TABLE_LABELS),
            $this->describeColumns(self::MIGRATION_SCRATCH_TABLE)
        );
        $this->assertSame(
            $this->describeIndexes(self::TABLE_LABELS),
            $this->describeIndexes(self::MIGRATION_SCRATCH_TABLE)
        );
    }

    public function testTheMigrationIsGuardedOnTheExistingTableAndDropsItOnTheWayDown(): void
    {
        $db = Db::get();
        $schema = $db->createSchemaManager()->introspectSchema();

        $up = new Version20261029100000($db, new NullLogger());
        $up->up($schema);
        $this->assertSame([], array_map(static fn ($query) => $query->getStatement(), $up->getSql()));

        $down = new Version20261029100000($db, new NullLogger());
        $down->down($schema);
        $this->assertSame(
            ['DROP TABLE `' . self::TABLE_LABELS . '`'],
            array_map(static fn ($query) => $query->getStatement(), $down->getSql())
        );
    }

    public function testASubGroupLabelInsertsAndReadsBackAlthoughNoSubGroupEntityExists(): void
    {
        $this->writeLabel(LabelEntityType::SubGroup, 4711, self::LANGUAGE_DE, self::TITLE_DE, 'Die Höhe');

        $label = Label::getByEntity(LabelEntityType::SubGroup, 4711, self::LANGUAGE_DE);

        $this->assertNotNull($label);
        $this->assertSame(LabelEntityType::SubGroup, $label->getEntityType());
        $this->assertSame(self::TITLE_DE, $label->getTitle());
        $this->assertSame('Die Höhe', $label->getDescription());
    }

    public function testSavingTheSameTripleTwiceUpdatesTheRowInsteadOfAddingOne(): void
    {
        $this->writeLabel(LabelEntityType::Key, 4712, self::LANGUAGE_DE, self::TITLE_DE, 'Die Höhe');
        $this->assertSame(1, $this->countLabels(LabelEntityType::Key, 4712));

        $this->writeLabel(LabelEntityType::Key, 4712, self::LANGUAGE_DE, 'Bauhöhe', 'Die Bauhöhe');
        $this->assertSame(1, $this->countLabels(LabelEntityType::Key, 4712));

        $label = Label::getByEntity(LabelEntityType::Key, 4712, self::LANGUAGE_DE);
        $this->assertNotNull($label);
        $this->assertSame('Bauhöhe', $label->getTitle());
        $this->assertSame('Die Bauhöhe', $label->getDescription());

        $label->delete();

        $this->assertSame(0, $this->countLabels(LabelEntityType::Key, 4712));
        $this->assertNull(Label::getByEntity(LabelEntityType::Key, 4712, self::LANGUAGE_DE));
    }

    public function testDeletingAnEntityDeletesItsLabelsInEveryLanguage(): void
    {
        foreach (self::ENTITY_TYPES as $entityType) {
            $entity = $this->createEntity($entityType);
            $entityId = (int) $entity->getId();

            $this->writeLabel($entityType, $entityId, self::LANGUAGE_DE, self::TITLE_DE, null);
            $this->writeLabel($entityType, $entityId, self::LANGUAGE_EN, 'Height', null);
            $this->assertSame(2, $this->countLabels($entityType, $entityId));

            $entity->delete();

            $this->assertSame(
                0,
                $this->countLabels($entityType, $entityId),
                'deleting a ' . $entityType->value . ' must take its labels with it'
            );
            $this->assertSame(0, $this->countEntityRows($entityType, $entityId));
        }
    }

    public function testDeletingAStoreLeavesTheLabelsOfItsCollectionsGroupsAndKeys(): void
    {
        $store = $this->createStore();
        $storeId = (int) $store->getId();
        $collection = $this->createEntity(LabelEntityType::Collection, $storeId);
        $group = $this->createEntity(LabelEntityType::Group, $storeId);
        $key = $this->createEntity(LabelEntityType::Key, $storeId);

        $this->writeLabel(LabelEntityType::Store, $storeId, self::LANGUAGE_DE, 'Speicher', null);
        $this->writeLabel(LabelEntityType::Collection, (int) $collection->getId(), self::LANGUAGE_DE, 'Sammlung', null);
        $this->writeLabel(LabelEntityType::Group, (int) $group->getId(), self::LANGUAGE_DE, 'Gruppe', null);
        $this->writeLabel(LabelEntityType::Key, (int) $key->getId(), self::LANGUAGE_DE, self::TITLE_DE, null);

        $store->delete();

        $this->assertSame(0, $this->countLabels(LabelEntityType::Store, $storeId));
        $this->assertSame(1, $this->countLabels(LabelEntityType::Collection, (int) $collection->getId()));
        $this->assertSame(1, $this->countLabels(LabelEntityType::Group, (int) $group->getId()));
        $this->assertSame(1, $this->countLabels(LabelEntityType::Key, (int) $key->getId()));
    }

    /**
     * The command deletes the configuration rows with its own statements and never constructs a
     * model, so the labels it has to remove are exactly the ones no model delete() ever sees.
     */
    public function testTheDeleteStoreCommandRemovesTheLabelsOfTheWholeStore(): void
    {
        $store = $this->createStore();
        $storeId = (int) $store->getId();
        $collection = $this->createEntity(LabelEntityType::Collection, $storeId);
        $group = $this->createEntity(LabelEntityType::Group, $storeId);
        $key = $this->createEntity(LabelEntityType::Key, $storeId);
        $survivor = $this->createEntity(LabelEntityType::Key);

        $this->writeLabel(LabelEntityType::Store, $storeId, self::LANGUAGE_DE, 'Speicher', null);
        $this->writeLabel(LabelEntityType::Collection, (int) $collection->getId(), self::LANGUAGE_DE, 'Sammlung', null);
        $this->writeLabel(LabelEntityType::Group, (int) $group->getId(), self::LANGUAGE_DE, 'Gruppe', null);
        $this->writeLabel(LabelEntityType::Key, (int) $key->getId(), self::LANGUAGE_DE, self::TITLE_DE, null);
        $this->writeLabel(LabelEntityType::Key, (int) $survivor->getId(), self::LANGUAGE_DE, self::TITLE_DE, null);

        $tester = new CommandTester(
            new DeleteClassificationStoreCommand(Pimcore::getContainer()->get(EngineResolver::class))
        );
        $tester->execute(['storeId' => (string) $storeId]);

        $this->assertSame(0, $this->countLabels(LabelEntityType::Store, $storeId));
        $this->assertSame(0, $this->countLabels(LabelEntityType::Collection, (int) $collection->getId()));
        $this->assertSame(0, $this->countLabels(LabelEntityType::Group, (int) $group->getId()));
        $this->assertSame(0, $this->countLabels(LabelEntityType::Key, (int) $key->getId()));
        $this->assertSame(
            1,
            $this->countLabels(LabelEntityType::Key, (int) $survivor->getId()),
            'a key of another store keeps its labels'
        );
    }

    private function countEntityRows(LabelEntityType $entityType, int $entityId): int
    {
        $table = match ($entityType) {
            LabelEntityType::Store => 'classificationstore_stores',
            LabelEntityType::Collection => 'classificationstore_collections',
            LabelEntityType::Group => 'classificationstore_groups',
            default => 'classificationstore_keys',
        };

        return (int) Db::get()->fetchOne('SELECT COUNT(*) FROM `' . $table . '` WHERE `id` = ?', [$entityId]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function describeColumns(string $table): array
    {
        return Db::get()->fetchAllAssociative(
            'SELECT `COLUMN_NAME`, `COLUMN_TYPE`, `IS_NULLABLE`, `COLUMN_DEFAULT`, `COLUMN_KEY`,'
            . ' `ORDINAL_POSITION`, `CHARACTER_SET_NAME`, `COLLATION_NAME`'
            . ' FROM `information_schema`.`COLUMNS`'
            . ' WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = ? ORDER BY `ORDINAL_POSITION`',
            [$table]
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function describeIndexes(string $table): array
    {
        return Db::get()->fetchAllAssociative(
            'SELECT `INDEX_NAME`, `SEQ_IN_INDEX`, `COLUMN_NAME`, `NON_UNIQUE`'
            . ' FROM `information_schema`.`STATISTICS`'
            . ' WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = ?'
            . ' ORDER BY `INDEX_NAME`, `SEQ_IN_INDEX`',
            [$table]
        );
    }
}
