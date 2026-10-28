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
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Csstore;

/**
 * The save writes the rows that changed, and nothing else. Every transition is asserted against
 * the full contents of both store tables, which is what the former delete-and-reinsert produced.
 *
 * @group model.datatype.classificationstore
 */
class SaveDiffTest extends AbstractBatchedRelationTest
{
    private const FIELDNAME = 'csstore';

    private const TYPE = 'input';

    private const BULK_KEY_COUNT = 300;

    private const DEFAULT_LANGUAGE = 'default';

    private const BULK_LANGUAGES = [self::DEFAULT_LANGUAGE, 'en', 'de'];

    private const VALUE_1 = 'one';

    private const VALUE_2 = 'two';

    private const VALUE_3 = 'three';

    /**
     * 900 rows at a batch size of 200 is 5 statements against the value table; the budget is the
     * criterion's 900 / batch size + 5, rounded to the single digit it asks for.
     */
    private const WRITE_STATEMENT_BUDGET = 10;

    private Classificationstore\GroupConfig $groupA;

    private Classificationstore\GroupConfig $groupB;

    private Classificationstore\KeyConfig $key1;

    private Classificationstore\KeyConfig $key2;

    private Classificationstore\KeyConfig $key3;

    private ?Classificationstore\CollectionConfig $collection = null;

    private Csstore $object;

