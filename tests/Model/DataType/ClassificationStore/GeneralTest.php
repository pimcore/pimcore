<?php

namespace Pimcore\Tests\Model\DataType\ClassificationStore;

use Carbon\Carbon;
use Pimcore\Cache;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Data\EncryptedField;
use Pimcore\Model\DataObject\Data\InputQuantityValue;
use Pimcore\Model\DataObject\Data\QuantityValue;
use Pimcore\Model\DataObject\Data\RgbaColor;
use Pimcore\Model\DataObject\QuantityValue\Unit;
use Pimcore\Model\User;
use Pimcore\Tests\Util\TestHelper;

class GeneralTest extends AbstractClassificationStoreTest
{
    public function setUp()
    {
        parent::setUp();

        \Pimcore::setAdminMode();
        TestHelper::cleanUp();
    }

    public function tearDown()
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

        $expectedCount = self::$configCount;
        $this->assertEquals($expectedCount, count($keyConfigListing), 'expected ' . $expectedCount . ' key configs');

        $relations = new Classificationstore\KeyGroupRelation\Listing();
        $relations->setCondition('groupId = ' . $groupConfig1->getId());
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
        $keyNames = ['input', 'select'];

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
        $keyConfig = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName('input', $store->getId());

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

    public function testBooleanSelect()
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $o = $this->createCsObject();
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField = $o->getCsstore();

        $groupConfig = Classificationstore\GroupConfig::getByName('testgroup1');
        $keyConfig = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName('booleanSelect', $store->getId());

