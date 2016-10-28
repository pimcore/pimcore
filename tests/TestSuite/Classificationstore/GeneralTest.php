<?php
/**
 * Created by IntelliJ IDEA.
 * User: josef.aichhorn@elements.at
 * Date: 11.11.2013
 */


class TestSuite_Classificationstore_GeneralTest extends Test_Base
{
    public function setUp()
    {
        $this->inAdminMode = Pimcore::inAdmin();
        Pimcore::setAdminMode();
        Test_Tool::cleanUp();
        parent::setUp();
    }

    public function tearDown()
    {
        if ($this->inAdminMode) {
            Pimcore::setAdminMode();
        } else {
            Pimcore::unsetAdminMode();
        }
    }

    public function testBasics()
    {
        $this->printTestName();

        // make sure that store config exists
        $store = \Pimcore\Model\Object\Classificationstore\StoreConfig::getByName("teststore");
        $this->assertNotNull($store, "couldn't find store definition");

        // make sure that the test group exists
        $groupConfig1 = \Pimcore\Model\Object\Classificationstore\GroupConfig::getByName("testgroup1");
        $this->assertNotNull($groupConfig1, "couldn't find group config");


        $keyConfigListing = new \Pimcore\Model\Object\Classificationstore\KeyConfig\Listing();
        $keyConfigListing = $keyConfigListing->load();
        $this->assertEquals(6, count($keyConfigListing), "expected 6 key configs");


        $relations = new Pimcore\Model\Object\Classificationstore\KeyGroupRelation\Listing();
        $relations->setCondition("groupId = "  . $groupConfig1->getId());
        $relations = $relations->load();
        $this->assertEquals(3, count($relations), "expected 3 relations");

        $o = new \Pimcore\Model\Object\Csstoreclass();
        $o->setParentId(1);
        $o->setKey("testobject");
        $o->setPublished(1);
        
        $o->save();

        \Pimcore\Cache::disable();

        \Pimcore\Cache::clearAll();

        /** @var  $csField \Pimcore\Model\Object\Classificationstore */
        $csField = $o->getCsstore();
        $this->assertTrue($csField instanceof \Pimcore\Model\Object\Classificationstore, "type mismatch");

        $groupConfig = \Pimcore\Model\Object\Classificationstore\GroupConfig::getByName("testgroup1");
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

        \Pimcore\Cache::clearAll();
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
        \Pimcore\Cache::clearAll();
        $o = \Pimcore\Model\Object\Csstoreclass::getById($o->getId());
        $csField = $o->getCsstore();
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), null, "en");
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), "defaultValue", "default");
        $o->save();

        \Pimcore\Cache::clearAll();
        $o = \Pimcore\Model\Object\Csstoreclass::getById($o->getId());
        $csField = $o->getCsstore();
        $value =  $csField->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), "en");
        $this->assertEquals("defaultValue", $value);




        \Pimcore\Cache::enable();
    }
}
