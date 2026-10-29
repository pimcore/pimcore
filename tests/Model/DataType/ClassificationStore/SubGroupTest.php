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

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Pimcore;
use Pimcore\Bundle\CoreBundle\Migrations\Version20261029120000;
use Pimcore\Db;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation\Listing as KeyGroupRelationListing;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelationCache;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig\Listing as SubGroupConfigListing;
use Pimcore\Model\DataObject\Csstore;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Model\Exception\InvalidConfigException;
use Pimcore\Tests\Support\Util\TestHelper;
use Psr\Log\NullLogger;

/**
 * The sub group vocabulary of a store, the tag it leaves on a key assignment, and the order a group
 * derives from it.
 *
 * @group model.datatype.classificationstore
 */
final class SubGroupTest extends AbstractClassificationStoreTest
{
    private const TABLE_SUBGROUPS = 'classificationstore_subgroups';

    private const TABLE_RELATIONS = 'classificationstore_relations';

    private const COLUMN = 'subGroup';

    private const GUARD_TABLE = 'test_subgroup_delete_guard';

    private const FIELDNAME = 'csstore';

    /**
     * @var KeyConfig[]
     */
    private array $createdKeys = [];

    /**
     * @var GroupConfig[]
     */
    private array $createdGroups = [];

    /**
     * @var SubGroupConfig[]
     */
    private array $createdSubGroups = [];

    /**
     * @var int[]
     */
    private array $createdStoreIds = [];

    protected function tearDown(): void
    {
        $db = Db::get();
        $db->executeStatement('DROP TABLE IF EXISTS `' . self::GUARD_TABLE . '`');

        foreach ($this->createdKeys as $keyConfig) {
            $keyConfig->delete();
        }
        foreach ($this->createdGroups as $group) {
            $group->delete();
        }
        foreach ($this->createdSubGroups as $subGroup) {
            $db->delete(self::TABLE_SUBGROUPS, ['id' => $subGroup->getId()]);
        }
        foreach ($this->createdStoreIds as $storeId) {
            $db->delete('classificationstore_stores', ['id' => $storeId]);
        }

        $this->createdKeys = [];
        $this->createdGroups = [];
        $this->createdSubGroups = [];
        $this->createdStoreIds = [];

        Pimcore::getContainer()->get(KeyGroupRelationCache::class)->reset();
        TestHelper::cleanUp();

        parent::tearDown();
    }

    /**
     * AC1: the dump installed this schema, so a migration that plans nothing on it is a migration
     * that agrees with the dump.
     */
    public function testTheSchemaOfTheDumpIsWhatTheMigrationWouldBuild(): void
    {
        $schemaManager = Db::get()->createSchemaManager();

        $columns = $schemaManager->listTableColumns(self::TABLE_SUBGROUPS);
        $expected = ['id', 'storeid', 'name', 'description', 'identifier', 'creationdate', 'modificationdate'];
        foreach ($expected as $column) {
            $this->assertArrayHasKey($column, $columns, self::TABLE_SUBGROUPS . ' must carry a ' . $column . ' column');
        }

        $subGroupIndexes = $schemaManager->listTableIndexes(self::TABLE_SUBGROUPS);
        $this->assertTrue($subGroupIndexes['storeid_identifier']->isUnique());
        $this->assertSame(['storeId', 'identifier'], $subGroupIndexes['storeid_identifier']->getColumns());

        $relationColumns = $schemaManager->listTableColumns(self::TABLE_RELATIONS);
        $this->assertArrayHasKey('subgroup', $relationColumns);
        $this->assertFalse($relationColumns['subgroup']->getNotnull());
        $this->assertSame(190, $relationColumns['subgroup']->getLength());

        $relationIndexes = $schemaManager->listTableIndexes(self::TABLE_RELATIONS);
        $this->assertArrayHasKey('subgroup', $relationIndexes);
        $this->assertFalse($relationIndexes['subgroup']->isUnique());
        $this->assertSame([self::COLUMN], $relationIndexes['subgroup']->getColumns());

        $this->assertContains(
            self::COLUMN,
            (new KeyGroupRelation())->getDao()->getValidTableColumns(self::TABLE_RELATIONS),
            'The valid table columns cache must know the subGroup column'
        );

        $this->assertSame([], $this->planUp(), 'the migration must be a no-op on the installed dump');
        $this->assertSame(
            [
                'ALTER TABLE `' . self::TABLE_RELATIONS . '` DROP INDEX `subGroup`, DROP COLUMN `subGroup`;',
                'DROP TABLE `' . self::TABLE_SUBGROUPS . '`;',
            ],
            $this->planDown()
        );
    }

