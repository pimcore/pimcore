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
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\LockWaitTimeoutException;
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
     * AC7: the tags are read from the stored row, so an instance that carries nothing but an id - the
     * shape a listing or a delete-by-id hands over - clears them like a fully loaded one.
     */
    public function testAnInstanceCarryingOnlyAnIdClearsTheTagsToo(): void
    {
        $subGroup = $this->createSubGroup();
        $group = $this->createGroup('id_only');
        $keyConfig = $this->createKey('id_only');
        $this->assignKey($group, $keyConfig, 0, $subGroup->getIdentifier());

        $idOnly = new SubGroupConfig();
        $idOnly->setId($subGroup->getId());
        $idOnly->delete();

        $this->assertNull(SubGroupConfig::getById($subGroup->getId()));
        $this->assertNull($this->fetchTag($group, $keyConfig));

        // the row is gone, so a second delete has nothing to read and nothing to clear
        $subGroup->delete();
        $this->assertNull($this->fetchTag($group, $keyConfig));
    }

    /**
     * AC7, the mirror image: an instance that was never saved carries an identifier of its own, and
     * must not reach the tags of the stored entry that happens to answer to it.
     */
    public function testAnUnsavedInstanceClearsNoTags(): void
    {
        $subGroup = $this->createSubGroup();
        $group = $this->createGroup('unsaved');
        $keyConfig = $this->createKey('unsaved');
        $this->assignKey($group, $keyConfig, 0, $subGroup->getIdentifier());

        $unsaved = new SubGroupConfig();
        $unsaved->setStoreId($subGroup->getStoreId());
        $unsaved->setName('never saved');
        $unsaved->setIdentifier($subGroup->getIdentifier());
        $unsaved->delete();

        $this->assertNotNull(SubGroupConfig::getById($subGroup->getId()));
        $this->assertSame($subGroup->getIdentifier(), $this->fetchTag($group, $keyConfig));
    }

    /**
     * AC2: the key assignments of a store name an entry by its identifier, and nothing in them would
     * follow it to a second store, so the store of a stored entry is fixed.
     */
    public function testTheStoreOfAStoredEntryCannotBeChanged(): void
    {
        $subGroup = $this->createSubGroup();
        $group = $this->createGroup('moved');
        $keyConfig = $this->createKey('moved');
        $this->assignKey($group, $keyConfig, 0, $subGroup->getIdentifier());

        $subGroup->setStoreId($this->createStore());
        $subGroup->setDescription('moved');

        try {
            $subGroup->save();
            $this->fail('Moving a stored entry to another store must be refused');
        } catch (InvalidConfigException $e) {
            $this->addToAssertionCount(1);
        }

        $stored = Db::get()->fetchAssociative(
            'SELECT `storeId`, `description` FROM ' . self::TABLE_SUBGROUPS . ' WHERE id = ?',
            [$subGroup->getId()]
        );

        $this->assertSame($this->getStoreId(), (int) $stored['storeId']);
        $this->assertNull($stored['description'], 'a refused save must write nothing at all');
        $this->assertSame($subGroup->getIdentifier(), $this->fetchTag($group, $keyConfig));

        // the store it already has is not a change
        $subGroup->setStoreId($this->getStoreId());
        $subGroup->save();

        $this->assertSame('moved', SubGroupConfig::getById($subGroup->getId())->getDescription());
    }

    /**
     * AC3: the identifier column is compared case insensitively, so a tag that differs from the entry
     * only in case names that entry. What is stored is the identifier of the entry, not the spelling
     * the caller used, which would otherwise read as a sub group of its own.
     */
    public function testTheStoredTagIsTheIdentifierOfTheResolvedEntry(): void
    {
        $subGroup = $this->createSubGroup(null, 'Dimensions');
        $group = $this->createGroup('canonical');
        $exactKey = $this->createKey('canonical_exact');
        $mixedCaseKey = $this->createKey('canonical_mixed');

        $this->assignKey($group, $exactKey, 10, 'Dimensions');
        $this->assignKey($group, $mixedCaseKey, 20, 'dimensions');

        $this->assertSame('Dimensions', $this->fetchTag($group, $exactKey));
        $this->assertSame('Dimensions', $this->fetchTag($group, $mixedCaseKey));

        $relation = KeyGroupRelation::getByGroupAndKeyId($group->getId(), $mixedCaseKey->getId());
        $this->assertSame('Dimensions', $relation->getSubGroup());

        $relationCache = Pimcore::getContainer()->get(KeyGroupRelationCache::class);
        $this->assertSame(
            ['Dimensions'],
            $relationCache->getSubGroupOrder($group->getId()),
            'two spellings of one entry are one sub group'
        );

        // and the deletion, which matches the tags against the identifier, reaches both of them
        $subGroup->delete();

        $this->assertNull($this->fetchTag($group, $exactKey));
        $this->assertNull($this->fetchTag($group, $mixedCaseKey));
    }

    /**
     * AC4: a deletion committed between the resolution of a tag and the write that stores it would
     * leave an identifier behind that resolves to nothing. The resolution locks the row it resolved
     * to, and holds it until the write commits, so the deletion has to wait for it.
     */
    public function testResolvingASubGroupHoldsItAgainstAConcurrentDeletion(): void
    {
        $subGroup = $this->createSubGroup();
        $group = $this->createGroup('held');
        $keyConfig = $this->createKey('held');

        $db = Db::get();
        $otherConnection = $this->openSecondConnection();
        $otherConnection->executeStatement('SET SESSION innodb_lock_wait_timeout = 2');

        $db->beginTransaction();

        try {
            $this->assignKey($group, $keyConfig, 0, $subGroup->getIdentifier());

            $otherConnection->beginTransaction();

            try {
                $otherConnection->delete(self::TABLE_SUBGROUPS, ['id' => $subGroup->getId()]);
                $this->fail('An entry a relation write resolved must not be deletable before that write commits');
            } catch (LockWaitTimeoutException $e) {
                $this->addToAssertionCount(1);
            } finally {
                $otherConnection->rollBack();
            }
        } finally {
            if ($db->isTransactionActive()) {
                $db->rollBack();
            }
            $otherConnection->close();
        }
    }

    /**
     * AC4, the other half: the deletion takes the same lock before it reads a single tag. Clearing
     * them first and locking afterwards would clear the tags of a relation write that has not landed
     * yet, and leave that one behind.
     */
    public function testDeletionTakesTheLockBeforeItClearsAnyTag(): void
    {
        $subGroup = $this->createSubGroup();
        $group = $this->createGroup('contended');
        $keyConfig = $this->createKey('contended');
        $this->assignKey($group, $keyConfig, 0, $subGroup->getIdentifier());

        $db = Db::get();
        $otherConnection = $this->openSecondConnection();
        $otherConnection->beginTransaction();

        $db->executeStatement('SET SESSION innodb_lock_wait_timeout = 2');

        // an enclosing transaction: the refused delete does not roll it back, so whatever that delete
        // managed to write before it hit the lock is still readable below
        $db->beginTransaction();

        try {
            // the lock a concurrent relation write holds while it resolves the entry
            $otherConnection->fetchOne(
                'SELECT `identifier` FROM ' . self::TABLE_SUBGROUPS . ' WHERE id = ? FOR UPDATE',
                [$subGroup->getId()]
            );

            try {
                $subGroup->delete();
                $this->fail('A deletion must not pass an entry another transaction holds');
            } catch (LockWaitTimeoutException $e) {
                $this->addToAssertionCount(1);
            }

            $this->assertSame(
                $subGroup->getIdentifier(),
                $this->fetchTag($group, $keyConfig),
                'the deletion must reach the lock before it clears a single tag'
            );
        } finally {
            if ($db->isTransactionActive()) {
                $db->rollBack();
            }
            $db->executeStatement('SET SESSION innodb_lock_wait_timeout = DEFAULT');
            $otherConnection->rollBack();
            $otherConnection->close();
        }
    }

    /**
     * A tag names an entry in the vocabulary of the store its group belongs to, so a group that moves
     * would take its tags to a store whose vocabulary knows nothing of them: they would resolve to
     * nothing, the deletion of the entry they came from would no longer reach them, and saving such a
     * relation again would be refused. The move is refused instead, for as long as a tag is there.
     */
    public function testAGroupThatCarriesATagCannotBeMovedToAnotherStore(): void
    {
        $subGroup = $this->createSubGroup();
        $group = $this->createGroup('movedgroup');
        $keyConfig = $this->createKey('movedgroup');
        $this->assignKey($group, $keyConfig, 0, $subGroup->getIdentifier());

        $otherStoreId = $this->createStore();
        $group->setStoreId($otherStoreId);
        $group->setDescription('moved');

        try {
            $group->save();
            $this->fail('Moving a group that carries a tag must be refused');
        } catch (InvalidConfigException $e) {
            $this->addToAssertionCount(1);
        }

        $stored = Db::get()->fetchAssociative(
            'SELECT `storeId`, `description` FROM `classificationstore_groups` WHERE id = ?',
            [$group->getId()]
        );

        $this->assertSame($this->getStoreId(), (int) $stored['storeId']);
        $this->assertNull($stored['description'], 'a refused save must write nothing at all');

        // the tag is still the one it was, and still reachable from the entry it names
        $this->assertSame($subGroup->getIdentifier(), $this->fetchTag($group, $keyConfig));
        $subGroup->delete();
        $this->assertNull($this->fetchTag($group, $keyConfig));

        // and with no tag left, the group moves as it did before
        $group->setStoreId($otherStoreId);
        $group->save();

        $this->assertSame(
            $otherStoreId,
            (int) Db::get()->fetchOne(
                'SELECT `storeId` FROM `classificationstore_groups` WHERE id = ?',
                [$group->getId()]
            )
        );

        // put it back, so that tearDown deletes it from the store it was created in
        $group->setStoreId($this->getStoreId());
        $group->save();
    }

    /**
     * The other half: a tag on its way in must not slip past the move that was looking for it. The move
     * holds the group row, and KeyGroupRelation\Dao::resolveSubGroup() reads the store of the group
     * under the same lock, so a relation write started in parallel waits for the move rather than
     * resolving against the store the group is about to leave.
     */
    public function testMovingAGroupHoldsItAgainstAConcurrentTagWrite(): void
    {
        $subGroup = $this->createSubGroup();
        $group = $this->createGroup('contendedgroup');
        $keyConfig = $this->createKey('contendedgroup');

        $db = Db::get();
        $otherConnection = $this->openSecondConnection();
        $otherConnection->beginTransaction();

        $db->executeStatement('SET SESSION innodb_lock_wait_timeout = 2');

        try {
            // the lock a move holds while it looks for a tag it would strand
            $otherConnection->fetchOne(
                'SELECT `storeId` FROM `classificationstore_groups` WHERE id = ? FOR UPDATE',
                [$group->getId()]
            );

            try {
                $this->assignKey($group, $keyConfig, 0, $subGroup->getIdentifier());
                $this->fail('A tag must not be resolved against a group another transaction is moving');
            } catch (LockWaitTimeoutException $e) {
                $this->addToAssertionCount(1);
            }

            $this->assertNull(
                $this->fetchTag($group, $keyConfig),
                'a relation write that could not read the store of its group must leave no tag'
            );
        } finally {
            if ($db->isTransactionActive()) {
                $db->rollBack();
            }
            $db->executeStatement('SET SESSION innodb_lock_wait_timeout = DEFAULT');
            $otherConnection->rollBack();
            $otherConnection->close();
        }
    }

    /**
     * The tags a deletion has to clear are read as of now, not from the snapshot of the transaction the
     * deletion runs in. Inside an enclosing transaction whose snapshot predates a committed tag, a plain
     * read would report the assignment as untagged, skip the clearing altogether, and still delete the
     * entry - leaving a tag that resolves to nothing, and a group whose cache was never invalidated.
     */
    public function testDeletionClearsATagCommittedAfterTheSnapshotOfItsTransaction(): void
    {
        $subGroup = $this->createSubGroup();
        $group = $this->createGroup('stale');
        $keyConfig = $this->createKey('stale');
        $this->assignKey($group, $keyConfig, 0);

        $db = Db::get();
        $otherConnection = $this->openSecondConnection();

        $db->beginTransaction();

        try {
            // establishes the snapshot of the deleting transaction, while the assignment is still untagged
            $this->assertNull($this->fetchTag($group, $keyConfig));

            $otherConnection->executeStatement(
                'UPDATE `' . self::TABLE_RELATIONS . '` SET `' . self::COLUMN . '` = ?'
                . ' WHERE `groupId` = ? AND `keyId` = ?',
                [$subGroup->getIdentifier(), $group->getId(), $keyConfig->getId()]
            );

            // committed, and still invisible from inside the snapshot - which is what a plain read would see
            $this->assertNull($this->fetchTag($group, $keyConfig));

            $subGroup->delete();

            $db->commit();
        } finally {
            if ($db->isTransactionActive()) {
                $db->rollBack();
            }
            $otherConnection->close();
        }

        $this->assertNull(
            $this->fetchTag($group, $keyConfig),
            'the deletion must clear a tag committed after the snapshot it runs in'
        );
        $this->assertSame(
            0,
            (int) $db->fetchOne('SELECT COUNT(*) FROM ' . self::TABLE_SUBGROUPS . ' WHERE id = ?', [$subGroup->getId()])
        );
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

    private function openSecondConnection(): Connection
    {
        return DriverManager::getConnection(Db::get()->getParams());
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
