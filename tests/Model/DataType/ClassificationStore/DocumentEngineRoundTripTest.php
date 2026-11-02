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

use Carbon\Carbon;
use Pimcore;
use Pimcore\Bundle\CoreBundle\DataObject\Classificationstore\DrainOutcome;
use Pimcore\Db;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Image as ImageFieldDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Csstore;
use Pimcore\Model\DataObject\Data\EncryptedField;
use Pimcore\Model\DataObject\Data\InputQuantityValue;
use Pimcore\Model\DataObject\Data\QuantityValue;
use Pimcore\Model\DataObject\Data\RgbaColor;
use Pimcore\Model\DataObject\QuantityValue\Unit;
use Pimcore\Model\User;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * The two engines against each other: the same items are written through both and the two loaded
 * stores are compared with one another.
 *
 * Comparing a document-engine write against a document-engine read would round-trip cleanly even
 * if both halves were wrong, which is why the EAV engine is the reference here.
 *
 * The same key types are what the drain is held to: it converts the values a store written on EAV
 * holds, compares its own round trip per pair and only then removes the EAV rows.
 *
 * @group model.datatype.classificationstore
 */
class DocumentEngineRoundTripTest extends AbstractClassificationStoreTest
{
    use MigrationServiceTrait;

    private const DOCUMENT_ENGINE = 'document';

    private const CLASS_NAME = 'csstore';

    private const FIELDNAME = 'csstore';

    private const INPUT_KEY = 'input';

    private const MIGRATED_AT = 1762000000;

    private const DATA_TABLE_PREFIX = 'object_classificationstore_data_';

    private const GROUPS_TABLE_PREFIX = 'object_classificationstore_groups_';

    private const DOCUMENT_TABLE_PREFIX = 'object_classificationstore_doc_';

    /**
     * A key id no key config answers, so that its value row reaches neither engine.
     */
    private const ORPHAN_KEY_ID = 2147483000;

    private const FIRST_GROUP = 'testgroup1';

    private const SECOND_GROUP = 'testgroup2';

    /**
     * The key names the shipped test store carries, in the order
     * {@see AbstractClassificationStoreTest::configureStore()} creates them: the first three belong
     * to the first group, the rest to the second one.
     */
    private const FIRST_GROUP_KEYS = ['date', 'datetime', 'encryptedField'];

    protected function setUp(): void
    {
        parent::setUp();

        Pimcore::setAdminMode();
        TestHelper::cleanUp();
    }

    protected function tearDown(): void
    {
        $this->setStoreEngine(null);

        TestHelper::cleanUp();

        parent::tearDown();
    }

    /**
     * Every key type the shipped test store exercises comes back from the document engine as what
     * the EAV engine gives back for the same input.
     */
    public function testEveryKeyTypeLoadsBackWhatTheEavEngineLoadsBack(): void
    {
        $values = $this->buildValues();

        $this->assertCount(
            AbstractClassificationStoreTest::$configCount,
            $values,
            'every key the store configures is written'
        );

        $eavValues = $this->writeAndReadBack('round-trip-eav', $values, null);
        $documentValues = $this->writeAndReadBack('round-trip-document', $values, self::DOCUMENT_ENGINE);

        foreach (array_keys($values) as $keyName) {
            $this->assertEquals(
                $eavValues[$keyName],
                $documentValues[$keyName],
                'the document engine returns another value than the EAV engine for ' . $keyName
            );
        }
    }

    /**
     * The drain over the same key types: every value a store written on EAV holds comes back from
     * the document the drain wrote for it, and the pair's EAV rows are gone afterwards.
     *
     * Three of the five exclusions of the comparison are seeded here, because they are rows the
     * model never carries and so cannot reach either side: a `type` column that no longer matches
     * the key, a collection placeholder row whose two value columns are empty, and a value row
     * whose key resolves to no definition. The fourth - a collection mapping the child shares with
     * its inheritable parent - is in {@see MigrationDrainScopeTest}, and the fifth is the two rows
     * of one group disagreeing on their collection id, seeded below as well.
     */
    public function testTheDrainConvertsEveryKeyTypeUnderTheComparisonExclusions(): void
    {
        $values = $this->buildValues();
        $store = $this->setStoreEngine(null);
        $object = $this->writeValues('drain-round-trip', $values, $store);
        $objectId = (int) $object->getId();

        $expected = $this->readValues($objectId, $values, $store);

        $this->seedComparisonExclusions($objectId, $store);
        $this->setStoreEngine(self::DOCUMENT_ENGINE);

        $class = ClassDefinition::getByName(self::CLASS_NAME);
        $this->assertInstanceOf(ClassDefinition::class, $class);

        $result = $this->getMigrationService()
            ->drainPair($class, $objectId, self::FIELDNAME, self::MIGRATED_AT);

        $this->assertSame(
            DrainOutcome::Converted,
            $result->outcome,
            'none of the seeded exclusions may count as a divergence: ' . (string) $result->divergence
        );

        $this->assertSame(0, $this->countEavRows($objectId));
        $this->assertNotSame([], $this->readDocumentRows($objectId));

        foreach ($this->readValues($objectId, $values, $store) as $keyName => $value) {
            $this->assertEquals($expected[$keyName], $value, $keyName . ' did not survive the drain');
        }
    }

