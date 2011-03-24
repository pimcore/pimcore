<?php
/**
 * Created by IntelliJ IDEA.
 * User: Michi
 * Date: 11.11.2010
 * Time: 10:35:07
 */


class Webservice_DocumentTest extends PHPUnit_Framework_TestCase
{

    /**
     * @return Zend_Soap_Client
     */
    protected function getSoapClient()
    {


        return Test_Tool::getSoapClient();
    }

    /**
     * @return string
     */
    protected function getListCondition()
    {

        $conf = Zend_Registry::get("pimcore_config_test");
        return $conf->webservice->document->condition;

    }

    public function testDocumentLink()
    {
        $this->documentTest("link");
    }

    public function testDocumentSnippet()
    {
        $this->documentTest("snippet");
    }

    public function testDocumentFolder()
    {
        $this->documentTest("folder");
    }

    public function testDocumentPage()
    {
        $this->documentTest("page");
    }

    protected function documentTest($subtype)
    {


        $client = $this->getSoapClient();

        //get an document list with 3 elements
        $condition = "`type`= '" . $subtype . "'";
        $generalCondition = $this->getListCondition();
        if (!empty($generalCondition)) {
            if (!empty($condition)) {
                $condition .= " AND " . $generalCondition;
            } else {
                $condition = $generalCondition;
            }

        }


        $order = "";
        $orderKey = "";
        $offset = 0;
        $limit = 3;
        $groupBy = "";

        $wsDocument = $client->getDocumentList($condition, $order, $orderKey, $offset, $limit, $groupBy);
        $this->assertTrue(is_array($wsDocument) and $wsDocument[0] instanceof Webservice_Data_Document_List_Item);

        //take the first element and fetch
        $documentId = $wsDocument[0]->id;
        $this->assertTrue(is_numeric($documentId));

        $wsMethod = "getDocument" . ucfirst($subtype) . "ById";

        $wsDocument = $client->$wsMethod($documentId);

        $wsDataClassIn = "Webservice_Data_Document_" . ucfirst($subtype) . "_In";
        $wsDataClassOut = "Webservice_Data_Document_" . ucfirst($subtype) . "_Out";

        $localClass = "Document_" . ucfirst($subtype);

        $this->assertTrue($wsDocument instanceof $wsDataClassOut);

        $document = new $localClass();

        $wsDocument->reverseMap($document);

        //some checks to see if we got a valid document
        $this->assertTrue($document->getCreationDate() > 0);
        $this->assertTrue(strlen($document->getPath()) > 0);


        //copy the document retrieved from ws
        $new = clone $document;
        $new->id = null;
        $new->setKey($document->getKey() . "_phpUnitTestCopy");
        $new->setResource(null);

        //send new document back via ws
        $apiPage = Webservice_Data_Mapper::map($new, $wsDataClassIn, "in");

        $wsMethod = "createDocument" . ucfirst($subtype);
        $id = $client->$wsMethod($apiPage);
        $this->assertTrue($id > 0);

        $wsMethod = "getDocument" . ucfirst($subtype) . "ById";
        $wsDocument = $client->$wsMethod($id);

        $this->assertTrue($wsDocument instanceof $wsDataClassOut);

        $refetchDocument = new $localClass();
        $wsDocument->reverseMap($refetchDocument);

        $this->assertTrue($refetchDocument->getId() > 1);


        $localDocument = $localClass::getById($documentId);

        //do that now, later id is null
        $localDocument->getProperties();

        //remove childs, this can not be set through WS
        $localDocument->childs = null;


        $this->assertTrue(Test_Tool::documentsAreEqual($localDocument, $refetchDocument, true));


        //update document
        if ($document instanceof Document_PageSnippet) {
            $refetchDocument->setPublished(!$refetchDocument->getPublished());
        }
        $refetchDocument->setProperty("updateTest", "text", "a update test");
        $apiPage = Webservice_Data_Mapper::map($refetchDocument, $wsDataClassIn, "in");

        $wsMethod = "updateDocument" . ucfirst($subtype);
        $success = $client->$wsMethod($apiPage);
        $this->assertTrue($success);

        $documentId = $refetchDocument->getId();
        $localDocument = $localClass::getById($documentId);
        $localDocument->getProperties();
        $localDocument->childs = null;
        $this->assertTrue(Test_Tool::documentsAreEqual($localDocument, $refetchDocument, true));

        //delete our test copy
        $success = $client->deleteDocument($refetchDocument->getId());
        $this->assertTrue($success);
    }


    /**
     * @expectedException SoapFault
     * @return void
     *
     */
    public function testCreateElementWithoutKey()
    {


        $new = new Document_Page();
        $apiPage = Webservice_Data_Mapper::map($new, Webservice_Data_Document_Page_In, "in");

        $client = $this->getSoapClient();

        $client->createDocumentPage($apiPage);


    }
}
