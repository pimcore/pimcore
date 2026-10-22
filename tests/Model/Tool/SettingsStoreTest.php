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

namespace Pimcore\Tests\Model\Tool;

use Pimcore\Db;
use Pimcore\Model\Tool\SettingsStore;
use Pimcore\Tests\Support\Test\ModelTestCase;

class SettingsStoreTest extends ModelTestCase
{
    protected function doTest(float|bool|int|string $data, ?string $scope, string $type): void
    {
        $db = Db::get();

        //test creating
        $id = 'my-id';
        SettingsStore::set($id, $data, $type, $scope);

        //test loading
        $setting = SettingsStore::get($id, $scope);
        $this->assertEquals($data, $setting->getData());
        $assetMethod = 'assertIs' . ucfirst($type);
        $this->$assetMethod($setting->getData());
        $queryResult = $db->fetchOne('SELECT id FROM ' . SettingsStore\Dao::TABLE_NAME . ' WHERE id = :id AND scope = :scope', [
            'id' => $id,
            'scope' => (string) $scope,
        ]);
        $this->assertEquals($id, $queryResult);

        $this->assertEquals($scope, $setting->getScope());

        //test scope
        if ($scope) {
            $ids = SettingsStore::getIdsByScope($scope);
            $this->assertTrue(in_array($id, $ids), 'Get settings store by scope');
        }

        //test updating
        $data = 'updated_data';
        SettingsStore::set($id, $data, SettingsStore::TYPE_STRING, $scope);
        $setting = SettingsStore::get($id, $scope);
        $this->assertEquals($data, $setting->getData());

        //test delete
        SettingsStore::delete($id, $scope);
        $queryResult = $db->fetchOne('SELECT id FROM ' . SettingsStore\Dao::TABLE_NAME . ' WHERE id = :id AND scope = :scope', [
            'id' => $id,
            'scope' => (string) $scope,
        ]);
        $this->assertFalse($queryResult);
    }

    public function testCreateStringEntry(): void
    {
        $this->doTest('this is a string', null, SettingsStore::TYPE_STRING);
        $this->doTest('this is another string with scope', 'my-scope', SettingsStore::TYPE_STRING);
    }

    public function testCreateIntegerEntry(): void
    {
        $this->doTest(123, null, SettingsStore::TYPE_INTEGER);
        $this->doTest(321, 'my-scope', SettingsStore::TYPE_INTEGER);
    }

    public function testCreateBoolEntry(): void
    {
        $this->doTest(true, null, SettingsStore::TYPE_BOOLEAN);
        $this->doTest(false, 'my-scope', SettingsStore::TYPE_BOOLEAN);
    }

    public function testCreateFloatEntry(): void
    {
        $this->doTest(2154.12, null, SettingsStore::TYPE_FLOAT);
        $this->doTest(2541.1247, 'my-scope', SettingsStore::TYPE_FLOAT);
    }

    public function testScoping(): void
    {
        SettingsStore::set('my-id1', 'some-data-1-scopeless', SettingsStore::TYPE_STRING);
        SettingsStore::set('my-id1', 'some-data-1', SettingsStore::TYPE_STRING, 'scope1');
        SettingsStore::set('my-id1', 'some-data-1-scope-2', SettingsStore::TYPE_STRING, 'scope2');
        SettingsStore::set('my-id2', 'some-data-2', SettingsStore::TYPE_STRING, 'scope1');
        SettingsStore::set('my-id3', 'some-data-3', SettingsStore::TYPE_STRING, 'scope2');
        SettingsStore::set('my-id4', 'some-data-4', SettingsStore::TYPE_STRING, 'scope1');

        $ids = SettingsStore::getIdsByScope('scope1');
        $this->assertTrue(in_array('my-id1', $ids), 'Get settings store by scope');
        $this->assertFalse(in_array('my-id3', $ids), 'Get settings store by scope');
        $this->assertEquals(3, count($ids));

        $ids = SettingsStore::getIdsByScope('scopeX');
        $this->assertEquals(0, count($ids));

        $this->assertEquals(SettingsStore::get('my-id1')->getData(), 'some-data-1-scopeless');
        $this->assertEquals(SettingsStore::get('my-id1', 'scope1')->getData(), 'some-data-1');
        $this->assertEquals(SettingsStore::get('my-id1', 'scope2')->getData(), 'some-data-1-scope-2');

        SettingsStore::delete('my-id1');
        $this->assertEquals(SettingsStore::get('my-id1', 'scope1')->getData(), 'some-data-1');
        $this->assertNull(SettingsStore::get('my-id1'));
    }

