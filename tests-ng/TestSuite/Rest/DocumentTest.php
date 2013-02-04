<?php
/**
 * Created by IntelliJ IDEA.
 * User: Michi
 * Date: 11.11.2010
 * Time: 10:35:07
 */


class TestSuite_Rest_DocumentTest extends Test_Base {

    public function setUp() {
//        // every single rest test assumes a clean database
//        Test_Tool::cleanUp();
    }

    public function testDelete() {
        $this->printTestName();
        $document = Test_Tool::createEmptyDocumentPage();

        $savedDocument = Document::getById($document->getId());
        $this->assertNotNull($savedDocument);

        $response = Test_RestClient::getInstance()->deleteDocument($document->getId());
        $this->assertTrue($response->success, "request wasn't successful");

        // this will wipe our local cache
        Pimcore::collectGarbage();

        $dd = Document::getById(2);

        // do not use assertNull, otherwise phpunit will dump the entire bloody object
        $this->assertTrue($dd == null, "document still exists");
    }
}