    /**
     * AC2
     */
    public function testVocabularyEntryRoundTripsAndEnforcesIdentifierUniqueness(): void
    {
        $storeId = $this->getStoreId();
        $otherStoreId = $this->createStore();

        $entry = new SubGroupConfig();
        $entry->setStoreId($storeId);
        $entry->setName('dimensions');
        $entry->setDescription('the dimensions of the product');
        $entry->save();
        $this->createdSubGroups[] = $entry;

        $this->assertNotNull($entry->getId());
        $this->assertNotNull($entry->getIdentifier(), 'a new entry gets a generated identifier');
        $this->assertStringNotContainsString('dimensions', $entry->getIdentifier());

        foreach ([
            SubGroupConfig::getById($entry->getId()),
            SubGroupConfig::getByName('dimensions', $storeId),
            SubGroupConfig::getByIdentifier($entry->getIdentifier(), $storeId),
        ] as $reloaded) {
            $this->assertNotNull($reloaded);
            $this->assertSame($entry->getId(), $reloaded->getId());
            $this->assertSame('dimensions', $reloaded->getName());
            $this->assertSame('the dimensions of the product', $reloaded->getDescription());
            $this->assertSame($storeId, $reloaded->getStoreId());
            $this->assertSame($entry->getIdentifier(), $reloaded->getIdentifier());
        }

        $this->assertNull(SubGroupConfig::getByIdentifier($entry->getIdentifier(), $otherStoreId));

        $created = SubGroupConfig::create();
        $this->createdSubGroups[] = $created;
        $this->assertNotNull($created->getIdentifier());

        $rowsBefore = $this->countSubGroupRows($storeId);

        $colliding = new SubGroupConfig();
        $colliding->setStoreId($storeId);
        $colliding->setName('colliding');
        $colliding->setIdentifier($entry->getIdentifier());

        try {
            $colliding->save();
            $this->fail('An identifier already in use in the store must be refused');
        } catch (InvalidConfigException $e) {
            $this->addToAssertionCount(1);
        }

        $this->assertSame($rowsBefore, $this->countSubGroupRows($storeId), 'a refused save must write nothing');

        $elsewhere = new SubGroupConfig();
        $elsewhere->setStoreId($otherStoreId);
        $elsewhere->setName('elsewhere');
        $elsewhere->setIdentifier($entry->getIdentifier());
        $elsewhere->save();
        $this->createdSubGroups[] = $elsewhere;

        $this->assertNotNull($elsewhere->getId(), 'the same identifier is free in a second store');

        $listing = new SubGroupConfigListing();
        $listing->setCondition('storeId = ?', [$otherStoreId]);
        $this->assertCount(1, $listing->load());
    }