    public function testNotExistingSettings(): void
    {
        SettingsStore::set('my-id1x', true, SettingsStore::TYPE_BOOLEAN);

        $setting = SettingsStore::get('my-id1x');
        $this->assertTrue($setting->getData());

        SettingsStore::set('my-id1x', false, SettingsStore::TYPE_BOOLEAN);

        $setting = SettingsStore::get('my-id1x');
        $this->assertFalse($setting->getData());

        SettingsStore::delete('my-id1x');
        $setting = SettingsStore::get('my-id1x');
        $this->assertNull($setting);
    }

    public function testScopeSnapshot(): void
    {
        $db = Db::get();
        foreach (['snapshot-scope', 'snapshot-other-scope'] as $scope) {
            foreach (SettingsStore::getIdsByScope($scope) as $id) {
                SettingsStore::delete($id, $scope);
            }
        }
        SettingsStore::set('snapshot-1', 'data-1', SettingsStore::TYPE_STRING, 'snapshot-scope');
        SettingsStore::set('snapshot-2', 42, SettingsStore::TYPE_INTEGER, 'snapshot-scope');
        SettingsStore::set('snapshot-1', 'other-scope', SettingsStore::TYPE_STRING, 'snapshot-other-scope');

        $this->assertNull(SettingsStore::getFromScopeSnapshot('snapshot-1', 'snapshot-scope'), 'no snapshot taken yet');

        $ids = SettingsStore::loadScopeSnapshot('snapshot-scope');
        $this->assertEqualsCanonicalizing(SettingsStore::getIdsByScope('snapshot-scope'), $ids);

        // entries come out exactly as get() returns them, types included
        foreach (['snapshot-1', 'snapshot-2'] as $id) {
            $expected = SettingsStore::get($id, 'snapshot-scope');
            $actual = SettingsStore::getFromScopeSnapshot($id, 'snapshot-scope');
            $this->assertSame($expected->getId(), $actual->getId());
            $this->assertSame($expected->getScope(), $actual->getScope());
            $this->assertSame($expected->getType(), $actual->getType());
            $this->assertSame($expected->getData(), $actual->getData());
        }
        $this->assertSame(42, SettingsStore::getFromScopeSnapshot('snapshot-2', 'snapshot-scope')->getData());
        $this->assertNull(SettingsStore::getFromScopeSnapshot('snapshot-missing', 'snapshot-scope'));
        $this->assertNull(SettingsStore::getFromScopeSnapshot('snapshot-1', 'snapshot-other-scope'), 'only the loaded scope is in memory');

        // served from memory: a change made behind the model's back is not seen ...
        $db->update(SettingsStore\Dao::TABLE_NAME, ['data' => 'changed'], ['id' => 'snapshot-1', 'scope' => 'snapshot-scope']);
        $this->assertSame('data-1', SettingsStore::getFromScopeSnapshot('snapshot-1', 'snapshot-scope')->getData());
        // ... and a returned entry is a copy
        SettingsStore::getFromScopeSnapshot('snapshot-1', 'snapshot-scope')->setData('modified');
        $this->assertSame('data-1', SettingsStore::getFromScopeSnapshot('snapshot-1', 'snapshot-scope')->getData());

        // the next snapshot of the scope replaces it
        SettingsStore::loadScopeSnapshot('snapshot-scope');
        $this->assertSame('changed', SettingsStore::getFromScopeSnapshot('snapshot-1', 'snapshot-scope')->getData());

        // writing to another scope keeps it, set() and delete() on the scope drop it
        SettingsStore::set('snapshot-1', 'other', SettingsStore::TYPE_STRING, 'snapshot-other-scope');
        $this->assertNotNull(SettingsStore::getFromScopeSnapshot('snapshot-1', 'snapshot-scope'));
        SettingsStore::set('snapshot-3', 'data-3', SettingsStore::TYPE_STRING, 'snapshot-scope');
        $this->assertNull(SettingsStore::getFromScopeSnapshot('snapshot-1', 'snapshot-scope'));

        SettingsStore::loadScopeSnapshot('snapshot-scope');
        $this->assertNotNull(SettingsStore::getFromScopeSnapshot('snapshot-3', 'snapshot-scope'));
        SettingsStore::delete('snapshot-3', 'snapshot-scope');
        $this->assertNull(SettingsStore::getFromScopeSnapshot('snapshot-1', 'snapshot-scope'));
        $this->assertNull(SettingsStore::get('snapshot-3', 'snapshot-scope'));
    }
}
