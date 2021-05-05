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

namespace Pimcore\Tests\Model\DataType;

use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Localizedfield;
use Pimcore\Tests\Test\ModelTestCase;
use Pimcore\Tests\Util\TestHelper;

class LocalizedFieldTest extends ModelTestCase
{
    public function tearDown(): void
    {
        Localizedfield::setStrictMode(Localizedfield::STRICT_DISABLED);
    }

    public function testStrictMode()
    {
        $object = TestHelper::createEmptyObject();

        $object->setLinput('Test');
        $this->assertEquals('Test', $object->getLinput());

        $object->setLinput('TestKo', 'ko');
        $this->assertEquals('TestKo', $object->getLinput('ko'));
    }

    public function testExceptionInStrictMode()
    {
        $object = TestHelper::createEmptyObject();

        Localizedfield::setStrictMode(Localizedfield::STRICT_ENABLED);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Language  not accepted in strict mode');
        $object->setLinput('Test');
    }

    public function testExceptionWithLocaleInStrictMode()
    {
        $object = TestHelper::createEmptyObject();

        Localizedfield::setStrictMode(Localizedfield::STRICT_ENABLED);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Language ko not accepted in strict mode');

        $object->setLinput('Test', 'ko');
    }

    public function testLocalizedFieldInsideFieldCollection()
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
        $object = DataObject::getById($object->getId(), true);
        $loadedFieldcollectionItem = $object->getFieldcollection()->get(0);

        //save data for language "de" on same index
        $loadedFieldcollectionItem->setLinput('textDE', 'de');
        $object->save();

        //Reload object from db
        $object = DataObject::getById($object->getId(), true);
        $loadedItem = $object->getFieldcollection()->get(0);

        //initial value (en): index 0
        $this->assertEquals('textEN', $loadedItem->getLinput('en'), 'Existing localized value inside fieldcollection not saved or loaded properly');

        //new value (de): index 0
        $this->assertEquals('textDE', $loadedItem->getLinput('de'), 'New localized value inside fieldcollection not saved or loaded properly');
    }
}