        $originalValue = true;
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $originalValue);
        $o->save();
        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId(), true);

        $newValue = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId());
        $this->assertEquals($originalValue, $newValue);
    }

    /**
     * @return \Pimcore\Model\DataObject\Csstore
     *
     * @throws \Exception
     */
    protected function createCsObject()
    {
        $o = new \Pimcore\Model\DataObject\Csstore();
        $o->setParentId(1);
        $o->setKey('testobject');
        $o->setPublished(1);
        $o->save();
        Cache::clearAll();

        return $o;
    }

    public function testCheckbox()
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $o = $this->createCsObject();
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField = $o->getCsstore();

        $groupConfig = Classificationstore\GroupConfig::getByName('testgroup1');
        $keyConfig = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName('checkbox', $store->getId());

        $originalValue = true;
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $originalValue);
        $o->save();
        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId(), true);

        $newValue = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId());
        $this->assertEquals($originalValue, $newValue);
    }

    public function testClassificationStoreInput()
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $o = $this->createCsObject();
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField = $o->getCsstore();

        $groupConfig = Classificationstore\GroupConfig::getByName('testgroup1');
        $keyConfig = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName('input', $store->getId());

        $originalValue = '123';
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $originalValue);
        $o->save();
        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId(), true);

        $newValue = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId());
        $this->assertEquals($originalValue, $newValue);
    }

    public function testClassificationStoreQuantityValue()
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $this->configureStoreWithQuantityValueField($store);

        $o = $this->createCsObject();

        Cache::disable();

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

    public function testCountry()
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $o = $this->createCsObject();
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField = $o->getCsstore();

        $groupConfig = Classificationstore\GroupConfig::getByName('testgroup1');
        $keyConfig = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName('country', $store->getId());

        $originalValue = 'AT';
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $originalValue);
        $o->save();
        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId(), true);

        $newValue = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId());
        $this->assertEquals($originalValue, $newValue);
    }

    public function testCountrymultiselect()
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $o = $this->createCsObject();
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField = $o->getCsstore();

        $groupConfig = Classificationstore\GroupConfig::getByName('testgroup1');
        $keyConfig = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName('countrymultiselect', $store->getId());

        $originalValue = ['AT', 'DE'];
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $originalValue);
        $o->save();
        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId(), true);

        $newValue = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId());
        $this->assertEquals($originalValue, $newValue);
    }

    public function testDate()
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $o = $this->createCsObject();
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField = $o->getCsstore();

        $groupConfig = Classificationstore\GroupConfig::getByName('testgroup1');
        $keyConfig = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName('date', $store->getId());

        $originalValue = new Carbon();
        $originalValue->setTimestamp(time());
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $originalValue);
        $o->save();
        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId(), true);

        $newValue = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId());
        $this->assertEquals($originalValue, $newValue);
    }

    public function testDatetime()
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $o = $this->createCsObject();
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField = $o->getCsstore();

        $groupConfig = Classificationstore\GroupConfig::getByName('testgroup1');
        $keyConfig = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName('datetime', $store->getId());

        $originalValue = new Carbon();
        $originalValue->setTimestamp(time());
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $originalValue);
        $o->save();
        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId(), true);

        $newValue = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId());
        $this->assertEquals($originalValue, $newValue);
    }

    public function testEncryptedField()
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $o = $this->createCsObject();
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField = $o->getCsstore();

        $groupConfig = Classificationstore\GroupConfig::getByName('testgroup1');
        $keyConfig = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName('encryptedField', $store->getId());

        $delegate = new Input();
        $originalValue = new EncryptedField($delegate, 'abc');
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $originalValue);
        $o->save();
        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId(), true);

        $newValue = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId());
        $this->assertEquals($originalValue, $newValue);
    }

    public function testInput()
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $o = $this->createCsObject();
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField = $o->getCsstore();

        $groupConfig = Classificationstore\GroupConfig::getByName('testgroup1');
        $keyConfig = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName('input', $store->getId());

        $originalValue = 'abc';
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $originalValue);
        $o->save();
        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId(), true);

        $newValue = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId());
        $this->assertEquals($originalValue, $newValue);
    }

    public function testInputQuantityValue()
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $o = $this->createCsObject();
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField = $o->getCsstore();

        $groupConfig = Classificationstore\GroupConfig::getByName('testgroup1');
        $keyConfig = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName('inputQuantityValue', $store->getId());

        $unit = Unit::getByAbbreviation('mm');
        if (!$unit) {
            $unit = new Unit();
            $unit->setAbbreviation('mm');
            $unit->save();
        }

        $originalValue = new InputQuantityValue('abc', $unit->getId());
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $originalValue);
        $o->save();
        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId(), true);

        $newValue = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId());
        $this->assertEquals($originalValue, $newValue);
    }

    public function testLanguage()
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $o = $this->createCsObject();
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField = $o->getCsstore();

        $groupConfig = Classificationstore\GroupConfig::getByName('testgroup1');
        $keyConfig = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName('language', $store->getId());

        $originalValue = 'fr';
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $originalValue);
        $o->save();
        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId(), true);

        $newValue = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId());
        $this->assertEquals($originalValue, $newValue);
    }

    public function testLanguagemultiselect()
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $o = $this->createCsObject();
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField = $o->getCsstore();

        $groupConfig = Classificationstore\GroupConfig::getByName('testgroup1');
        $keyConfig = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName('languagemultiselect', $store->getId());

        $originalValue = ['AT', 'DE'];
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $originalValue);
        $o->save();
        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId(), true);

        $newValue = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId());
        $this->assertEquals($originalValue, $newValue);
    }

    public function testMultiselect()
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $o = $this->createCsObject();
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField = $o->getCsstore();

        $groupConfig = Classificationstore\GroupConfig::getByName('testgroup1');
        $keyConfig = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName('multiselect', $store->getId());

        $originalValue = ['A', 'D'];
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $originalValue);
        $o->save();
        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId(), true);

        $newValue = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId());
        $this->assertEquals($originalValue, $newValue);
    }

    public function testNumeric()
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $o = $this->createCsObject();
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField = $o->getCsstore();

        $groupConfig = Classificationstore\GroupConfig::getByName('testgroup1');
        $keyConfig = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName('numeric', $store->getId());

        $originalValue = 12.57;
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $originalValue);
        $o->save();
        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId(), true);

        $newValue = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId());
        $this->assertEquals($originalValue, $newValue);
    }

    public function testQuantityValue()
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $o = $this->createCsObject();
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField = $o->getCsstore();

        $groupConfig = Classificationstore\GroupConfig::getByName('testgroup1');
        $keyConfig = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName('quantityValue', $store->getId());

        $unit = Unit::getByAbbreviation('mm');
        if (!$unit) {
            $unit = new Unit();
            $unit->setAbbreviation('mm');
            $unit->save();
        }

        $originalValue = new QuantityValue(123, $unit->getId());
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $originalValue);
        $o->save();
        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId(), true);

        $newValue = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId());
        $this->assertEquals($originalValue, $newValue);
    }

    public function testRgbaColor()
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $o = $this->createCsObject();
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField = $o->getCsstore();

        $groupConfig = Classificationstore\GroupConfig::getByName('testgroup1');
        $keyConfig = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName('rgbaColor', $store->getId());

        $originalValue = new RgbaColor(1, 2, 3, 4);
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $originalValue);
        $o->save();
        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId(), true);

        $newValue = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId());
        $this->assertEquals($originalValue, $newValue);
    }

    public function testSelect()
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $o = $this->createCsObject();
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField = $o->getCsstore();

        $groupConfig = Classificationstore\GroupConfig::getByName('testgroup1');
        $keyConfig = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName('select', $store->getId());

        $originalValue = 'B';
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $originalValue);
        $o->save();
        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId(), true);

        $newValue = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId());
        $this->assertEquals($originalValue, $newValue);
    }

    public function testSlider()
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $o = $this->createCsObject();
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField = $o->getCsstore();

        $groupConfig = Classificationstore\GroupConfig::getByName('testgroup1');
        $keyConfig = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName('slider', $store->getId());

        $originalValue = 47;
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $originalValue);
        $o->save();
        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId(), true);

        $newValue = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId());
        $this->assertEquals($originalValue, $newValue);
    }

    public function testTable()
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $o = $this->createCsObject();
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField = $o->getCsstore();

        $groupConfig = Classificationstore\GroupConfig::getByName('testgroup1');
        $keyConfig = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName('table', $store->getId());

        $originalValue = [['A', 'B'], ['C', 'D']];
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $originalValue);
        $o->save();
        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId(), true);

        $newValue = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId());
        $this->assertEquals($originalValue, $newValue);
    }

    public function testTextarea()
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $o = $this->createCsObject();
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField = $o->getCsstore();

        $groupConfig = Classificationstore\GroupConfig::getByName('testgroup1');
        $keyConfig = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName('textarea', $store->getId());

        $originalValue = "line1\nline2";
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $originalValue);
        $o->save();
        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId(), true);

        $newValue = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId());
        $this->assertEquals($originalValue, $newValue);
    }

    public function testTime()
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $o = $this->createCsObject();
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField = $o->getCsstore();

        $groupConfig = Classificationstore\GroupConfig::getByName('testgroup1');
        $keyConfig = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName('time', $store->getId());

        $originalValue = '12:30';
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $originalValue);
        $o->save();
        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId(), true);

        $newValue = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId());
        $this->assertEquals($originalValue, $newValue);
    }

    public function testUser()
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $o = $this->createCsObject();
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField = $o->getCsstore();

        $groupConfig = Classificationstore\GroupConfig::getByName('testgroup1');
        $keyConfig = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName('user', $store->getId());

        $userListing = new User\Listing();
        $userListing->setLimit(1);
        $userListing = $userListing->load();
        if (!$userListing) {
            $user = new User();
            $user->setName('testuser');
            $user->save();
        } else {
            $user = $userListing[0];
        }
        $originalValue = $user->getId();
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $originalValue);
        $o->save();
        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId(), true);

        $newValue = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId());
        $this->assertEquals($originalValue, $newValue);
    }

    public function testWysiwyg()
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $o = $this->createCsObject();
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField = $o->getCsstore();

        $groupConfig = Classificationstore\GroupConfig::getByName('testgroup1');
        $keyConfig = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName('wysiwyg', $store->getId());

        $originalValue = 'line1<br>line2';
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $originalValue);
        $o->save();
        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId(), true);

        $newValue = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId());
        $this->assertEquals($originalValue, $newValue);
    }
}