    /**
     * All of a pair is one transaction: rolled back, its EAV rows are still there and it holds no
     * document at all, so the read-through keeps serving its stored values.
     */
    public function testARolledBackDrainLeavesThePairWhollyOnEav(): void
    {
        $store = $this->setStoreEngine(null);
        $object = $this->writeValues('drain-rollback', [self::INPUT_KEY => 'kept on eav'], $store);
        $objectId = (int) $object->getId();

        $eavRows = $this->countEavRows($objectId);
        $this->assertGreaterThan(0, $eavRows);

        $this->setStoreEngine(self::DOCUMENT_ENGINE);

        $class = ClassDefinition::getByName(self::CLASS_NAME);
        $this->assertInstanceOf(ClassDefinition::class, $class);

        $db = Db::get();
        $db->beginTransaction();

        try {
            $this->getMigrationService()->drainPair($class, $objectId, self::FIELDNAME, self::MIGRATED_AT);
        } finally {
            $db->rollBack();
        }

        $this->assertSame($eavRows, $this->countEavRows($objectId));
        $this->assertSame([], $this->readDocumentRows($objectId));
        $this->assertSame(
            'kept on eav',
            $this->reloadStore($objectId)->getLocalizedKeyValue(
                $this->getGroupId(self::INPUT_KEY),
                $this->getKeyId(self::INPUT_KEY, $store)
            )
        );
    }

    /**
     * The value every key of the test store is written with.
     *
     * @return array<string, mixed> key name => value
     */
    private function buildValues(): array
    {
        $unit = Unit::getByAbbreviation('mm');
        if (!$unit) {
            $unit = new Unit();
            $unit->setAbbreviation('mm');
            $unit->save();
        }

        return [
            'date' => new Carbon('2026-10-29 00:00:00'),
            'datetime' => new Carbon('2026-10-29 10:11:12'),
            'encryptedField' => new EncryptedField(new Input(), 'abc'),
            self::INPUT_KEY => 'abc',
            'rgbaColor' => new RgbaColor(1, 2, 3, 4),
            'select' => 'B',
            'time' => '12:30',
            'numeric' => 12.57,
            'booleanSelect' => true,
            'user' => $this->getUserId(),
            'textarea' => "line1\nline2",
            'wysiwyg' => 'line1<br />line2',
            'checkbox' => true,
            'slider' => 47,
            'table' => [['A', 'B'], ['C', 'D']],
            'country' => 'AT',
            'language' => 'fr',
            'multiselect' => ['A', 'D'],
            'countrymultiselect' => ['AT', 'DE'],
            'languagemultiselect' => ['AT', 'DE'],
            'quantityValue' => new QuantityValue(123, $unit->getId()),
            'inputQuantityValue' => new InputQuantityValue('abc', $unit->getId()),
            // Added with the image marshaller (#1656). The guard above is what caught their
            // absence: the store configured two keys this round trip never wrote.
            'image' => TestHelper::createImageAsset(),
            'encryptedImage' => new EncryptedField(new ImageFieldDefinition(), TestHelper::createImageAsset()),
        ];
    }

    /**
     * Writes every value through the given engine and reads the stored object back.
     *
     * @param array<string, mixed> $values
     *
     * @return array<string, mixed> key name => loaded value
     */
    private function writeAndReadBack(string $objectKey, array $values, ?string $engine): array
    {
        $store = $this->setStoreEngine($engine);
        $object = $this->writeValues($objectKey, $values, $store);

        return $this->readValues((int) $object->getId(), $values, $store);
    }