    protected function setUp(): void
    {
        parent::setUp();

        $this->groupA = $this->createGroup('save_a');
        $this->groupB = $this->createGroup('save_b');
        $this->key1 = $this->createKey('save_k1');
        $this->key2 = $this->createKey('save_k2');
        $this->key3 = $this->createKey('save_k3');

        $this->assignKey($this->groupA, $this->key1, 0);
        $this->assignKey($this->groupA, $this->key2, 1);
        $this->assignKey($this->groupB, $this->key3, 0);

        $this->collection = new Classificationstore\CollectionConfig();
        $this->collection->setStoreId($this->getStoreId());
        $this->collection->setName('batched_collection_' . uniqid());
        $this->collection->save();

        $collectionRelation = new Classificationstore\CollectionGroupRelation();
        $collectionRelation->setColId($this->collection->getId());
        $collectionRelation->setGroupId($this->groupA->getId());
        $collectionRelation->save();

        $this->object = new Csstore();
        $this->object->setParentId(1);
        $this->object->setKey('save-diff');
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

    public function testChangingAndRestoringAValue(): void
    {
        $this->assertStoredRows(
            [
                $this->dataRow($this->groupA->getId(), $this->key1->getId(), self::VALUE_1),
                $this->dataRow($this->groupA->getId(), $this->key2->getId(), self::VALUE_2),
            ],
            [$this->groupA->getId()]
        );

        $this->saveStore(function (Classificationstore $store): void {
            $store->setLocalizedKeyValue($this->groupA->getId(), $this->key1->getId(), 'changed');
        });

        $this->assertStoredRows(
            [
                $this->dataRow($this->groupA->getId(), $this->key1->getId(), 'changed'),
                $this->dataRow($this->groupA->getId(), $this->key2->getId(), self::VALUE_2),
            ],
            [$this->groupA->getId()]
        );

        $this->saveStore(function (Classificationstore $store): void {
            $store->setLocalizedKeyValue($this->groupA->getId(), $this->key1->getId(), self::VALUE_1);
        });

        $this->assertStoredRows(
            [
                $this->dataRow($this->groupA->getId(), $this->key1->getId(), self::VALUE_1),
                $this->dataRow($this->groupA->getId(), $this->key2->getId(), self::VALUE_2),
            ],
            [$this->groupA->getId()]
        );
    }

    public function testClearingAndSettingAValueAgain(): void
    {
        $this->saveStore(function (Classificationstore $store): void {
            $store->setLocalizedKeyValue($this->groupA->getId(), $this->key1->getId(), null);
        });

        $this->assertStoredRows(
            [$this->dataRow($this->groupA->getId(), $this->key2->getId(), self::VALUE_2)],
            [$this->groupA->getId()]
        );

        $this->saveStore(function (Classificationstore $store): void {
            $store->setLocalizedKeyValue($this->groupA->getId(), $this->key1->getId(), 'again');
        });

        $this->assertStoredRows(
            [
                $this->dataRow($this->groupA->getId(), $this->key1->getId(), 'again'),
                $this->dataRow($this->groupA->getId(), $this->key2->getId(), self::VALUE_2),
            ],
            [$this->groupA->getId()]
        );
    }

    public function testActivatingAndDeactivatingAGroup(): void
    {
        $this->saveStore(function (Classificationstore $store): void {
            $store->setLocalizedKeyValue($this->groupB->getId(), $this->key3->getId(), self::VALUE_3);
        });

        $this->assertStoredRows(
            [
                $this->dataRow($this->groupA->getId(), $this->key1->getId(), self::VALUE_1),
                $this->dataRow($this->groupA->getId(), $this->key2->getId(), self::VALUE_2),
                $this->dataRow($this->groupB->getId(), $this->key3->getId(), self::VALUE_3),
            ],
            [$this->groupA->getId(), $this->groupB->getId()]
        );

        $this->saveStore(function (Classificationstore $store): void {
            $store->setActiveGroups([$this->groupA->getId() => true]);
        });

        $this->assertStoredRows(
            [
                $this->dataRow($this->groupA->getId(), $this->key1->getId(), self::VALUE_1),
                $this->dataRow($this->groupA->getId(), $this->key2->getId(), self::VALUE_2),
            ],
            [$this->groupA->getId()]
        );
    }

    public function testChangingTheCollectionMapping(): void
    {
        $collectionId = $this->collection->getId();

        // a mapping change alone does not mark the store dirty, so the save has to run regardless
        $this->saveStore(function (Classificationstore $store) use ($collectionId): void {
            $store->setGroupCollectionMapping($this->groupA->getId(), $collectionId);
        }, true);

        $this->assertStoredRows(
            [
                $this->dataRow($this->groupA->getId(), $this->key1->getId(), self::VALUE_1, $collectionId),
                $this->dataRow($this->groupA->getId(), $this->key2->getId(), self::VALUE_2, $collectionId),
            ],
            [$this->groupA->getId()]
        );

        $this->saveStore(function (Classificationstore $store): void {
            $store->setGroupCollectionMappings([]);
        }, true);

        $this->assertStoredRows(
            [
                $this->dataRow($this->groupA->getId(), $this->key1->getId(), self::VALUE_1),
                $this->dataRow($this->groupA->getId(), $this->key2->getId(), self::VALUE_2),
            ],
            [$this->groupA->getId()]
        );
    }

    public function testSetItemsImport(): void
    {
        $this->saveStore(function (Classificationstore $store): void {
            $store->setItems([
                $this->groupA->getId() => [
                    $this->key1->getId() => [self::DEFAULT_LANGUAGE => 'imported'],
                ],
            ]);
        });

        $this->assertStoredRows(
            [$this->dataRow($this->groupA->getId(), $this->key1->getId(), 'imported')],
            [$this->groupA->getId()]
        );
    }

    public function testDenormalize(): void
    {
        if ($this->getStoreId() !== 1) {
            $this->markTestSkipped('denormalize() resolves group and key names in the default store.');
        }

        $fieldDefinition = $this->object->getClass()->getFieldDefinition(self::FIELDNAME);

        $store = $fieldDefinition->denormalize([
            $this->groupA->getName() => [
                self::DEFAULT_LANGUAGE => [$this->key2->getName() => 'denormalized'],
            ],
        ]);

        // denormalize() builds a store that is not yet bound to an object and a field
        $store->setObject($this->object);
        $store->setFieldname(self::FIELDNAME);
        $store->save();

        $this->assertStoredRows(
            [$this->dataRow($this->groupA->getId(), $this->key2->getId(), 'denormalized')],
            [$this->groupA->getId()]
        );
    }

    public function testRemoveGroupData(): void
    {
        $this->saveStore(function (Classificationstore $store): void {
            $store->setLocalizedKeyValue($this->groupB->getId(), $this->key3->getId(), self::VALUE_3);
        });

        // removeGroupData() does not mark the store dirty, so the save has to run regardless
        $this->saveStore(function (Classificationstore $store): void {
            $store->removeGroupData($this->groupA->getId());
        }, true);

        $this->assertStoredRows(
            [$this->dataRow($this->groupB->getId(), $this->key3->getId(), self::VALUE_3)],
            [$this->groupA->getId(), $this->groupB->getId()]
        );
    }

    public function testChangingOneValueOfManyWritesAHandfulOfStatements(): void
    {
        $keyConfigs = $this->seedBulkKeys();

        $store = $this->reloadStore();
        $store->setLocalizedKeyValue($this->groupB->getId(), $keyConfigs[0]->getId(), 'second');

        $this->startMeasurement();
        $store->save();

        $this->assertLessThan(
            self::WRITE_STATEMENT_BUDGET,
            $this->countWriteStatements(),
            'one changed row out of 900 must not rewrite the stored rows'
        );
    }

    public function testRewritingEveryValueIsBatched(): void
    {
        $keyConfigs = $this->seedBulkKeys();

        $store = $this->reloadStore();
        foreach ($keyConfigs as $keyConfig) {
            foreach (self::BULK_LANGUAGES as $language) {
                $store->setLocalizedKeyValue($this->groupB->getId(), $keyConfig->getId(), 'second', $language);
            }
        }

        $this->startMeasurement();
        $store->save();

        $this->assertLessThan(
            self::WRITE_STATEMENT_BUDGET,
            $this->countWriteStatements(),
            '900 changed rows must be written in multi-row statements'
        );
    }

    /**
     * 300 keys in 3 languages, the shape the criterion names.
     *
     * @return Classificationstore\KeyConfig[]
     */
    private function seedBulkKeys(): array
    {
        $keyConfigs = [];

        $store = $this->reloadStore();
        for ($index = 0; $index < self::BULK_KEY_COUNT; $index++) {
            $keyConfig = $this->createKey('bulk_' . $index);
            $this->assignKey($this->groupB, $keyConfig, $index);
            $keyConfigs[] = $keyConfig;

            foreach (self::BULK_LANGUAGES as $language) {
                $store->setLocalizedKeyValue($this->groupB->getId(), $keyConfig->getId(), 'first', $language);
            }
        }
        $store->save();

        return $keyConfigs;
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

        $this->assertGreaterThan(0, $this->countReadStatements(), 'the diff reads the stored rows back');
        $this->assertSame(0, $this->countWriteStatements(), 'an unchanged store writes nothing');
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

    private function getDataTableName(): string
    {
        return 'object_classificationstore_data_' . $this->object->getClass()->getId();
    }

    private function getGroupsTableName(): string
    {
        return 'object_classificationstore_groups_' . $this->object->getClass()->getId();
    }

    /**
     * @return array<string, ?string>
     */
    private function dataRow(
        int $groupId,
        int $keyId,
        ?string $value,
        ?int $collectionId = null,
        string $language = self::DEFAULT_LANGUAGE
    ): array {
        return [
            'id' => (string) $this->object->getId(),
            'collectionId' => $collectionId === null ? null : (string) $collectionId,
            'groupId' => (string) $groupId,
            'keyId' => (string) $keyId,
            'value' => $value,
            'value2' => null,
            'fieldname' => self::FIELDNAME,
            'language' => $language,
            'type' => self::TYPE,
        ];
    }

    /**
     * @param array<array<string, ?string>> $expectedDataRows
     * @param int[] $expectedGroupIds
     */
    private function assertStoredRows(array $expectedDataRows, array $expectedGroupIds): void
    {
        $this->assertEquals(
            $expectedDataRows,
            $this->grabRows($this->getDataTableName(), 'groupId, keyId, language'),
            'the value table holds exactly the rows the store describes'
        );

        $expectedGroupRows = [];
        sort($expectedGroupIds);
        foreach ($expectedGroupIds as $groupId) {
            $expectedGroupRows[] = [
                'id' => (string) $this->object->getId(),
                'groupId' => (string) $groupId,
                'fieldname' => self::FIELDNAME,
            ];
        }

        $this->assertEquals(
            $expectedGroupRows,
            $this->grabRows($this->getGroupsTableName(), 'groupId'),
            'the groups table holds exactly the active groups'
        );
    }

    /**
     * @return array<array<string, ?string>>
     */
    private function grabRows(string $table, string $order): array
    {
        $rows = Db::get()->fetchAllAssociative(
            'SELECT * FROM `' . $table . '` WHERE `id` = ? AND `fieldname` = ? ORDER BY ' . $order,
            [$this->object->getId(), self::FIELDNAME]
        );

        return array_map(
            static fn (array $row) => array_map(static fn ($value) => $value === null ? null : (string) $value, $row),
            $rows
        );
    }
}
