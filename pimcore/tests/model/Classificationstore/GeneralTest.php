<?php

namespace Pimcore\Tests\Model\Datatype\ClassificationStore;

use Pimcore\Cache;
use Pimcore\Model\Object\Classificationstore;
use Pimcore\Tests\Util\TestHelper;

class GeneralTest extends AbstractClassificationStoreTest
{
    /**
     * @var bool
     */
    protected $inAdminMode;

    public function setUp()
    {
        $this->inAdminMode = \Pimcore::inAdmin();

        \Pimcore::setAdminMode();
        TestHelper::cleanUp();

        parent::setUp();
    }

    public function tearDown()
    {
        if ($this->inAdminMode) {
            \Pimcore::setAdminMode();
        } else {
            \Pimcore::unsetAdminMode();
        }

        parent::tearDown();
    }

    public function testBasics()
    {
        // make sure that store config exists
        $store = Classificationstore\StoreConfig::getByName("teststore");
        $this->assertNotNull($store, "Couldn't find store definition");

        // make sure that the test group exists
        $groupConfig1 = Classificationstore\GroupConfig::getByName("testgroup1");
        $this->assertNotNull($groupConfig1, "couldn't find group config");

        $keyConfigListing = new Classificationstore\KeyConfig\Listing();
        $keyConfigListing = $keyConfigListing->load();

        $this->assertEquals(6, count($keyConfigListing), "expected 6 key configs");

        $relations = new Classificationstore\KeyGroupRelation\Listing();
        $relations->setCondition("groupId = "  . $groupConfig1->getId());
        $relations = $relations->load();

        $this->assertEquals(3, count($relations), "expected 3 relations");

        $o = new \Pimcore\Model\Object\Csstoreclass();
        $o->setParentId(1);
        $o->setKey("testobject");
        $o->setPublished(1);
        
        $o->save();

        Cache::disable();
        Cache::clearAll();

        /** @var  $csField \Pimcore\Model\Object\Classificationstore */
        $csField = $o->getCsstore();
        $this->assertTrue($csField instanceof \Pimcore\Model\Object\Classificationstore, "type mismatch");

        $groupConfig = Classificationstore\GroupConfig::getByName("testgroup1");
        $keyNames = ["key1", "key2", "key3"];

        $validLanguages = \Pimcore\Tool::getValidLanguages();
        array_push($validLanguages, "default");

        $idx = 0;

        foreach ($validLanguages as $validLanguage) {
            foreach ($keyNames as $keyName) {
                $keyConfig = \Pimcore\Model\Object\Classificationstore\KeyConfig::getByName($keyName, $store->getId());
                $idx++;
                $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $idx, $validLanguage);
            }
        }
        $o->save();

        Cache::clearAll();

        $o = \Pimcore\Model\Object\Csstoreclass::getById($o->getId());
        $csField = $o->getCsstore();

        $idx = 0;

        foreach ($validLanguages as $validLanguage) {
            foreach ($keyNames as $keyName) {
                $keyConfig = \Pimcore\Model\Object\Classificationstore\KeyConfig::getByName($keyName, $store->getId());
                $idx++;
                $value =  $csField->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $validLanguage);
                $this->assertEquals($idx, $value);
            }
        }

        // now check if inheritance is correctly implented
        $keyConfig = \Pimcore\Model\Object\Classificationstore\KeyConfig::getByName("key1", $store->getId());

        Cache::clearAll();

        $o = \Pimcore\Model\Object\Csstoreclass::getById($o->getId());
        $csField = $o->getCsstore();
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), null, "en");
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), "defaultValue", "default");
        $o->save();

        Cache::clearAll();

        $o = \Pimcore\Model\Object\Csstoreclass::getById($o->getId());
        $csField = $o->getCsstore();
        $value =  $csField->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), "en");
        $this->assertEquals("defaultValue", $value);

        Cache::enable();
    }
}
