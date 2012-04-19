<?php
/**
 * Created by IntelliJ IDEA.
 * User: Michi
 * Date: 11.11.2010
 * Time: 10:35:07
 */


class Webservice_ObjectTest extends PHPUnit_Framework_TestCase {

    /**
     * @return Zend_Soap_Client
     */
    protected function getSoapClient() {

        return Test_Tool::getSoapClient();
    }

    /**
     * @return string
     */
    protected function getListCondition() {

        $conf = Zend_Registry::get("pimcore_config_test");
        return $conf->webservice->object->condition;

    }


    public function testObjectFolder() {

        $client = $this->getSoapClient();

        //get an object list with 3 elements
        $condition = "o_type='folder'";
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

        $wsDocument = $client->getObjectList($condition, $order, $orderKey, $offset, $limit, $groupBy);
        $this->assertTrue(is_array($wsDocument) and $wsDocument[0] instanceof Webservice_Data_Object_List_Item);

        //take 2nd element and fetch object  (first will be home)
        $id = $wsDocument[0]->id;
        $this->assertTrue(is_numeric($id));

        $wsDocument = $client->getObjectFolderById($id);

        $this->assertTrue($wsDocument instanceof Webservice_Data_Object_Folder_Out);

        $object = new Object_Folder();
        $wsDocument->reverseMap($object);

        //copy the object retrieved from ws
        $new = clone $object;
        $new->id = null;
        $new->setKey($object->getKey() . "_php-unit-test-copy");
        $new->setResource(null);

        //send new object back via ws
        $apiObject = Webservice_Data_Mapper::map($new, "Webservice_Data_Object_Folder_In", "in");


        $id = $client->createObjectFolder($apiObject);

        $this->assertTrue($id > 0);

        $wsDocument = $client->getObjectFolderById($id);
        $this->assertTrue($wsDocument instanceof Webservice_Data_Object_Folder_Out);

        $refetchObject = new Object_Folder();
        $wsDocument->reverseMap($refetchObject);

        //make sure we deal with 2 different objects
        $this->assertTrue($id == $refetchObject->getId());
        $this->assertTrue($id != $object->getId());

        //compare original object, and the one we mangled back and forth through the web service
        $localObject = Object_Abstract::getById($object->getId());

        //remove childs, this can not be set through WS
        $localObject->setChilds(null);

        $this->assertTrue(Test_Tool::objectsAreEqual($localObject, $refetchObject,true));


        //update object
        $refetchObject->setProperty("updateTest", "text", "a update test");
        $apiObject = Webservice_Data_Mapper::map($refetchObject, "Webservice_Data_Object_Folder_In", "in");
        $success = $client->updateObjectFolder($apiObject);
        $this->assertTrue($success);

        $id = $refetchObject->getId();
        Test_Tool::resetRegistry();
        $localObject = Object_Abstract::getById($id);
        $localObject->setChilds(null);

        $this->assertTrue(Test_Tool::objectsAreEqual($localObject, $refetchObject, true));

        //delete our test copy
        $success = $client->deleteObject($refetchObject->getId());
        $this->assertTrue($success);


    }


    public function testObjectConcrete() {

        $client = $this->getSoapClient();

        //get an object list with 3 elements
        $condition = "o_type='object'";
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

        $wsDocument = $client->getObjectList($condition, $order, $orderKey, $offset, $limit, $groupBy);
        $this->assertTrue(is_array($wsDocument) and $wsDocument[0] instanceof Webservice_Data_Object_List_Item);

        //take first element and fetch object
        $id = $wsDocument[0]->id;
        $this->assertTrue(is_numeric($id));
        $wsDocument = $client->getObjectConcreteById($id);
        $this->assertTrue($wsDocument instanceof Webservice_Data_Object_Concrete_Out);
        $className = "Object_" . ucfirst($wsDocument->className);
        $this->assertTrue(class_exists($className));
        $object = new $className();

        $wsDocument->reverseMap($object);
        //some checks to see if we got a valid object
        $this->assertTrue($object->getCreationDate() > 0);
        $this->assertTrue(strlen($object->getPath()) > 0);

        //copy the object retrieved from ws
        $new = clone $object;
        $new->id = null;
        $new->setKey($object->getKey() . "_php-unit-test-copy");
        $new->setResource(null);
        //send new object back via ws
        $apiObject = Webservice_Data_Mapper::map($new, "Webservice_Data_Object_Concrete_In", "in");

        $id = $client->createObjectConcrete($apiObject);

        $this->assertTrue($id > 0);

        $wsDocument = $client->getObjectConcreteById($id);
        $this->assertTrue($wsDocument instanceof Webservice_Data_Object_Concrete_Out);
        $refetchObject = new $className();
        $wsDocument->reverseMap($refetchObject);

        //make sure we deal with 2 different objects
        $this->assertTrue($id == $refetchObject->getId());
        $this->assertTrue($id != $object->getId());

        //compare original object, and the one we mangled back and forth through the web service
        $localObject = Object_Abstract::getById($object->getId());
        //remove childs, this can not be set through WS
        $localObject->setChilds(null);

        $this->assertTrue(Test_Tool::objectsAreEqual($localObject, $refetchObject,true));


        //update object
        $refetchObject->setProperty("updateTest", "text", "a update test");
        $refetchObject->setInput("my updated test");
        $apiObject = Webservice_Data_Mapper::map($refetchObject, "Webservice_Data_Object_Concrete_In", "in");
//        Logger::err(print_r($apiObject,true));

        $success = $client->updateObjectConcrete($apiObject);
         Logger::err($client->getLastRequest());

        $this->assertTrue($success);
        $id = $refetchObject->getId();
        Test_Tool::resetRegistry();
        $localObject = Object_Abstract::getById($id);
        $localObject->setChilds(null);

        $this->assertTrue(Test_Tool::objectsAreEqual($localObject, $refetchObject, true));

        //delete our test copy
        $success = $client->deleteObject($refetchObject->getId());
        $this->assertTrue($success);


    }


}
