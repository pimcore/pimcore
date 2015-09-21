<?php
/**
 * Created by JetBrains PhpStorm.
 * User: jaichhorn
 * Date: 31.01.13
 * Time: 09:29
 * To change this template use File | Settings | File Templates.
 */
class Test_Base extends PHPUnit_Framework_TestCase {

    /** If true (default) the base will make sure that the unittest class exists
     * @var bool
     */
    public $needsTestClass = true;


    public function printTestName() {
        try {
            throw new Exception();
        } catch (Exception $e) {
            $trace = $e->getTrace();
            print("### running ...  " . $trace[1]["class"] . "::" . $trace[1]["function"] . " ... good luck!\n");
        }
    }

    public function setUp() {
        if ($this->needsTestClass) {
            // either unittest class already exists or it must be created
            $class = Object_Class::getByName("unittest");
            if (!$class) {

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

                $fd = $class->getFieldDefinition("objectswithmetadata");
                if($fd) {
                    $fd->setAllowedClassId($class->getId());
                }

                $class->save();


                $class = Object_Class::getByName("unittest");
                $this->assertNotNull($class, "test class does not exist");
            }
        }
    }
}