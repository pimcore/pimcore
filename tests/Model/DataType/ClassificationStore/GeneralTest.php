<?php

namespace Pimcore\Tests\Model\DataType\ClassificationStore;

use Pimcore\Cache;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Tests\Util\TestHelper;

class GeneralTest extends AbstractClassificationStoreTest
{
    public function setUp(): void
    {
        parent::setUp();

        \Pimcore::setAdminMode();
        TestHelper::cleanUp();
    }

    public function tearDown(): void
    {
        TestHelper::cleanUp();
        parent::tearDown();
    }

    public function testBasics()
    {
        // make sure that store config exists
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $this->assertNotNull($store, "Couldn't find store definition");

        // make sure that the test group exists
        $groupConfig1 = Classificationstore\GroupConfig::getByName('testgroup1');
        $this->assertNotNull($groupConfig1, "couldn't find group config");

        $keyConfigListing = new Classificationstore\KeyConfig\Listing();
        $keyConfigListing = $keyConfigListing->load();

        $this->assertEquals(6, count($keyConfigListing), 'expected 6 key configs');

        $relations = new Classificationstore\KeyGroupRelation\Listing();
        $relations->setCondition('groupId = '  . $groupConfig1->getId());
        $relations = $relations->load();

        $this->assertEquals(3, count($relations), 'expected 3 relations');

        $o = new \Pimcore\Model\DataObject\Csstore();
        $o->setParentId(1);
        $o->setKey('testobject');
        $o->setPublished(1);

        $o->save();

        Cache::disable();
        Cache::clearAll();

        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField = $o->getCsstore();
        $this->assertTrue($csField instanceof \Pimcore\Model\DataObject\Classificationstore, 'type mismatch');

        $groupConfig = Classificationstore\GroupConfig::getByName('testgroup1');
        $keyNames = ['key1', 'key2', 'key3'];

        $validLanguages = \Pimcore\Tool::getValidLanguages();
        array_push($validLanguages, 'default');

        $idx = 0;

        foreach ($validLanguages as $validLanguage) {
            foreach ($keyNames as $keyName) {
                $keyConfig = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName($keyName, $store->getId());
                $idx++;
                $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $idx, $validLanguage);
            }
        }
        $o->save();

        Cache::clearAll();

        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId());
        $csField = $o->getCsstore();

        $idx = 0;

        foreach ($validLanguages as $validLanguage) {
            foreach ($keyNames as $keyName) {
                $keyConfig = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName($keyName, $store->getId());
                $idx++;
                $value = $csField->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $validLanguage);
                $this->assertEquals($idx, $value);
            }
        }

        // now check if inheritance is correctly implemented
        $keyConfig = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName('key1', $store->getId());

        Cache::clearAll();

        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId());
        $csField = $o->getCsstore();
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), null, 'en');
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), 'defaultValue', 'default');
        $o->save();

        Cache::clearAll();

        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId());
        $csField = $o->getCsstore();
        $value = $csField->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), 'en');
        $this->assertEquals('defaultValue', $value);

        Cache::enable();
    }

    public function testClassificationStoreQuantityValue()
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $this->configureStoreWithQuantityValueField($store);

        $o = new \Pimcore\Model\DataObject\Csstore();
        $o->setParentId(1);
        $o->setKey('testobject');
        $o->setPublished(1);
        $o->save();

        Cache::disable();
        Cache::clearAll();

        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField = $o->getCsstore();
        $groupConfig = Classificationstore\GroupConfig::getByName('testgroupQvalue');
        $keyConfig = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName('qValue', $store->getId());
        $value = new \Pimcore\Model\DataObject\Data\QuantityValue(123, 1);
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $value);
        $o->save();

        Cache::clearAll();
        Cache\Runtime::clear();

        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId());
        /** @var \Pimcore\Model\DataObject\Data\QuantityValue $value1 */
        $value1 = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId());
        $this->assertEquals($value->getValue(), $value1->getValue());
        $this->assertEquals($value->getUnit(), $value1->getUnit());

        //clear value
        $value = new \Pimcore\Model\DataObject\Data\QuantityValue(null, 1);
        $o->getCsstore()->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $value);
        $o->save();

        Cache::clearAll();
        Cache\Runtime::clear();

        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId());
        /** @var \Pimcore\Model\DataObject\Data\QuantityValue $value1 */
        $value1 = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId());
        $this->assertNull($value1->getValue());

        //clear value+unit (nullify field)
        $value = new \Pimcore\Model\DataObject\Data\QuantityValue(null, null);
        $o->getCsstore()->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $value);
        $o->save();

        Cache::clearAll();
        Cache\Runtime::clear();

        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId());
        /** @var \Pimcore\Model\DataObject\Data\QuantityValue $value1 */
        $value1 = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId());
        $this->assertNull($value1);

        Cache::enable();
    }
}
