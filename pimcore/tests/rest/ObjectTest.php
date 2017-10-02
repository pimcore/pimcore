<?php

namespace Pimcore\Tests\Rest;

use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Folder;
use Pimcore\Model\DataObject\Unittest;
use Pimcore\Tests\Test\RestTestCase;
use Pimcore\Tests\Util\TestHelper;

class ObjectTest extends RestTestCase
{
    public function testObjectList()
    {
        $list = $this->restClient->getObjectList();

        $this->assertEquals(1, count($list), 'expected 1 list item');
        $this->assertEquals('folder', $list[0]->getType(), 'expected type to be folder');
    }

    public function testObjectGet()
    {
        $object = $this->restClient->getObjectById(1);

        $this->assertEquals('folder', $object->getType(), 'expected type to be folder');
        $this->assertEquals(1, $object->getId(), 'wrong id');

        $originalCount = TestHelper::getObjectCount();

        $emptyObject = TestHelper::createEmptyObject();
        $id = $emptyObject->getId();

        $newLocalCount = TestHelper::getObjectCount();
        $newApiCount = $this->restClient->getObjectCount();
        $expectedCount = $originalCount + 1;

        $this->assertEquals($expectedCount, $newLocalCount);
        $this->assertEquals($expectedCount, $newApiCount);

        $object = $this->restClient->getObjectById($id);

        $this->assertNotNull($object, 'expected new object');
        $this->assertInstanceOf(Unittest::class, $object);
        $this->assertEquals($emptyObject->getId(), $object->getId());
        $this->assertEquals($emptyObject->getFullPath(), $object->getFullPath());
    }

    public function testCreateObjectConcrete()
    {
        $this->assertEquals(1, TestHelper::getObjectCount());

        $unsavedObject = TestHelper::createEmptyObject('', false);

        // object not saved, object count must still be one
        $this->assertEquals(1, TestHelper::getObjectCount());

        $time = time();

        $result = $this->restClient->createObjectConcrete($unsavedObject);

        $this->assertTrue($result->success, 'request not successful . ' . $result->msg);
        $this->assertEquals(2, TestHelper::getObjectCount());

        $id = $result->id;
        $this->assertTrue($id > 1, 'id must be greater than 1');

        $objectDirect = AbstractObject::getById($id);
        $creationDate = $objectDirect->getCreationDate();

        $this->assertTrue($creationDate >= $time, 'wrong creation date');

        // as the object key is unique there must be exactly one object with that key
        $list = $this->restClient->getObjectList("o_key = '" . $unsavedObject->getKey() . "'");
        $this->assertEquals(1, count($list));
    }

    public function testDelete()
    {
        $savedObject = TestHelper::createEmptyObject();

        $savedObject = AbstractObject::getById($savedObject->getId());
        $this->assertNotNull($savedObject);

        $response = $this->restClient->deleteObject($savedObject->getId());
        $this->assertTrue($response->success, "request wasn't successful");

        // this will wipe our local cache
        \Pimcore::collectGarbage();

        $savedObject = AbstractObject::getById($savedObject->getId());
        $this->assertNull($savedObject, 'object still exists');
    }

    public function testFolder()
    {
        // create folder but don't save it
        /** @var Folder $folder */
        $folder = TestHelper::createObjectFolder('myfolder', false);
        $folder->setType('folder');

        $fitem = AbstractObject::getById($folder->getId());
        $this->assertNull($fitem);

        $response = $this->restClient->createObjectFolder($folder);
        $this->assertTrue($response->success, "request wasn't successful");

        $id = $response->id;
        $this->assertTrue($id > 1, 'id not set');

        $folderDirect = AbstractObject::getById($id);
        $this->assertEquals('folder', $folderDirect->getType());
        $this->assertInstanceOf(Folder::class, $folderDirect);

        $folderRest = $this->restClient->getObjectById($id);
        $this->assertEquals('folder', $folderRest->getType());
        $this->assertInstanceOf(Folder::class, $folderRest);
        $this->assertTrue(TestHelper::objectsAreEqual($folderRest, $folderDirect, false), 'objects are not equal');

        $this->restClient->deleteObject($id);

        \Pimcore::collectGarbage();

        $folderDirect = AbstractObject::getById($id);
        $this->assertNull($folderDirect, 'folder still exists');
    }
}