    /**
     * @param array<string, mixed> $values
     */
    private function writeValues(
        string $objectKey,
        array $values,
        Classificationstore\StoreConfig $store
    ): Csstore {
        $object = new Csstore();
        $object->setParentId(1);
        $object->setKey($objectKey);
        $object->setPublished(true);

        $classificationStore = $object->getCsstore();
        foreach ($values as $keyName => $value) {
            $classificationStore->setLocalizedKeyValue(
                $this->getGroupId($keyName),
                $this->getKeyId($keyName, $store),
                $value
            );
        }
        $object->save();

        return $object;
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return array<string, mixed> key name => loaded value
     */
    private function readValues(int $objectId, array $values, Classificationstore\StoreConfig $store): array
    {
        $reloaded = $this->reloadStore($objectId);

        $loadedValues = [];
        foreach (array_keys($values) as $keyName) {
            $loadedValues[$keyName] = $reloaded->getLocalizedKeyValue(
                $this->getGroupId($keyName),
                $this->getKeyId($keyName, $store)
            );
        }

        return $loadedValues;
    }

    private function reloadStore(int $objectId): Classificationstore
    {
        $object = Csstore::getById($objectId, ['force' => true]);
        $this->assertInstanceOf(Csstore::class, $object);

        return $object->getCsstore();
    }

    /**
     * The three exclusions that are rows rather than values: a `type` column the key no longer
     * carries, a collection placeholder row whose two value columns are empty, and a value row
     * whose key resolves to no definition - plus a second collection id in the same group, so that
     * the group's rows do not agree on one.
     */
    private function seedComparisonExclusions(int $objectId, Classificationstore\StoreConfig $store): void
    {
        $db = Db::get();
        $dataTable = $db->quoteIdentifier(self::DATA_TABLE_PREFIX . $this->getClassId());
        $groupId = $this->getGroupId(self::INPUT_KEY);

        $db->executeStatement(
            'UPDATE ' . $dataTable . ' SET `type` = ?, `collectionId` = ?'
            . ' WHERE `id` = ? AND `fieldname` = ? AND `keyId` = ?',
            ['a-type-the-key-does-not-have', 11, $objectId, self::FIELDNAME, $this->getKeyId(self::INPUT_KEY, $store)]
        );
        $db->executeStatement(
            'UPDATE ' . $dataTable . ' SET `collectionId` = ?'
            . ' WHERE `id` = ? AND `fieldname` = ? AND `keyId` = ?',
            [22, $objectId, self::FIELDNAME, $this->getKeyId('textarea', $store)]
        );

        // a placeholder row: the key belongs to the other group, so this row carries a collection
        // id and no value at all
        $db->insert(self::DATA_TABLE_PREFIX . $this->getClassId(), [
            'id' => $objectId,
            'collectionId' => 33,
            'groupId' => $groupId,
            'keyId' => $this->getKeyId('date', $store),
            'fieldname' => self::FIELDNAME,
            'language' => 'default',
            'type' => 'date',
        ]);

        // an orphan row: no key config answers this id, so neither engine reads it
        $db->insert(self::DATA_TABLE_PREFIX . $this->getClassId(), [
            'id' => $objectId,
            'groupId' => $groupId,
            'keyId' => self::ORPHAN_KEY_ID,
            'fieldname' => self::FIELDNAME,
            'language' => 'default',
            'type' => 'input',
            'value' => 'a value nothing can read',
        ]);
    }

    private function countEavRows(int $objectId): int
    {
        return $this->countRowsFor(self::DATA_TABLE_PREFIX, $objectId)
            + $this->countRowsFor(self::GROUPS_TABLE_PREFIX, $objectId);
    }

    private function countRowsFor(string $prefix, int $objectId): int
    {
        $db = Db::get();
        $table = $prefix . $this->getClassId();

        if ($db->fetchOne('SHOW TABLES LIKE ?', [$table]) === false) {
            return 0;
        }

        return (int) $db->fetchOne(
            'SELECT COUNT(*) FROM ' . $db->quoteIdentifier($table) . ' WHERE id = ? AND fieldname = ?',
            [$objectId, self::FIELDNAME]
        );
    }

    /**
     * @return array<array<string, mixed>>
     */
    private function readDocumentRows(int $objectId): array
    {
        $db = Db::get();
        $table = self::DOCUMENT_TABLE_PREFIX . $this->getClassId();

        if ($db->fetchOne('SHOW TABLES LIKE ?', [$table]) === false) {
            return [];
        }

        return $db->fetchAllAssociative(
            'SELECT * FROM ' . $db->quoteIdentifier($table) . ' WHERE id = ? AND fieldname = ?',
            [$objectId, self::FIELDNAME]
        );
    }

    private function getClassId(): string
    {
        $class = ClassDefinition::getByName(self::CLASS_NAME);
        $this->assertInstanceOf(ClassDefinition::class, $class);

        return $class->getId();
    }

    private function setStoreEngine(?string $engine): Classificationstore\StoreConfig
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $store->setEngine($engine);
        $store->save();

        $this->createValueTables($store, self::CLASS_NAME);

        return $store;
    }

    private function getGroupId(string $keyName): int
    {
        $groupName = in_array($keyName, self::FIRST_GROUP_KEYS, true) ? self::FIRST_GROUP : self::SECOND_GROUP;

        return Classificationstore\GroupConfig::getByName($groupName)->getId();
    }

    private function getKeyId(string $keyName, Classificationstore\StoreConfig $store): int
    {
        return Classificationstore\KeyConfig::getByName($keyName, $store->getId())->getId();
    }

    private function getUserId(): int
    {
        $userListing = new User\Listing();
        $userListing->setLimit(1);
        $users = $userListing->load();

        if ($users) {
            return $users[0]->getId();
        }

        $user = new User();
        $user->setName('testuser');
        $user->save();

        return $user->getId();
    }
}
