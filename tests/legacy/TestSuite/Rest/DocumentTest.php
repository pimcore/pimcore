<?php
/**
 * Created by IntelliJ IDEA.
 * User: Michi
 * Date: 11.11.2010
 * Time: 10:35:07
 */


class TestSuite_Rest_DocumentTest extends Test_BaseRest
{
    public function setUp()
    {
        //        // every single rest test assumes a clean database
        Test_Tool::cleanUp();
        parent::setUp();
    }

    public function testCreate()
    {
        $this->printTestName();
        $this->assertEquals(1, Test_Tool::getDocoumentCount());

        $unsavedObject = Test_Tool::createEmptyDocumentPage("", false);
        // object not saved, object count must still be one
        $this->assertEquals(1, Test_Tool::getDocoumentCount());

        $time = time();

        $result = self::getRestClient()->createDocument($unsavedObject);
        $this->assertTrue($result->success, "request not successful");
        $this->assertEquals(2, Test_Tool::getDocoumentCount());

        $id = $result->id;
        $this->assertTrue($id > 1, "id must be greater than 1");

        $objectDirect = Document::getById($id);
        $creationDate = $objectDirect->getCreationDate();
        $this->assertTrue($creationDate >= $time, "wrong creation date");


        // as the object key is unique there must be exactly one document with that key
        $list = self::getRestClient()->getDocumentList("`key` = '" . $unsavedObject->getKey() . "'");


        $this->assertEquals(1, count($list));
    }



    public function testDelete()
    {
        $this->printTestName();
        $document = Test_Tool::createEmptyDocumentPage();

        $savedDocument = Document::getById($document->getId());
        $this->assertNotNull($savedDocument);

        $response = self::getRestClient()->deleteDocument($document->getId());
        $this->assertTrue($response->success, "request wasn't successful");

        // this will wipe our local cache
        Pimcore::collectGarbage();

        $dd = Document::getById(2);

        // do not use assertNull, otherwise phpunit will dump the entire bloody object
        $this->assertTrue($dd == null, "document still exists");
    }


    public function testFolder()
    {
        $this->printTestName();

        // create folder but don't save it
        $folder = Test_Tool::createEmptyDocumentPage("myfolder", false);
        $folder->setType("folder");

        $fitem = Document::getById($folder->getId());
        $this->assertNull($fitem);

        $response = self::getRestClient()->createDocumentFolder($folder);
        $this->assertTrue($response->success, "request wasn't successful");

        $id = $response->id;
        $this->assertTrue($id > 1, "id not set");

        $folderDirect = Document::getById($id);
        $this->assertTrue($folderDirect->getType() == "folder");

        $folderRest = self::getRestClient()->getDocumentById($id);
        $this->assertTrue(Test_Tool::documentsAreEqual($folderRest, $folderDirect, false), "documents are not equal");

        self::getRestClient()->deleteDocument($id);

        Pimcore::collectGarbage();
        $folderDirect = Document::getById($id);
        $this->assertNull($folderDirect, "folder still exists");
    }
}
