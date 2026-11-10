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
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig;
use Pimcore\Tests\Support\Test\ModelTestCase;

/**
 * Disabling a key deletes its group relations once, on the transition to disabled, and not on every
 * later save of the key.
 *
 * @group model.classificationstore.keydisable
 */
final class KeyDisableTest extends ModelTestCase
{
    use ClassificationStoreAutoIncrementTrait;

    private const TABLE_STORES = 'classificationstore_stores';

    private const TABLE_GROUPS = 'classificationstore_groups';

    private const TABLE_KEYS = 'classificationstore_keys';

    private const TABLE_RELATIONS = 'classificationstore_relations';

    private ?int $storeId = null;

    protected function setUp(): void
    {
        parent::setUp();

        $store = new StoreConfig();
        $store->setName(uniqid('keydisable', true));
        $store->save();
        $this->storeId = $store->getId();
    }

    protected function tearDown(): void
    {
        $db = Db::get();

        $db->executeStatement(
            'DELETE FROM ' . self::TABLE_RELATIONS . ' WHERE keyId IN (SELECT id FROM ' . self::TABLE_KEYS
            . ' WHERE storeId = ?)',
            [$this->storeId]
        );
        $db->delete(self::TABLE_KEYS, ['storeId' => $this->storeId]);
        $db->delete(self::TABLE_GROUPS, ['storeId' => $this->storeId]);
        $db->delete(self::TABLE_STORES, ['id' => $this->storeId]);

        $this->restoreClassificationStoreAutoIncrement();

        parent::tearDown();
    }

    public function testASaveOfADisabledKeyLeavesItsRelations(): void
    {
        $group = $this->createGroup();
        $key = $this->createKey(true);
        $this->relate($group, $key, 3);

        $key->setEnabled(false);
        $key->save();
        $this->assertSame(0, $this->countRelations($key));

        $this->relate($group, $key, 7, 1, 'tag');

        // twice, to show the relation is kept rather than removed by a later save
        foreach (['first', 'second'] as $description) {
            $key->setDescription($description);
            $key->save();

            $row = Db::get()->fetchAssociative(
                'SELECT sorter, mandatory, subGroup FROM ' . self::TABLE_RELATIONS . ' WHERE keyId = ?',
                [$key->getId()]
            );
            $this->assertSame(['sorter' => 7, 'mandatory' => 1, 'subGroup' => 'tag'], array_map(
                static fn ($value) => is_numeric($value) ? (int) $value : $value,
                $row
            ));
        }
    }

    public function testTheTransitionToDisabledDeletesEveryRelationOfTheKeyOnly(): void
    {
        $groupA = $this->createGroup();
        $groupB = $this->createGroup();
        $key = $this->createKey(true);
        $other = $this->createKey(true);

        foreach ([$groupA, $groupB] as $group) {
            $this->relate($group, $key, 1, 1, 'tag', '{"indexed":false}', '{"defaultUnit":"cm"}');
        }
        $this->relate($groupA, $other, 2);

        $key->setEnabled(false);
        $key->save();

        $this->assertSame(0, $this->countRelations($key));
        $this->assertSame(1, $this->countRelations($other));
    }

    public function testEnablingAndSavingAnEnabledKeyDeleteNothing(): void
    {
        $group = $this->createGroup();
        $key = $this->createKey(false);
        $this->relate($group, $key, 1);

        $key->setEnabled(true);
        $key->save();
        $this->assertSame(1, $this->countRelations($key));

        $key->setDescription('changed');
        $key->save();
        $this->assertSame(1, $this->countRelations($key));
        $this->assertTrue(KeyConfig::getById($key->getId(), true)->getEnabled());
    }

    public function testCreatingADisabledKeyDeletesNothing(): void
    {
        $group = $this->createGroup();
        $existing = $this->createKey(true);
        $this->relate($group, $existing, 1);

        $created = $this->createKey(false);

        $this->assertSame(1, $this->countRelations($existing));
        $this->assertFalse(KeyConfig::getById($created->getId(), true)->getEnabled());
    }

    public function testAStoredNullCountsAsDisabled(): void
    {
        $group = $this->createGroup();
        $db = Db::get();
        $db->insert(self::TABLE_KEYS, ['storeId' => $this->storeId, 'name' => uniqid('nullkey', true), 'type' => 'input']);
        $keyId = (int) $db->lastInsertId();
        $this->assertNull($db->fetchOne('SELECT enabled FROM ' . self::TABLE_KEYS . ' WHERE id = ?', [$keyId]));
        $db->insert(self::TABLE_RELATIONS, ['groupId' => $group->getId(), 'keyId' => $keyId]);

        $key = KeyConfig::getById($keyId, true);
        $key->setEnabled(false);
        $key->setDescription('changed');
        $key->save();

        $this->assertSame(1, $this->countRelations($key));
    }

    private function createGroup(): GroupConfig
    {
        $group = new GroupConfig();
        $group->setStoreId($this->storeId);
        $group->setName(uniqid('group', true));
        $group->save();

        return $group;
    }

    private function createKey(bool $enabled): KeyConfig
    {
        $key = new KeyConfig();
        $key->setStoreId($this->storeId);
        $key->setName(uniqid('key', true));
        $key->setType('input');
        $key->setEnabled($enabled);
        $key->save();

        return $key;
    }

    private function relate(
        GroupConfig $group,
        KeyConfig $key,
        int $sorter,
        int $mandatory = 0,
        ?string $subGroup = null,
        ?string $metadata = null,
        ?string $definitionOverride = null
    ): void {
        Db::get()->insert(self::TABLE_RELATIONS, [
            'groupId' => $group->getId(),
            'keyId' => $key->getId(),
            'sorter' => $sorter,
            'mandatory' => $mandatory,
            'subGroup' => $subGroup,
            'metadata' => $metadata,
            'definitionOverride' => $definitionOverride,
        ]);
    }

    private function countRelations(KeyConfig $key): int
    {
        return (int) Db::get()->fetchOne(
            'SELECT COUNT(*) FROM ' . self::TABLE_RELATIONS . ' WHERE keyId = ?',
            [$key->getId()]
        );
    }
}
