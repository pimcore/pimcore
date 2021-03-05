<?php

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
        SettingsStore::set($id, $data, $scope, $type);

        //test loading
        $setting = SettingsStore::get($id);
        $this->assertEquals($data, $setting->getData());
        $assetMethod = 'assertIs' . ucfirst($type);
        $this->$assetMethod($setting->getData());
        $queryResult = $db->fetchOne('SELECT id FROM ' . SettingsStore\Dao::TABLE_NAME . ' WHERE id = ?', $id);
        $this->assertEquals($id, $queryResult);

        $this->assertEquals($scope, $setting->getScope());

        //test scope
        if ($scope) {
            $ids = SettingsStore::getIdsByScope($scope);
            $this->assertTrue(in_array($id, $ids), 'Get settings store by scope');
        }

        //test updating
        $data = 'updated_data';
        SettingsStore::set($id, $data, $scope, 'string');
        $setting = SettingsStore::get($id);
        $this->assertEquals($data, $setting->getData());

        //test delete
        SettingsStore::delete($id);
        $queryResult = $db->fetchOne('SELECT id FROM ' . SettingsStore\Dao::TABLE_NAME . ' WHERE id = ?', $id);
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
        SettingsStore::set('my-id1', 'some-data-1', 'scope1', 'string');
        SettingsStore::set('my-id2', 'some-data-2', 'scope1', 'string');
        SettingsStore::set('my-id3', 'some-data-3', 'scope2', 'string');
        SettingsStore::set('my-id4', 'some-data-4', 'scope1', 'string');

        $ids = SettingsStore::getIdsByScope('scope1');
        $this->assertTrue(in_array('my-id1', $ids), 'Get settings store by scope');
        $this->assertFalse(in_array('my-id3', $ids), 'Get settings store by scope');
        $this->assertEquals(3, count($ids));

        $ids = SettingsStore::getIdsByScope('scopeX');
        $this->assertEquals(0, count($ids));
    }

    public function testNotExistingSettings()
    {
        SettingsStore::set('my-id1', true, 'scope1', 'bool');

        $setting = SettingsStore::get('my-id1');
        $this->assertTrue($setting->getData());

        SettingsStore::set('my-id1', false, 'scope1', 'bool');

        $setting = SettingsStore::get('my-id1');
        $this->assertFalse($setting->getData());

        SettingsStore::delete('my-id1');
        $setting = SettingsStore::get('my-id1');
        $this->assertNull($setting);
    }
}
