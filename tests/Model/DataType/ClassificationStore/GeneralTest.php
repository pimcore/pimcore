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

use Carbon\Carbon;
use Exception;
use Pimcore;
use Pimcore\Cache;
use Pimcore\Db;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\DataObject\ClassDefinition\Data\Image as ImageFieldDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Data\EncryptedField;
use Pimcore\Model\DataObject\Data\InputQuantityValue;
use Pimcore\Model\DataObject\Data\QuantityValue;
use Pimcore\Model\DataObject\Data\RgbaColor;
use Pimcore\Model\DataObject\QuantityValue\Unit;
use Pimcore\Model\User;
use Pimcore\Tests\Support\Util\TestHelper;

class GeneralTest extends AbstractClassificationStoreTest
{
    private const FIELDNAME = 'csstore';

    private const DEFAULT_LANGUAGE = 'default';

    public function setUp(): void
    {
        parent::setUp();

        Pimcore::setAdminMode();
        TestHelper::cleanUp();
    }

    public function tearDown(): void
    {
        TestHelper::cleanUp();
        parent::tearDown();
    }

    public function testBasics(): void
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
        $o->setPublished(true);

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

    public function testBooleanSelect(): void
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $o = $this->createCsObject();
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField = $o->getCsstore();

        $groupConfig = Classificationstore\GroupConfig::getByName('testgroup1');
        $keyConfig = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName('booleanSelect', $store->getId());