    /**
     * AC3
     */
    public function testRelationCarriesSubGroupAcrossSaveAndLoad(): void
    {
        $subGroup = $this->createSubGroup();
        $group = $this->createGroup('carried');
        $keyConfig = $this->createKey('carried');
        $this->assignKey($group, $keyConfig, 0, $subGroup->getIdentifier());

        $reloaded = KeyGroupRelation::getByGroupAndKeyId($group->getId(), $keyConfig->getId());
        $this->assertSame($subGroup->getIdentifier(), $reloaded->getSubGroup());

        $listing = new KeyGroupRelationListing();
        $listing->setCondition('groupId = ?', [$group->getId()]);
        $listed = $listing->load();

        $this->assertCount(1, $listed);
        $this->assertSame($subGroup->getIdentifier(), $listed[0]->getSubGroup());

        $legacy = unserialize($this->legacyPayload($group->getId(), $keyConfig->getId()));

        $this->assertInstanceOf(KeyGroupRelation::class, $legacy);
        $this->assertNull($legacy->getSubGroup(), 'a payload produced without the property must not throw');
        $this->assertFalse($legacy->isSubGroupInitialized());
    }

    /**
     * AC4
     */
    public function testUnknownSubGroupIsRefused(): void
    {
        $subGroup = $this->createSubGroup();
        $group = $this->createGroup('refused');
        $keyConfig = $this->createKey('refused');
        $this->assignKey($group, $keyConfig, 10, $subGroup->getIdentifier());

        $unknown = KeyGroupRelation::getByGroupAndKeyId($group->getId(), $keyConfig->getId());
        $unknown->setSubGroup('no-such-sub-group');
        $unknown->setSorter(99);

        try {
            $unknown->save();
            $this->fail('A sub group that names no vocabulary entry must be refused');
        } catch (InvalidConfigException $e) {
            $this->addToAssertionCount(1);
        }

        $this->assertSame($subGroup->getIdentifier(), $this->fetchTag($group, $keyConfig));
        $this->assertSame(10, $this->fetchSorter($group, $keyConfig), 'a refused save must write nothing at all');

        // an entry that exists, but in a store the group does not belong to
        $elsewhere = $this->createSubGroup($this->createStore());

        $foreign = KeyGroupRelation::getByGroupAndKeyId($group->getId(), $keyConfig->getId());
        $foreign->setSubGroup($elsewhere->getIdentifier());
        $foreign->setSorter(99);

        try {
            $foreign->save();
            $this->fail('A sub group of another store must be refused');
        } catch (InvalidConfigException $e) {
            $this->addToAssertionCount(1);
        }

        $this->assertSame($subGroup->getIdentifier(), $this->fetchTag($group, $keyConfig));
        $this->assertSame(10, $this->fetchSorter($group, $keyConfig));
    }

    /**
     * AC5
     */
    public function testUntouchedSaveKeepsTheTag(): void
    {
        $subGroup = $this->createSubGroup();
        $group = $this->createGroup('untouched');
        $keyConfig = $this->createKey('untouched');
        $this->assignKey($group, $keyConfig, 0, $subGroup->getIdentifier());

        // the assignment pattern the documentation shows: group id, key id, save
        $assignment = new KeyGroupRelation();
        $assignment->setGroupId($group->getId());
        $assignment->setKeyId($keyConfig->getId());
        $assignment->save();

        $this->assertSame($subGroup->getIdentifier(), $this->fetchTag($group, $keyConfig));

        $listing = new KeyGroupRelationListing();
        $listing->setCondition('groupId = ?', [$group->getId()]);
        $loaded = $listing->load()[0];
        $loaded->save();

        $this->assertSame($subGroup->getIdentifier(), $this->fetchTag($group, $keyConfig));
    }

    /**
     * AC6
     */
    public function testExplicitNullClearsTheTag(): void
    {
        $subGroup = $this->createSubGroup();
        $group = $this->createGroup('cleared');
        $keyConfig = $this->createKey('cleared');
        $this->assignKey($group, $keyConfig, 0, $subGroup->getIdentifier());

        $relation = KeyGroupRelation::getByGroupAndKeyId($group->getId(), $keyConfig->getId());
        $relation->setSubGroup(null);
        $relation->save();

        $this->assertNull($this->fetchTag($group, $keyConfig));
        $this->assertNull(KeyGroupRelation::getByGroupAndKeyId($group->getId(), $keyConfig->getId())->getSubGroup());
    }

