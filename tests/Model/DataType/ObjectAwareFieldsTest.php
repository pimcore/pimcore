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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Tests\Model\DataType;

use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Objectbrick\Data\LazyLoadingLocalizedTest;
use Pimcore\Model\DataObject\Unittest;
use Pimcore\Tests\Model\LazyLoading\AbstractLazyLoadingTest;
use Pimcore\Tests\Support\Util\TestHelper;

class ObjectAwareFieldsTest extends AbstractLazyLoadingTest
{
    private function reloadObject(int $id): array
    {
        //reload object from database
        $databaseObject = AbstractObject::getById($id, ['force' => true]);

        //load latest version of object
        $latestVersion = $databaseObject->getLatestVersion();
        $this->assertNotNull($latestVersion, 'Latest version could not be loaded.');

        $latestObjectVersion = $latestVersion->loadData();
        $this->assertNotNull($latestObjectVersion, 'Object from latest version could not be loaded.');

        $this->assertNotEquals($databaseObject->getInput(), $latestObjectVersion->getInput(), 'Object versions are not different');

        return [$databaseObject, $latestObjectVersion];
    }

    public function testLocalizedField(): void
    {
        /**
         * @var Unittest $object
         */
        $object = TestHelper::createEmptyObject();

        $object->setInput('1');
        $object->setLinput('some localized input');
        $object->save();

        //create new unpublished version of object
        $object = Concrete::getById($object->getId(), ['force' => true]);
        $object->setInput($object->getInput() + 1);
        $object->saveVersion();

        [$databaseObject, $latestObjectVersion] = $this->reloadObject($object->getId());

        $this->assertEquals($latestObjectVersion->getLocalizedfields()->getObject()->getInput(), $latestObjectVersion->getInput(), 'Object reference in localized field is not right.');
    }

    public function testLocalizedFieldInFieldCollection(): void
    {
        /**
         * @var Unittest $object
         */
        $object = TestHelper::createEmptyObject();

        $object->setInput('1');

        $items = new Fieldcollection();
        $item = new FieldCollection\Data\Unittestfieldcollection();
        $item->setLinput('textEN', 'en');
        $items->add($item);
        $object->setFieldcollection($items);
        $object->save();

        //create new unpublished version of object
        $object = Concrete::getById($object->getId(), ['force' => true]);
        $object->getFieldcollection();
        $object->setInput($object->getInput() + 1);
        $object->saveVersion();

        [$databaseObject, $latestObjectVersion] = $this->reloadObject($object->getId());

        $fieldCollectionItems = $latestObjectVersion->getFieldcollection()->getItems();
        foreach ($fieldCollectionItems as $item) {
            $this->assertEquals($item->getObject()->getInput(), $latestObjectVersion->getInput(), 'Object reference in field collection is not right.');
            $this->assertEquals($item->getLocalizedFields()->getObject()->getInput(), $latestObjectVersion->getInput(), 'Object reference in localized field in field collection is not right.');
        }
    }

    public function testLocalizedFieldInObjectBrick(): void
    {
        $object = $this->createDataObject();
        $brick = new LazyLoadingLocalizedTest($object);
        $brick->setLInput(uniqid());
        $object->getBricks()->setLazyLoadingLocalizedTest($brick);
        $object->setInput('1');

        $object->save();

        //create new unpublished version of object
        $object = Concrete::getById($object->getId(), ['force' => true]);
        $object->getBricks();
        $object->setInput($object->getInput() + 1);
        $object->saveVersion();

        [$databaseObject, $latestObjectVersion] = $this->reloadObject($object->getId());

        $brickItems = $latestObjectVersion->getBricks()->getItems();
        foreach ($brickItems as $item) {
            $this->assertEquals($item->getObject()->getInput(), $latestObjectVersion->getInput(), 'Object reference in object brick is not right.');
            $this->assertEquals($item->getLocalizedFields()->getObject()->getInput(), $latestObjectVersion->getInput(), 'Object reference in localized field in object brick is not right.');
        }
    }
}
