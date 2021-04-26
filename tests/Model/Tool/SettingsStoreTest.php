<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Tests\Model\Tool;

use Pimcore\Db;
use Pimcore\Model\Tool\SettingsStore;
use Pimcore\Tests\Test\ModelTestCase;

class SettingsStoreTest extends ModelTestCase
{
    protected function doTest($data, $scope, $type)
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
        SettingsStore::set($id, $data, 'string', $scope);
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

    public function testCreateStringEntry()
    {
        $this->doTest('this is a string', null, 'string');
        $this->doTest('this is another string with scope', 'my-scope', 'string');
    }

    public function testCreateIntegerEntry()
    {
        $this->doTest(123, null, 'int');
        $this->doTest(321, 'my-scope', 'int');
    }

    public function testCreateBoolEntry()
    {
        $this->doTest(true, null, 'bool');
        $this->doTest(false, 'my-scope', 'bool');
    }

    public function testCreateFloatEntry()
    {
        $this->doTest(2154.12, null, 'float');
        $this->doTest(2541.1247, 'my-scope', 'float');
    }

    public function testScoping()
    {
        SettingsStore::set('my-id1', 'some-data-1-scopeless', 'string');
        SettingsStore::set('my-id1', 'some-data-1', 'string', 'scope1');
        SettingsStore::set('my-id1', 'some-data-1-scope-2', 'string', 'scope2');
        SettingsStore::set('my-id2', 'some-data-2', 'string', 'scope1');
        SettingsStore::set('my-id3', 'some-data-3', 'string', 'scope2');
        SettingsStore::set('my-id4', 'some-data-4', 'string', 'scope1');

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

    public function testNotExistingSettings()
    {
        SettingsStore::set('my-id1x', true, 'bool');

        $setting = SettingsStore::get('my-id1x');
        $this->assertTrue($setting->getData());

        SettingsStore::set('my-id1x', false, 'bool');

        $setting = SettingsStore::get('my-id1x');
        $this->assertFalse($setting->getData());

        SettingsStore::delete('my-id1x');
        $setting = SettingsStore::get('my-id1x');
        $this->assertNull($setting);
    }
}