    /**
     * AC7
     */
    public function testDeletingVocabularyEntryClearsItsTags(): void
    {
        $subGroup = $this->createSubGroup();
        $firstGroup = $this->createGroup('deleted_a');
        $secondGroup = $this->createGroup('deleted_b');
        $firstKey = $this->createKey('deleted_a');
        $secondKey = $this->createKey('deleted_b');

        $this->assignKey($firstGroup, $firstKey, 0, $subGroup->getIdentifier());
        $this->assignKey($secondGroup, $secondKey, 0, $subGroup->getIdentifier());

        $relationCache = Pimcore::getContainer()->get(KeyGroupRelationCache::class);
        $this->assertSame(
            $subGroup->getIdentifier(),
            $relationCache->getRelation($firstGroup->getId(), $firstKey->getId())->getSubGroup()
        );
        $this->assertSame(
            $subGroup->getIdentifier(),
            $relationCache->getRelation($secondGroup->getId(), $secondKey->getId())->getSubGroup()
        );

        $subGroup->delete();

        $this->assertNull($this->fetchTag($firstGroup, $firstKey));
        $this->assertNull($this->fetchTag($secondGroup, $secondKey));
        $this->assertNull(
            $relationCache->getRelation($firstGroup->getId(), $firstKey->getId())->getSubGroup(),
            'the held relations must stop reporting the tag without a kernel reset'
        );
        $this->assertNull(
            $relationCache->getRelation($secondGroup->getId(), $secondKey->getId())->getSubGroup()
        );
    }

    /**
     * AC7, the other half: the vocabulary row and the tags are one unit of work.
     */
    public function testARefusedDeleteLeavesTheTagsUntouched(): void
    {
        $subGroup = $this->createSubGroup();
        $group = $this->createGroup('undeleted');
        $keyConfig = $this->createKey('undeleted');
        $this->assignKey($group, $keyConfig, 0, $subGroup->getIdentifier());

        $this->blockDeletionOf($subGroup);

        try {
            $subGroup->delete();
            $this->fail('A row that cannot be deleted must not report success');
        } catch (ForeignKeyConstraintViolationException $e) {
            $this->addToAssertionCount(1);
        }

        $this->assertFalse(Db::get()->isTransactionActive(), 'a refused delete must not leave its transaction open');
        $this->assertSame($subGroup->getIdentifier(), $this->fetchTag($group, $keyConfig));
        $this->assertSame(1, $this->countSubGroupRows($subGroup->getStoreId()));
    }

    /**
     * AC8
     */
    public function testSubGroupOrderIsDerivedFromSorter(): void
    {
        $first = $this->createSubGroup();
        $second = $this->createSubGroup();

        $group = $this->createGroup('ordered');
        $keyB1 = $this->createKey('ordered_b1');
        $keyA = $this->createKey('ordered_a');
        $keyB2 = $this->createKey('ordered_b2');

        $this->assignKey($group, $keyB1, 10, $second->getIdentifier());
        $this->assignKey($group, $keyA, 20, $first->getIdentifier());
        $this->assignKey($group, $keyB2, 30, $second->getIdentifier());

        $relationCache = Pimcore::getContainer()->get(KeyGroupRelationCache::class);

        $this->assertSame(
            [$second->getIdentifier(), $first->getIdentifier()],
            $relationCache->getSubGroupOrder($group->getId())
        );

        // the first key of the leading sub group moves last, so the other one now opens the group
        $moved = KeyGroupRelation::getByGroupAndKeyId($group->getId(), $keyB1->getId());
        $moved->setSorter(40);
        $moved->save();

        $this->assertSame(
            [$first->getIdentifier(), $second->getIdentifier()],
            $relationCache->getSubGroupOrder($group->getId())
        );
        $this->assertSame(
            $second->getIdentifier(),
            $this->fetchTag($group, $keyB1),
            'moving a key writes its sorter and leaves its tag as it was'
        );

        // an all digit identifier is a valid one, and must not come back as an int
        $numeric = $this->createSubGroup(null, '2024');
        $numericGroup = $this->createGroup('numeric');
        $this->assignKey($numericGroup, $this->createKey('numeric'), 0, $numeric->getIdentifier());

        $this->assertSame(['2024'], $relationCache->getSubGroupOrder($numericGroup->getId()));

        // a sub group exists in a group as long as a key the user can see carries it
        $hiddenGroup = $this->createGroup('hidden');
        $hiddenKey = $this->createKey('hidden', false);
        $this->assignKey($hiddenGroup, $hiddenKey, 0, $first->getIdentifier());

        $this->assertCount(1, $relationCache->getRelations($hiddenGroup->getId()), 'the relation is still there');
        $this->assertSame([], $relationCache->getSubGroupOrder($hiddenGroup->getId()));

        $untagged = $this->createGroup('untagged');
        $this->assignKey($untagged, $this->createKey('untagged'), 0);

        $this->assertSame([], $relationCache->getSubGroupOrder($untagged->getId()));
    }

