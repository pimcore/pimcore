<?php
/**
 * Created by JetBrains PhpStorm.
 * User: jaichhorn
 * Date: 31.01.13
 * Time: 09:33
 * To change this template use File | Settings | File Templates.
 */
class Test_SuiteBase extends PHPUnit_Framework_TestSuite
{

    protected function setUp() {
        // turn off frontend mode by default
        Object_Abstract::setHideUnpublished(false);

        if (!Object_Class::getByName("unittest")) {
            $conf = new Zend_Config_Xml(TESTS_PATH . "/resources/objects/class-import.xml");
            $importData = $conf->toArray();

            $layout = Object_Class_Service::generateLayoutTreeFromArray($importData["layoutDefinitions"]);

            $class = Object_Class::create();
            $class->setName("unittest");
            $class->setUserOwner(1);
            $class->save();

            $id = $class->getId();
            $class = Object_Class::getById($id);

            $class->setLayoutDefinitions($layout);

            $class->setUserModification(1);
            $class->setModificationDate(time());

            $class->save();
        }

        if (!Object_Class::getByName("allfields")) {
            $conf = new Zend_Config_Xml(TESTS_PATH . "/resources/objects/class-allfields.xml");
            $importData = $conf->toArray();

            $layout = Object_Class_Service::generateLayoutTreeFromArray($importData["layoutDefinitions"]);

            $class = Object_Class::create();
            $class->setName("allfields");
            $class->setUserOwner(1);
            $class->save();

            $id = $class->getId();
            $class = Object_Class::getById($id);

            $class->setLayoutDefinitions($layout);

            $class->setUserModification(1);
            $class->setModificationDate(time());

            $class->save();
        }

    }

    protected function tearDown() {
        Test_Tool::cleanUp();
    }


}
