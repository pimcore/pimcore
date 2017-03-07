<?php

namespace Pimcore\Tests\Rest;

use Pimcore\Model\Document;
use Pimcore\Tests\Test\RestTestCase;
use Pimcore\Tests\Util\TestHelper;

class DocumentTest extends RestTestCase
{
    public function testCreate()
    {
        $this->assertEquals(1, TestHelper::getDocumentCount());

        $unsavedObject = TestHelper::createEmptyDocumentPage('', false);

        // object not saved, object count must still be one
        $this->assertEquals(1, TestHelper::getDocumentCount());

        $time = time();

        $result = $this->restClient->createDocument($unsavedObject);

        $this->assertTrue($result->success, "request not successful");
        $this->assertEquals(2, TestHelper::getDocumentCount());

        $id = $result->id;
        $this->assertTrue($id > 1, "id must be greater than 1");

        $documentDirect = Document::getById($id);
        $creationDate   = $documentDirect->getCreationDate();

        $this->assertGreaterThanOrEqual($time, $creationDate, 'wrong creation date');

        // as the object key is unique there must be exactly one document with that key
        $list = $this->restClient->getDocumentList("`key` = '" . $unsavedObject->getKey() . "'");

        $this->assertEquals(1, count($list));
    }

    public function testDelete()
    {
        $document = TestHelper::createEmptyDocumentPage();

        $savedDocument = Document::getById($document->getId());
        $this->assertNotNull($savedDocument);

        $response = $this->restClient->deleteDocument($document->getId());
        $this->assertTrue($response->success, "request wasn't successful");

        // this will wipe our local cache
        \Pimcore::collectGarbage();

        $documentDirect = Document::getById(2);

        // do not use assertNull, otherwise phpunit will dump the entire bloody object
        $this->assertTrue($documentDirect === null, "document still exists");
    }

    public function testFolder()
    {
        // create folder but don't save it
        $folder = TestHelper::createEmptyDocumentFolder("myfolder", false);

        $fitem = Document::getById($folder->getId());
        $this->assertNull($fitem);

        $response = $this->restClient->createDocumentFolder($folder);
        $this->assertTrue($response->success, "request wasn't successful");

        $id = $response->id;
        $this->assertTrue($id > 1, "id not set");

        $folderDirect = Document::getById($id);
        $this->assertTrue($folderDirect->getType() === "folder");
        $this->assertInstanceOf(Document\Folder::class, $folderDirect);

        $folderRest = $this->restClient->getDocumentById($id);
        $this->assertTrue(TestHelper::documentsAreEqual($folderRest, $folderDirect, false), "documents are not equal");
        $this->assertInstanceOf(Document\Folder::class, $folderRest);

        $this->restClient->deleteDocument($id);

        \Pimcore::collectGarbage();

        $folderDirect = Document::getById($id);
        $this->assertNull($folderDirect, "folder still exists");
    }
}
