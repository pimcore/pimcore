<?php

namespace Pimcore\Tests\Helper;

use Codeception\Module;
use Pimcore\Model\Object\ClassDefinition;

class ClassManager extends Module
{
    /**
     * Create or load a class definition
     *
     * @param $name
     * @param $jsonFile
     *
     * @return ClassDefinition
     */
    public function createClass($name, $jsonFile)
    {
        // class either already exists or it must be created
        $class = ClassDefinition::getByName($name);

        if (!$class) {
            $jsonPath = __DIR__ . '/../Resources/objects/' . $jsonFile;
            $this->assertFileExists($jsonPath);

            $json = file_get_contents($jsonPath);
            $this->assertNotEmpty($json);

            $class = new ClassDefinition();
            $class->setName($name);
            $class->setUserOwner(1);

            ClassDefinition\Service::importClassDefinitionFromJson($class, $json);

            $class->save();

            $id = $class->getId();

            $class = ClassDefinition::getById($id);
            $class->setUserModification(1);
            $class->setModificationDate(time());

            /** @var ClassDefinition\Data\ObjectsMetadata $fd */
            $fd = $class->getFieldDefinition('objectswithmetadata');
            if ($fd) {
                $fd->setAllowedClassId($class->getId());
            }

            $class->save();
            $class = ClassDefinition::getByName($name);
        }

        $this->assertNotNull($class, "test class does not exist");
        $this->assertInstanceOf(ClassDefinition::class, $class);

        return $class;
    }
}