        $originalValue = true;
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $originalValue);
        $o->save();
        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId(), ['force' => true]);

        $newValue = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId());
        $this->assertEquals($originalValue, $newValue);
    }

    /**
     *
     * @throws Exception
     */
    protected function createCsObject(): \Pimcore\Model\DataObject\Csstore
    {
        $o = new \Pimcore\Model\DataObject\Csstore();
        $o->setParentId(1);
        $o->setKey('testobject');
        $o->setPublished(true);
        $o->save();
        Cache::clearAll();

        return $o;
    }

    public function testCheckbox(): void
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $o = $this->createCsObject();
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField = $o->getCsstore();

        $groupConfig = Classificationstore\GroupConfig::getByName('testgroup1');
        $keyConfig = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName('checkbox', $store->getId());

        $originalValue = true;
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $originalValue);
        $o->save();
        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId(), ['force' => true]);

        $newValue = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId());
        $this->assertEquals($originalValue, $newValue);
    }

    public function testClassificationStoreInput(): void
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $o = $this->createCsObject();
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField = $o->getCsstore();

        $groupConfig = Classificationstore\GroupConfig::getByName('testgroup1');
        $keyConfig = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName('input', $store->getId());

        $originalValue = '123';
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $originalValue);
        $o->save();
        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId(), ['force' => true]);

        $newValue = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId());
        $this->assertEquals($originalValue, $newValue);
    }

    public function testClassificationStoreQuantityValue(): void
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $this->configureStoreWithQuantityValueField($store);

        $o = $this->createCsObject();

        Cache::disable();

        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField = $o->getCsstore();
        $groupConfig = Classificationstore\GroupConfig::getByName('testgroupQvalue');
        $keyConfig = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName('qValue', $store->getId());
        $value = new \Pimcore\Model\DataObject\Data\QuantityValue(123, '1');
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $value);
        $o->save();

        Cache::clearAll();
        Cache\RuntimeCache::clear();

        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId());
        /** @var \Pimcore\Model\DataObject\Data\QuantityValue $value1 */
        $value1 = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId());
        $this->assertEquals($value->getValue(), $value1->getValue());
        $this->assertEquals($value->getUnit(), $value1->getUnit());

        //clear value
        $value = new \Pimcore\Model\DataObject\Data\QuantityValue(null, '1');
        $o->getCsstore()->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $value);
        $o->save();

        Cache::clearAll();
        Cache\RuntimeCache::clear();

        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId());
        /** @var \Pimcore\Model\DataObject\Data\QuantityValue $value1 */
        $value1 = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId());
        $this->assertNull($value1->getValue());

        //clear value+unit (nullify field)
        $value = new \Pimcore\Model\DataObject\Data\QuantityValue(null, null);
        $o->getCsstore()->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $value);
        $o->save();

        Cache::clearAll();
        Cache\RuntimeCache::clear();

        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId());
        /** @var \Pimcore\Model\DataObject\Data\QuantityValue $value1 */
        $value1 = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId());
        $this->assertNull($value1);

        Cache::enable();
    }

    public function testCountry(): void
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $o = $this->createCsObject();
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField = $o->getCsstore();

        $groupConfig = Classificationstore\GroupConfig::getByName('testgroup1');
        $keyConfig = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName('country', $store->getId());

        $originalValue = 'AT';
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $originalValue);
        $o->save();
        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId(), ['force' => true]);

        $newValue = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId());
        $this->assertEquals($originalValue, $newValue);
    }

    public function testCountrymultiselect(): void
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $o = $this->createCsObject();
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField = $o->getCsstore();

        $groupConfig = Classificationstore\GroupConfig::getByName('testgroup1');
        $keyConfig = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName('countrymultiselect', $store->getId());

        $originalValue = ['AT', 'DE'];
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $originalValue);
        $o->save();
        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId(), ['force' => true]);

        $newValue = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId());
        $this->assertEquals($originalValue, $newValue);
    }

    public function testDate(): void
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $o = $this->createCsObject();
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField = $o->getCsstore();

        $groupConfig = Classificationstore\GroupConfig::getByName('testgroup1');
        $keyConfig = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName('date', $store->getId());

        $originalValue = new Carbon();
        $originalValue->setTimestamp(time());
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $originalValue);
        $o->save();
        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId(), ['force' => true]);

        $newValue = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId());
        $this->assertEquals($originalValue, $newValue);
    }

    public function testDatetime(): void
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $o = $this->createCsObject();
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField = $o->getCsstore();

        $groupConfig = Classificationstore\GroupConfig::getByName('testgroup1');
        $keyConfig = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName('datetime', $store->getId());

        $originalValue = new Carbon();
        $originalValue->setTimestamp(time());
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $originalValue);
        $o->save();
        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId(), ['force' => true]);

        $newValue = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId());
        $this->assertEquals($originalValue, $newValue);
    }

    public function testEncryptedField(): void
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $o = $this->createCsObject();
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField = $o->getCsstore();

        $groupConfig = Classificationstore\GroupConfig::getByName('testgroup1');
        $keyConfig = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName('encryptedField', $store->getId());

        $delegate = new Input();
        $originalValue = new EncryptedField($delegate, 'abc');
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $originalValue);
        $o->save();
        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId(), ['force' => true]);

        $newValue = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId());
        $this->assertEquals($originalValue, $newValue);
    }

    /**
     * An image delegate stores the asset id in the value column on its own and leaves the second
     * column empty, so there is nothing to encrypt there.
     */
    public function testEncryptedImage(): void
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $o = $this->createCsObject();
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField = $o->getCsstore();

        $groupConfig = Classificationstore\GroupConfig::getByName('testgroup1');
        $keyConfig = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName('encryptedImage', $store->getId());

        $originalValue = TestHelper::createImageAsset();
        $csField->setLocalizedKeyValue(
            $groupConfig->getId(),
            $keyConfig->getId(),
            new EncryptedField(new ImageFieldDefinition(), $originalValue)
        );
        $o->save();
        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId(), ['force' => true]);

        $newValue = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId());
        $this->assertInstanceOf(EncryptedField::class, $newValue);
        $this->assertInstanceOf(Image::class, $newValue->getPlain());
        $this->assertSame($originalValue->getId(), $newValue->getPlain()->getId());

        if ($this->storesInEavColumns()) {
            $rows = $this->grabImageRows($o, $groupConfig->getId(), $keyConfig->getId());
            $this->assertCount(1, $rows);
            $this->assertNull($rows[0]['value2'], 'the second column stays empty instead of holding an encrypted null');
            $this->assertNotSame((string) $originalValue->getId(), $rows[0]['value'], 'the asset id is stored encrypted');
        }
    }

    public function testImage(): void
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $o = $this->createCsObject();
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField = $o->getCsstore();

        $groupConfig = Classificationstore\GroupConfig::getByName('testgroup1');
        $keyConfig = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName('image', $store->getId());

        $languages = [self::DEFAULT_LANGUAGE, 'en'];
        $originalValue = TestHelper::createImageAsset();

        foreach ($languages as $language) {
            $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $originalValue, $language);
        }
        $o->save();
        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId(), ['force' => true]);

        foreach ($languages as $language) {
            $newValue = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $language);
            $this->assertInstanceOf(Image::class, $newValue);
            $this->assertEquals($originalValue->getId(), $newValue->getId());
        }

        if ($this->storesInEavColumns()) {
            $this->assertSame(
                array_fill(0, count($languages), ['value' => (string) $originalValue->getId(), 'value2' => null]),
                $this->grabImageRows($o, $groupConfig->getId(), $keyConfig->getId()),
                'the asset id is stored on its own, in the value column'
            );
        }

        foreach ($languages as $language) {
            $o->getCsstore()->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), null, $language);
        }
        $o->save();

        if ($this->storesInEavColumns()) {
            $this->assertSame([], $this->grabImageRows($o, $groupConfig->getId(), $keyConfig->getId()));
        }
    }

    /**
     * A stored value that does not resolve to an image asset - the empty string, a string that is
     * no id at all (what a save before the marshaller existed left behind), a string that only
     * starts with the id of an existing image, the id of a deleted asset and the id of an asset
     * that is not an image - reads back as null.
     */
    public function testImageWithAStoredValueThatDoesNotResolve(): void
    {
        if (!$this->storesInEavColumns()) {
            // The case is written by putting raw text into value/value2. An engine that keeps its
            // values in a document has no such columns, so there is nothing to corrupt and nothing
            // this test could assert.
            $this->markTestSkipped('the unresolvable stored value is written into the EAV columns');
        }

        $store = Classificationstore\StoreConfig::getByName('teststore');
        $o = $this->createCsObject();
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField = $o->getCsstore();

        $groupConfig = Classificationstore\GroupConfig::getByName('testgroup1');
        $keyConfig = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName('image', $store->getId());

        $existingImage = TestHelper::createImageAsset();
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $existingImage);
        $o->save();

        $deletedAsset = TestHelper::createImageAsset();
        $deletedAssetId = $deletedAsset->getId();
        $deletedAsset->delete();

        $notAnImage = TestHelper::createDocumentAsset();
        $storedValues = [
            '',
            'not-an-id',
            $existingImage->getId() . 'junk',
            $existingImage->getId() . '.7',
            (string) $deletedAssetId,
            (string) $notAnImage->getId(),
        ];

        foreach ($storedValues as $storedValue) {
            Db::get()->update(
                $this->getDataTableName($o),
                ['value' => $storedValue],
                [
                    'id' => $o->getId(),
                    'fieldname' => self::FIELDNAME,
                    'groupId' => $groupConfig->getId(),
                    'keyId' => $keyConfig->getId(),
                    'language' => self::DEFAULT_LANGUAGE,
                ]
            );

            $reloaded = \Pimcore\Model\DataObject\Csstore::getById($o->getId(), ['force' => true]);

            $this->assertNull(
                $reloaded->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId()),
                'a stored value of "' . $storedValue . '" must read back as null'
            );
        }
    }

    /**
     * The value columns of the stored rows of a key, ordered by language.
     *
     * @return array<array<string, ?string>>
     */
    /**
     * Whether the engine under test keeps values in the EAV value/value2 columns.
     *
     * The image assertions come in two halves: the round trip, which every engine must satisfy,
     * and the storage layout, which only describes the EAV tables. DocumentGeneralTest inherits
     * both, and the second half asserted against columns a document store does not have.
     */
    private function storesInEavColumns(): bool
    {
        return $this->getStoreEngine() === null;
    }

    private function grabImageRows(\Pimcore\Model\DataObject\Csstore $o, int $groupId, int $keyId): array
    {
        return Db::get()->fetchAllAssociative(
            'SELECT `value`, `value2` FROM `' . $this->getDataTableName($o) . '` WHERE `id` = ? AND `fieldname` = ?'
            . ' AND `groupId` = ? AND `keyId` = ? ORDER BY `language`',
            [$o->getId(), self::FIELDNAME, $groupId, $keyId]
        );
    }

    private function getDataTableName(\Pimcore\Model\DataObject\Csstore $o): string
    {
        return 'object_classificationstore_data_' . $o->getClass()->getId();
    }

    public function testInput(): void
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $o = $this->createCsObject();
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField = $o->getCsstore();

        $groupConfig = Classificationstore\GroupConfig::getByName('testgroup1');
        $keyConfig = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName('input', $store->getId());

        $originalValue = 'abc';
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $originalValue);
        $o->save();
        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId(), ['force' => true]);

        $newValue = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId());
        $this->assertEquals($originalValue, $newValue);
    }

    public function testInputQuantityValue(): void
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
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $originalValue);
        $o->save();
        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId(), ['force' => true]);

        $newValue = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId());
        $this->assertEquals($originalValue, $newValue);
    }

    public function testLanguage(): void
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $o = $this->createCsObject();
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField = $o->getCsstore();

        $groupConfig = Classificationstore\GroupConfig::getByName('testgroup1');
        $keyConfig = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName('language', $store->getId());

        $originalValue = 'fr';
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $originalValue);
        $o->save();
        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId(), ['force' => true]);

        $newValue = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId());
        $this->assertEquals($originalValue, $newValue);
    }

    public function testLanguagemultiselect(): void
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $o = $this->createCsObject();
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField = $o->getCsstore();

        $groupConfig = Classificationstore\GroupConfig::getByName('testgroup1');
        $keyConfig = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName('languagemultiselect', $store->getId());

        $originalValue = ['AT', 'DE'];
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $originalValue);
        $o->save();
        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId(), ['force' => true]);

        $newValue = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId());
        $this->assertEquals($originalValue, $newValue);
    }

    public function testMultiselect(): void
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $o = $this->createCsObject();
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField = $o->getCsstore();

        $groupConfig = Classificationstore\GroupConfig::getByName('testgroup1');
        $keyConfig = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName('multiselect', $store->getId());

        $originalValue = ['A', 'D'];
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $originalValue);
        $o->save();
        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId(), ['force' => true]);

        $newValue = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId());
        $this->assertEquals($originalValue, $newValue);
    }

    public function testNumeric(): void
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $o = $this->createCsObject();
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField = $o->getCsstore();

        $groupConfig = Classificationstore\GroupConfig::getByName('testgroup1');
        $keyConfig = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName('numeric', $store->getId());

        $originalValue = 12.57;
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $originalValue);
        $o->save();
        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId(), ['force' => true]);

        $newValue = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId());
        $this->assertEquals($originalValue, $newValue);
    }

    public function testQuantityValue(): void
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
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $originalValue);
        $o->save();
        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId(), ['force' => true]);

        $newValue = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId());
        $this->assertEquals($originalValue, $newValue);
    }

    public function testRgbaColor(): void
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $o = $this->createCsObject();
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField = $o->getCsstore();

        $groupConfig = Classificationstore\GroupConfig::getByName('testgroup1');
        $keyConfig = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName('rgbaColor', $store->getId());

        $originalValue = new RgbaColor(1, 2, 3, 4);
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $originalValue);
        $o->save();
        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId(), ['force' => true]);

        $newValue = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId());
        $this->assertEquals($originalValue, $newValue);
    }

    public function testSelect(): void
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $o = $this->createCsObject();
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField = $o->getCsstore();

        $groupConfig = Classificationstore\GroupConfig::getByName('testgroup1');
        $keyConfig = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName('select', $store->getId());

        $originalValue = 'B';
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $originalValue);
        $o->save();
        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId(), ['force' => true]);

        $newValue = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId());
        $this->assertEquals($originalValue, $newValue);
    }

    public function testSlider(): void
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $o = $this->createCsObject();
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField = $o->getCsstore();

        $groupConfig = Classificationstore\GroupConfig::getByName('testgroup1');
        $keyConfig = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName('slider', $store->getId());

        $originalValue = 47;
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $originalValue);
        $o->save();
        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId(), ['force' => true]);

        $newValue = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId());
        $this->assertEquals($originalValue, $newValue);
    }

    public function testTable(): void
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $o = $this->createCsObject();
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField = $o->getCsstore();

        $groupConfig = Classificationstore\GroupConfig::getByName('testgroup1');
        $keyConfig = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName('table', $store->getId());

        $originalValue = [['A', 'B'], ['C', 'D']];
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $originalValue);
        $o->save();
        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId(), ['force' => true]);

        $newValue = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId());
        $this->assertEquals($originalValue, $newValue);
    }

    public function testTextarea(): void
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $o = $this->createCsObject();
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField = $o->getCsstore();

        $groupConfig = Classificationstore\GroupConfig::getByName('testgroup1');
        $keyConfig = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName('textarea', $store->getId());

        $originalValue = "line1\nline2";
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $originalValue);
        $o->save();
        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId(), ['force' => true]);

        $newValue = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId());
        $this->assertEquals($originalValue, $newValue);
    }

    public function testTime(): void
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $o = $this->createCsObject();
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField = $o->getCsstore();

        $groupConfig = Classificationstore\GroupConfig::getByName('testgroup1');
        $keyConfig = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName('time', $store->getId());

        $originalValue = '12:30';
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $originalValue);
        $o->save();
        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId(), ['force' => true]);

        $newValue = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId());
        $this->assertEquals($originalValue, $newValue);
    }

    public function testUser(): void
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
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $originalValue);
        $o->save();
        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId(), ['force' => true]);

        $newValue = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId());
        $this->assertEquals($originalValue, $newValue);
    }

    public function testWysiwyg(): void
    {
        $store = Classificationstore\StoreConfig::getByName('teststore');
        $o = $this->createCsObject();
        /** @var \Pimcore\Model\DataObject\Classificationstore $csField */
        $csField = $o->getCsstore();

        $groupConfig = Classificationstore\GroupConfig::getByName('testgroup1');
        $keyConfig = \Pimcore\Model\DataObject\Classificationstore\KeyConfig::getByName('wysiwyg', $store->getId());

        $originalValue = 'line1<br />line2';
        $csField->setLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId(), $originalValue);
        $o->save();
        $o = \Pimcore\Model\DataObject\Csstore::getById($o->getId(), ['force' => true]);

        $newValue = $o->getCsstore()->getLocalizedKeyValue($groupConfig->getId(), $keyConfig->getId());
        $this->assertEquals($originalValue, $newValue);
    }
}
