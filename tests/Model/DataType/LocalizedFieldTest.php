<?php
declare(strict_types=1);

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Tests\Model\DataType;

use Exception;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Localizedfield;
use Pimcore\SystemSettingsConfig;
use Pimcore\Tests\Support\Helper\Pimcore;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use Pimcore\Version;

class LocalizedFieldTest extends ModelTestCase
{
    protected array $originalConfig;

    protected SystemSettingsConfig $config;

    public function setUp(): void
    {
        parent::setUp();

        if (Version::getMajorVersion() >= 11) {
            $pimcoreModule = $this->getModule('\\'.Pimcore::class);
            $this->config = $pimcoreModule->grabService(SystemSettingsConfig::class);
            $this->originalConfig = $this->config->get();
        } else {
            $this->originalConfig = \Pimcore\Config::getSystemConfiguration();
        }

    }

    public function tearDown(): void
    {
        if (Version::getMajorVersion() >= 11) {
            $this->config->testSave($this->originalConfig);
        } else {
            \Pimcore\Config::setSystemConfiguration($this->originalConfig);
        }

        Localizedfield::setStrictMode((bool)Localizedfield::STRICT_DISABLED);
    }

    public function testStrictMode(): void
    {
        $object = TestHelper::createEmptyObject();

        $object->setLinput('Test');
        $this->assertEquals('Test', $object->getLinput());

        $object->setLinput('TestKo', 'ko');
        $this->assertEquals('TestKo', $object->getLinput('ko'));
    }

    public function testExceptionInStrictMode(): void
    {
        $object = TestHelper::createEmptyObject();

        Localizedfield::setStrictMode(Localizedfield::STRICT_ENABLED);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Language  not accepted in strict mode');
        $object->setLinput('Test');
    }

    public function testExceptionWithLocaleInStrictMode(): void
    {
        $object = TestHelper::createEmptyObject();

        Localizedfield::setStrictMode(Localizedfield::STRICT_ENABLED);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Language ko not accepted in strict mode');

        $object->setLinput('Test', 'ko');
    }

    public function testLocalizedFieldInsideFieldCollection(): void
    {
        $object = TestHelper::createEmptyObject();

        //save data for language "en"
        $items = new Fieldcollection();
        $item = new FieldCollection\Data\Unittestfieldcollection();
        $item->setLinput('textEN', 'en');
        $items->add($item);
        $object->setFieldcollection($items);
        $object->save();

        //Reload object from db
        $object = DataObject::getById($object->getId(), ['force' => true]);
        $loadedFieldcollectionItem = $object->getFieldcollection()->get(0);

        //save data for language "de" on same index
        $loadedFieldcollectionItem->setLinput('textDE', 'de');
        $object->save();

        //Reload object from db
        $object = DataObject::getById($object->getId(), ['force' => true]);
        $loadedItem = $object->getFieldcollection()->get(0);

        //initial value (en): index 0
        $this->assertEquals('textEN', $loadedItem->getLinput('en'), 'Existing localized value inside fieldcollection not saved or loaded properly');

        //new value (de): index 0
        $this->assertEquals('textDE', $loadedItem->getLinput('de'), 'New localized value inside fieldcollection not saved or loaded properly');
    }

    public function testLocalizedFieldFallback(): void
    {
        $configuration = $this->originalConfig;
        $configuration['general']['fallback_languages']['de'] = 'en';

        if (Version::getMajorVersion() >= 11) {
            $this->config->testSave($configuration);
        } else {
            \Pimcore\Config::setSystemConfiguration($configuration);
        }

        $object = TestHelper::createEmptyObject();

        //en values
        $object->setLinput('TestEN', 'en');
        $object->setLcheckbox(true, 'en');
        $object->setLnumber(123, 'en');

        //de values
        $object->setLinput('TestDE', 'de');
        $object->setLcheckbox(true, 'de');
        $object->setLnumber(456, 'de');

        $object->save();

        //check values stored properly
        $object = DataObject\Unittest::getById($object->getId(), ['force' => true]);
        $this->assertEquals('TestDE', $object->getLinput('de'));
        $this->assertEquals(true, $object->getLcheckbox('de'));
        $this->assertEquals(456, $object->getLnumber('de'));

        //empty de values check fallback
        $object->setLinput('', 'de');
        $object->setLcheckbox(null, 'de');
        $object->setLnumber(null, 'de');
        $object->save();

        $object = DataObject\Unittest::getById($object->getId(), ['force' => true]);

        $this->assertEquals('TestEN', $object->getLinput('de'));
        $this->assertEquals(123, $object->getLnumber('de'));
        $this->assertEquals(true, $object->getLcheckbox('de'));

        //asset listing works with fallback value
        $listing = new DataObject\Unittest\Listing();
        $listing->setLocale('de');

        $listing->setCondition("linput = 'TestEN'");
        $this->assertEquals(1, count($listing->load()), 'Expected 1 item for fallback en condition');

        $listing->setCondition("lcheckbox = '1'");
        $this->assertEquals(1, count($listing->load()), 'Expected 1 item for fallback en condition');

        $listing->setCondition("lnumber = '123'");
        $this->assertEquals(1, count($listing->load()), 'Expected 1 item for fallback en condition');

        //special case checkbox: set value to false and test fallback should not work
        $object->setLcheckbox(false, 'de');
        $object->save();

        $this->assertEquals(false, $object->getLcheckbox('de')); //should not take the fallback

        $listing = new DataObject\Unittest\Listing();
        $listing->setLocale('de');

        $listing->setCondition("lcheckbox = '1'");
        $this->assertEquals(0, count($listing->load()), 'Expected 0 item for fallback en condition as locale set to "de"');

        //special case number: set value to 0 and test fallback should not work
        $object->setLnumber(0, 'de');
        $object->save();

        $this->assertEquals(0, $object->getLnumber('de')); //should not take the fallback

        $listing = new DataObject\Unittest\Listing();
        $listing->setLocale('de');

        $listing->setCondition("lcheckbox = '123'");
        $this->assertEquals(0, count($listing->load()), 'Expected 0 item for fallback en condition as locale set to "de"');
    }
}
