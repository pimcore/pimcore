<?php
/**
 * Created by JetBrains PhpStorm.
 * User: jaichhorn
 * Date: 31.01.13
 * Time: 09:29
 * To change this template use File | Settings | File Templates.
 */
class Test_Base extends PHPUnit_Framework_TestCase
{

    /** If true (default) the base will make sure that the unittest class exists
     * @var bool
     */
    public $needsTestClass = true;


    public function printTestName()
    {
        try {
            throw new Exception();
        } catch (Exception $e) {
            $trace = $e->getTrace();
            print("### running ...  " . $trace[1]["class"] . "::" . $trace[1]["function"] . " ... good luck!\n");
        }
    }

    public function setUp()
    {
        if ($this->needsTestClass) {
            // either unittest class already exists or it must be created
            $class = \Pimcore\Model\Object\ClassDefinition::getByName("unittest");
            if (!$class) {
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


                $class = Object_Class::getByName("unittest");
                $this->assertNotNull($class, "test class does not exist");
            }
        }
    }
}