    /**
     * AC8: a sub group every key of which is disabled is left out of the order entirely, and comes
     * back in its first-occurring position as soon as one of its keys is visible again.
     */
    public function testASubGroupWithoutAVisibleKeyIsLeftOut(): void
    {
        $hidden = $this->createSubGroup();
        $shown = $this->createSubGroup();

        $group = $this->createGroup('partly_hidden');
        $firstHiddenKey = $this->createKey('partly_hidden_first', false);
        $shownKey = $this->createKey('partly_hidden_shown');
        $secondHiddenKey = $this->createKey('partly_hidden_second', false);

        $this->assignKey($group, $firstHiddenKey, 10, $hidden->getIdentifier());
        $this->assignKey($group, $shownKey, 20, $shown->getIdentifier());
        $this->assignKey($group, $secondHiddenKey, 30, $hidden->getIdentifier());

        $relationCache = Pimcore::getContainer()->get(KeyGroupRelationCache::class);

        $this->assertCount(3, $relationCache->getRelations($group->getId()), 'the relations are still there');
        $this->assertSame([$shown->getIdentifier()], $relationCache->getSubGroupOrder($group->getId()));

        // the key that opens the group becomes visible again, so its sub group leads the order
        $firstHiddenKey->setEnabled(true);
        $firstHiddenKey->save();

        $this->assertSame(
            [$hidden->getIdentifier(), $shown->getIdentifier()],
            $relationCache->getSubGroupOrder($group->getId())
        );
    }

    /**
     * AC9
     */
    public function testTagIsInertToResolution(): void
    {
        $subGroup = $this->createSubGroup();
        $group = $this->createGroup('inert');
        $mandatoryKey = $this->createKey('inert_mandatory');
        $plainKey = $this->createKey('inert_plain');

        $this->assignKey($group, $mandatoryKey, 0, null, true);
        $this->assignKey($group, $plainKey, 1);

        $object = new Csstore();
        $object->setParentId(1);
        $object->setKey('inert');
        $object->setPublished(true);
        $object->getCsstore()->setActiveGroups([$group->getId() => true]);
        $object->getCsstore()->setLocalizedKeyValue($group->getId(), $mandatoryKey->getId(), 'a value');
        $object->save();

        $untaggedLayout = $this->enrich($object);
        $untaggedKeys = $this->relationKeyIds($group);
        $untaggedMessage = $this->validationMessage($object, $group, $mandatoryKey);
        $this->assertNotSame('', $untaggedMessage, 'precondition: the mandatory key fails validation');

        foreach ([$mandatoryKey, $plainKey] as $keyConfig) {
            $relation = KeyGroupRelation::getByGroupAndKeyId($group->getId(), $keyConfig->getId());
            $relation->setSubGroup($subGroup->getIdentifier());
            $relation->save();
        }

        $this->assertSame($subGroup->getIdentifier(), $this->fetchTag($group, $plainKey), 'precondition: tagged');

        $this->assertSame($untaggedLayout, $this->enrich($object));
        $this->assertSame($untaggedKeys, $this->relationKeyIds($group));
        $this->assertSame($untaggedMessage, $this->validationMessage($object, $group, $mandatoryKey));
    }

