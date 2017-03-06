<?php

namespace Pimcore\Tests\Rest;

use Codeception\Util\Debug;
use Pimcore\Model\Object\Unittest;
use Pimcore\Tests\RestTester;
use Pimcore\Tests\Test\RestTestCase;
use Pimcore\Tests\Util\TestHelper;

class ObjectTest extends RestTestCase
{
    /**
     * @var RestTester
     */
    protected $tester;

    /**
     * @var RestClient
     */
    protected $restClient;

    public function setUp()
    {
        parent::setUp();

        // every single rest test assumes a clean database
        TestHelper::cleanUp();

        // authenticate as rest user
        $this->tester->addApiKeyParam('rest');

        // setup test rest client
        $this->restClient = new RestClient($this->tester);
    }

    public function testObjectList()
    {
        $list = $this->restClient->getObjectList();

        $this->assertEquals(1, count($list), "expected 1 list item");
        $this->assertEquals("folder", $list[0]->getType(), "expected type to be folder");
    }

    public function testObjectGet()
    {
        $object = $this->restClient->getObjectById(1);

        $this->assertEquals("folder", $object->getType(), "expected type to be folder");
        $this->assertEquals(1, $object->getId(), "wrong id");

        $originalCount = TestHelper::getObjectCount();

        $emptyObject = TestHelper::createEmptyObject();
        $id          = $emptyObject->getId();

        $newLocalCount = TestHelper::getObjectCount();
        $newApiCount   = $this->restClient->getObjectCount();
        $expectedCount = $originalCount + 1;

        $this->assertEquals($expectedCount, $newLocalCount);
        $this->assertEquals($expectedCount, $newApiCount);

        $object = $this->restClient->getObjectById($id);

        $this->assertNotNull($object, "expected new object");
        $this->assertInstanceOf(Unittest::class, $object);
        $this->assertEquals($emptyObject->getId(), $object->getId());
        $this->assertEquals($emptyObject->getFullPath(), $object->getFullPath());
    }

    public function testCreateObjectConcrete()
    {
        $this->printTestName();
        $this->assertEquals(1, Test_Tool::getObjectCount());

        $unsavedObject = Test_Tool::createEmptyObject("", false);
        // object not saved, object count must still be one
        $this->assertEquals(1, Test_Tool::getObjectCount());

        $time = time();

        $result = self::getRestClient()->createObjectConcrete($unsavedObject);
//        var_dump($result);
        $this->assertTrue($result->success, "request not successful . " . $result->msg);
        $this->assertEquals(2, Test_Tool::getObjectCount());

        $id = $result->id;
        $this->assertTrue($id > 1, "id must be greater than 1");

        $objectDirect = Object_Abstract::getById($id);
        $creationDate = $objectDirect->getCreationDate();
        $this->assertTrue($creationDate >= $time, "wrong creation date");

        // as the object key is unique there must be exactly one object with that key
        $list = self::getRestClient()->getObjectList("o_key = '" . $unsavedObject->getKey() . "'");
        $this->assertEquals(1, count($list));
    }

    public function testDelete()
    {
        $this->printTestName();

        $savedObject = Test_Tool::createEmptyObject();

        $savedObject = Object_Abstract::getById($savedObject->getId());
        $this->assertNotNull($savedObject);

        $response = self::getRestClient()->deleteObject($savedObject->getId());
        $this->assertTrue($response->success, "request wasn't successful");

        // this will wipe our local cache
        Pimcore::collectGarbage();

        $savedObject = Object_Abstract::getById($savedObject->getId());
        $this->assertTrue($savedObject == null, "object still exists");
    }

    public function testFolder()
    {
        $this->printTestName();

        // create folder but don't save it
        $folder = Test_Tool::createEmptyObject("myfolder", false);
        $folder->setType("folder");

        $fitem = Object_Abstract::getById($folder->getId());
        $this->assertNull($fitem);

        $response = self::getRestClient()->createObjectFolder($folder);
        $this->assertTrue($response->success, "request wasn't successful");

        $id = $response->id;
        $this->assertTrue($id > 1, "id not set");

        $folderDirect = Object_Abstract::getById($id);
        $this->assertTrue($folderDirect->getType() == "folder");

        $folderRest = self::getRestClient()->getObjectById($id);
        $this->assertTrue(Test_Tool::objectsAreEqual($folderRest, $folderDirect, false), "objects are not equal");

        self::getRestClient()->deleteObject($id);

        Pimcore::collectGarbage();
        $folderDirect = Object_Abstract::getById($id);
        $this->assertNull($folderDirect, "folder still exists");
    }
}
