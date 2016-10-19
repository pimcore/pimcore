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
        \Pimcore\Model\Object\AbstractObject::setHideUnpublished(false);


        $collectionName = "unittestfieldcollection";
        try {
            \Pimcore\Model\Object\Fieldcollection\Definition::getByKey($collectionName);
        } catch (Exception $e) {
            $fieldCollection = new \Pimcore\Model\Object\Fieldcollection\Definition();
            $fieldCollection->setKey("$collectionName");

            $json = file_get_contents(TESTS_PATH . "/resources/objects/fieldcollection-import.json");

            $collection = new \Pimcore\Model\Object\Fieldcollection\Definition();
            $collection->setKey($collectionName);

            \Pimcore\Model\Object\ClassDefinition\Service::importFieldCollectionFromJson($collection, $json);
        }

        $unittestClass = \Pimcore\Model\Object\ClassDefinition::getByName("unittest");
        if (!\Pimcore\Model\Object\ClassDefinition::getByName("unittest")) {
            $json = file_get_contents(TESTS_PATH . "/resources/objects/class-import.json");

            $class = new \Pimcore\Model\Object\ClassDefinition();
            $class->setName("unittest");
            $class->setUserOwner(1);
            \Pimcore\Model\Object\ClassDefinition\Service::importClassDefinitionFromJson($class, $json);
            $class->save();

            $id = $class->getId();

            $class = \Pimcore\Model\Object\ClassDefinition::getById($id);
            $class->setUserModification(1);
            $class->setModificationDate(time());

            $fd = $class->getFieldDefinition("objectswithmetadata");
            if ($fd) {
                $fd->setAllowedClassId($class->getId());
            }

            $class->save();

            $unittestClass = $class;
        }

        if (!\Pimcore\Model\Object\ClassDefinition::getByName("allfields")) {
            $conf = new Zend_Config_Xml(TESTS_PATH . "/resources/objects/class-allfields.xml");
            $importData = $conf->toArray();

            $layout = \Pimcore\Model\Object\ClassDefinition\Service::generateLayoutTreeFromArray($importData["layoutDefinitions"]);

            $class = \Pimcore\Model\Object\ClassDefinition::create();
            $class->setName("allfields");
            $class->setUserOwner(1);
            $class->save();

            $id = $class->getId();
            $class = Pimcore\Model\Object\ClassDefinition::getById($id);

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
            \Pimcore\Model\Object\Objectbrick\Definition::getByKey($brickname);
        } catch (Exception $e) {
            $objectBrick = new \Pimcore\Model\Object\Objectbrick\Definition();
            $objectBrick->setKey($brickname);

            $conf = new Zend_Config_Xml(TESTS_PATH . "/resources/objects/brick-import.xml");
            $importData = $conf->toArray();

            $layout = \Pimcore\Model\Object\ClassDefinition\Service::generateLayoutTreeFromArray($importData["layoutDefinitions"]);
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