    private function getStoreId(): int
    {
        return StoreConfig::getByName('teststore')->getId();
    }

    private function createStore(): int
    {
        $store = new StoreConfig();
        $store->setName(uniqid('subgroupstore', true));
        $store->save();

        $this->createdStoreIds[] = $store->getId();

        return $store->getId();
    }

    private function createSubGroup(?int $storeId = null, ?string $identifier = null): SubGroupConfig
    {
        $subGroup = new SubGroupConfig();
        $subGroup->setStoreId($storeId ?? $this->getStoreId());
        $subGroup->setName(uniqid('subgroup', true));
        if ($identifier !== null) {
            $subGroup->setIdentifier($identifier);
        }
        $subGroup->save();

        $this->createdSubGroups[] = $subGroup;

        return $subGroup;
    }

    private function createGroup(string $label): GroupConfig
    {
        $group = new GroupConfig();
        $group->setStoreId($this->getStoreId());
        $group->setName($this->uniqueName($label));
        $group->save();

        $this->createdGroups[] = $group;

        return $group;
    }

    private function createKey(string $label, bool $enabled = true): KeyConfig
    {
        $name = $this->uniqueName($label);

        $definition = new Input();
        $definition->setName($name);

        $keyConfig = new KeyConfig();
        $keyConfig->setStoreId($this->getStoreId());
        $keyConfig->setName($name);
        $keyConfig->setDescription('description of ' . $label);
        $keyConfig->setEnabled($enabled);
        $keyConfig->setType($definition->getFieldType());
        $keyConfig->setDefinition(json_encode($definition));
        $keyConfig->save();

        $this->createdKeys[] = $keyConfig;

        return $keyConfig;
    }

    private function assignKey(
        GroupConfig $group,
        KeyConfig $keyConfig,
        int $sorter = 0,
        ?string $subGroup = null,
        bool $mandatory = false
    ): void {
        $relation = new KeyGroupRelation();
        $relation->setGroupId($group->getId());
        $relation->setKeyId($keyConfig->getId());
        $relation->setSorter($sorter);
        $relation->setMandatory($mandatory);
        if ($subGroup !== null) {
            $relation->setSubGroup($subGroup);
        }
        $relation->save();
    }

    /**
     * @return string[]
     */
    private function planUp(): array
    {
        $db = Db::get();
        $migration = new Version20261029120000($db, new NullLogger());
        $migration->up($db->createSchemaManager()->introspectSchema());

        return array_map(static fn ($query) => $query->getStatement(), $migration->getSql());
    }

    /**
     * @return string[]
     */
    private function planDown(): array
    {
        $db = Db::get();
        $migration = new Version20261029120000($db, new NullLogger());
        $migration->down($db->createSchemaManager()->introspectSchema());

        return array_map(static fn ($query) => $query->getStatement(), $migration->getSql());
    }

    /**
     * A foreign key that refuses the delete of the vocabulary row, so that the tags it would have
     * cleared a moment earlier have to be rolled back with it.
     */
    private function blockDeletionOf(SubGroupConfig $subGroup): void
    {
        $db = Db::get();
        $db->executeStatement(
            'CREATE TABLE `' . self::GUARD_TABLE . '` ('
            . '`subGroupId` INT(11) unsigned NOT NULL, PRIMARY KEY (`subGroupId`), '
            . 'CONSTRAINT `FK_' . self::GUARD_TABLE . '` FOREIGN KEY (`subGroupId`) '
            . 'REFERENCES `' . self::TABLE_SUBGROUPS . '` (`id`)'
            . ') DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci'
        );
        $db->insert(self::GUARD_TABLE, ['subGroupId' => $subGroup->getId()]);
    }

