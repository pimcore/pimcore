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
    protected function setUp()
    {
        // turn off frontend mode by default
        Object_Abstract::setHideUnpublished(false);


        $collectionName = "unittestfieldcollection";
        try {
            Object_Fieldcollection_Definition::getByKey($collectionName);
        } catch (Exception $e) {
            $fieldCollection = new Object_Fieldcollection_Definition();
            $fieldCollection->setKey("$collectionName");

            $conf = new Zend_Config_Xml(TESTS_PATH . "/resources/objects/fieldcollection-import.xml");
            $importData = $conf->toArray();

            $layout = Object_Class_Service::generateLayoutTreeFromArray($importData["layoutDefinitions"]);
            $fieldCollection->setLayoutDefinitions($layout);
            $fieldCollection->save();
        }

        $unittestClass = Object_Class::getByName("unittest");
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

            $fd = $class->getFieldDefinition("objectswithmetadata");
            if ($fd) {
                $fd->setAllowedClassId($class->getId());
            }

            $class->setUserModification(1);
            $class->setModificationDate(time());

            $class->save();
            $unittestClass = $class;
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

            $fd = $class->getFieldDefinition("objectswithmetadata");
            if ($fd) {
                $fd->setAllowedClassId($class->getId());
            }

            $class->setUserModification(1);
            $class->setModificationDate(time());

            $class->save();
        }

        $brickname = "unittestBrick";

        try {
            Object_Objectbrick_Definition::getByKey($brickname);
        } catch (Exception $e) {
            $objectBrick = new Object_Objectbrick_Definition();
            $objectBrick->setKey($brickname);

            $conf = new Zend_Config_Xml(TESTS_PATH . "/resources/objects/brick-import.xml");
            $importData = $conf->toArray();

            $layout = Object_Class_Service::generateLayoutTreeFromArray($importData["layoutDefinitions"]);
            $objectBrick->setLayoutDefinitions($layout);
            $clDef = $importData["classDefinitions"];
            $newClassDef = ["classname" => $unittestClass->getId(),
                            "fieldname" => $clDef["fieldname"]];


            $objectBrick->setClassDefinitions([
                    $newClassDef]

            );
            try {
                $objectBrick->save();
            } catch (Exception $e) {
                throw $e;
            }
        }
    }

    protected function tearDown()
    {
        Test_Tool::cleanUp();
    }
}
