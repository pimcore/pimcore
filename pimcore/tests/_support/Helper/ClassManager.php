<?php

namespace Pimcore\Tests\Helper;

use Codeception\Module;
use Pimcore\Model\Object\ClassDefinition;
use Pimcore\Model\Object\Fieldcollection\Definition as FieldcollectionDefinition;
use Pimcore\Model\Object\Objectbrick\Definition as ObjectbrickDefinition;
use Symfony\Component\Filesystem\Filesystem;

class ClassManager extends Module
{
    /**
     * @param string $name
     *
     * @return ClassDefinition|null
     */
    public function getClass($name)
    {
        if ($class = ClassDefinition::getByName($name)) {
            return $class;
        }
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasClass($name)
    {
        return null !== $this->getClass($name);
    }

    /**
     * Create or load a class definition
     *
     * @param string $name
     * @param string $filename
     *
     * @return ClassDefinition
     */
    public function setupClass($name, $filename)
    {
        // class either already exists or it must be created
        if (!$this->hasClass($name)) {
            $this->debug(sprintf('[CLASSMANAGER] Setting up class %s', $name));

            $json = $this->loadJson($filename);

            $class = new ClassDefinition();
            $class->setName($name);
            $class->setUserOwner(1);

            ClassDefinition\Service::importClassDefinitionFromJson($class, $json, true);

            $class->save();

            $class = ClassDefinition::getById($class->getId());
            $class->setUserModification(1);
            $class->setModificationDate(time());

            $class->save();
        }

        $class = $this->getClass($name);

        $this->assertNotNull($class, sprintf('Test class %s does not exist and could not be created', $name));
        $this->assertInstanceOf(ClassDefinition::class, $class);

        $classFile = PIMCORE_CLASS_DIRECTORY . '/Object/' . ucfirst($class->getName()) . '.php';
        $this->assertFileExists($classFile, sprintf('Test class file %s does not exist', $classFile));

        $fullClassName = 'Pimcore\\Model\\Object\\' . ucfirst($class->getName());
        $this->assertTrue(class_exists($fullClassName), sprintf('Class %s cannot be found/loaded', $fullClassName));

        return $class;
    }

    /**
     * @param string $name
     *
     * @return FieldcollectionDefinition
     */
    public function getFieldcollection($name)
    {
        try {
            if ($fc = FieldcollectionDefinition::getByKey($name)) {
                return $fc;
            }
        } catch (\Exception $e) {
        }
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasFieldCollection($name)
    {
        return null !== $this->getFieldcollection($name);
    }

    /**
     * Create or load a fieldcollection
     *
     * @param string $name
     * @param string $filename
     *
     * @return FieldcollectionDefinition
     */
    public function setupFieldcollection($name, $filename)
    {
        if (!$this->hasFieldCollection($name)) {
            $this->debug(sprintf('[CLASSMANAGER] Setting up fieldcollection %s', $name));

            $fieldCollection = new FieldcollectionDefinition();
            $fieldCollection->setKey($name);

            $json = $this->loadJson($filename);

            ClassDefinition\Service::importFieldCollectionFromJson($fieldCollection, $json, true);
        }

        $fieldCollection = $this->getFieldcollection($name);

        $this->assertNotNull($fieldCollection, sprintf('Test fieldcollection %s does not exist and could not be created', $name));
        $this->assertInstanceOf(FieldcollectionDefinition::class, $fieldCollection);

        return $fieldCollection;
    }

    /**
     * @param $name
     *
     * @return ObjectbrickDefinition|null
     */
    public function getObjectbrick($name)
    {
        try {
            if ($ob = ObjectbrickDefinition::getByKey($name)) {
                return $ob;
            }
        } catch (\Exception $e) {
        }
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasObjectbrick($name)
    {
        return null !== $this->getObjectbrick($name);
    }

    /**
     * Create or load a fieldcollection. Needs an array of class IDs which are mapped to the classDefinitions
     * field in the export file.
     *
     * @param string $name
     * @param string $filename
     *
     * @return ObjectbrickDefinition
     */
    public function setupObjectbrick($name, $filename)
    {
        if (!$this->hasObjectbrick($name)) {
            $this->debug(sprintf('[CLASSMANAGER] Setting up objectbrick %s', $name));

            $objectBrick = new ObjectbrickDefinition();
            $objectBrick->setKey($name);

            $json = $this->loadJson($filename);

            ClassDefinition\Service::importObjectBrickFromJson($objectBrick, $json, true);
        }

        $objectBrick = $this->getObjectbrick($name);

        $this->assertNotNull($objectBrick, sprintf('Test objectbrick %s does not exist and could not be created', $name));
        $this->assertInstanceOf(ObjectbrickDefinition::class, $objectBrick);

        return $objectBrick;
    }

    /**
     * Load JSON for file
     *
     * @param string $filename
     *
     * @return string
     */
    protected function loadJson($filename)
    {
        $path = $this->resolveFilePath($filename);
        $json = file_get_contents($path);

        $this->assertNotEmpty($json);

        return $json;
    }

    /**
     * Resolve filename to reource path
     *
     * @param string $filename
     *
     * @return string
     */
    protected function resolveFilePath($filename)
    {
        $fs   = new Filesystem();
        $path = $filename;

        // prepend standard resources dir if relative path
        if (!$fs->isAbsolutePath($filename)) {
            $path = __DIR__ . '/../Resources/objects/' . $filename;
        }

        $this->assertFileExists($path);

        return $path;
    }
}