    private function legacyPayload(int $groupId, int $keyId): string
    {
        $properties = [
            'keyId' => $keyId,
            'groupId' => $groupId,
            'name' => 'legacy',
            'description' => '',
            'definition' => '{}',
            'type' => 'input',
            'sorter' => 0,
            'groupName' => 'legacy',
            'mandatory' => false,
            'enabled' => true,
        ];

        $payload = 'O:' . strlen(KeyGroupRelation::class) . ':"' . KeyGroupRelation::class . '":'
            . count($properties) . ':{';

        foreach ($properties as $name => $value) {
            $property = "\0*\0" . $name;
            $payload .= 's:' . strlen($property) . ':"' . $property . '";' . serialize($value);
        }

        return $payload . '}';
    }

    /**
     * The enriched layout reduced to what it says about a group and its keys, so that a difference
     * reads as one rather than as a dump of two object graphs.
     *
     * @return array<int, array<string, mixed>>
     */
    private function enrich(Csstore $object): array
    {
        $fieldDefinition = $object->getClass()->getFieldDefinition(self::FIELDNAME);
        $fieldDefinition->enrichLayoutDefinition($object);

        $layout = [];
        foreach ($fieldDefinition->activeGroupDefinitions as $groupId => $group) {
            $layout[$groupId] = [
                'name' => $group['name'],
                'id' => $group['id'],
                'description' => $group['description'],
                'keys' => array_map(static fn (array $key) => [
                    'name' => $key['name'],
                    'id' => $key['id'],
                    'description' => $key['description'],
                    'definition' => json_encode($key['definition']),
                ], $group['keys']),
            ];
        }

        return $layout;
    }

    /**
     * @return int[]
     */
    private function relationKeyIds(GroupConfig $group): array
    {
        return array_map(
            static fn (KeyGroupRelation $relation) => $relation->getKeyId(),
            $group->getRelations()
        );
    }

    /**
     * The message the mandatory key of the group produces when it holds no value.
     */
    private function validationMessage(Csstore $object, GroupConfig $group, KeyConfig $mandatoryKey): string
    {
        $data = $object->getCsstore();
        $data->setLocalizedKeyValue($group->getId(), $mandatoryKey->getId(), null);

        try {
            $object->getClass()->getFieldDefinition(self::FIELDNAME)->checkValidity($data);
        } catch (ValidationException $e) {
            return $e->getMessage();
        }

        return '';
    }

    private function countSubGroupRows(int $storeId): int
    {
        return (int) Db::get()->fetchOne(
            'SELECT COUNT(*) FROM ' . self::TABLE_SUBGROUPS . ' WHERE storeId = ?',
            [$storeId]
        );
    }

    private function fetchTag(GroupConfig $group, KeyConfig $keyConfig): ?string
    {
        $tag = Db::get()->fetchOne(
            'SELECT `subGroup` FROM ' . self::TABLE_RELATIONS . ' WHERE groupId = ? AND keyId = ?',
            [$group->getId(), $keyConfig->getId()]
        );

        return is_string($tag) ? $tag : null;
    }

    private function fetchSorter(GroupConfig $group, KeyConfig $keyConfig): int
    {
        return (int) Db::get()->fetchOne(
            'SELECT `sorter` FROM ' . self::TABLE_RELATIONS . ' WHERE groupId = ? AND keyId = ?',
            [$group->getId(), $keyConfig->getId()]
        );
    }

    /**
     * Underscores only: a field definition name has to pass the identifier check of Data::setName().
     */
    private function uniqueName(string $label): string
    {
        return 'subgroup_' . $label . '_' . uniqid();
    }
}
