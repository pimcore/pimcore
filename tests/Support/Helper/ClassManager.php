<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Tests\Support\Helper;

use Codeception\Module;
use Exception;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinitionInterface;
use Pimcore\Model\DataObject\Exception\DefinitionWriteException;
use Pimcore\Model\DataObject\Fieldcollection\Definition as FieldcollectionDefinition;
use Pimcore\Model\DataObject\Objectbrick\Definition as ObjectbrickDefinition;
use Symfony\Component\Filesystem\Filesystem;

class ClassManager extends Module
{
    /**
     * @throws Exception
     */
    public function getClass(string $name): ?ClassDefinitionInterface
    {
        if ($class = ClassDefinition::getByName($name)) {
            return $class;
        }

        return null;
    }

    public function hasClass(string $name): bool
    {
        return null !== $this->getClass($name);
    }

    /**
     * Create or load a class definition
     *
     * @throws DefinitionWriteException
     */
    public function setupClass(string $name, string $filename): ClassDefinitionInterface
    {
        // class either already exists or it must be created
        if (!$this->hasClass($name)) {
            $this->debug(sprintf('[CLASSMANAGER] Setting up class %s', $name));

            $json = $this->loadJson($filename);

            $class = new ClassDefinition();
            $class->setName($name);
            $class->setUserOwner(1);
            $class->setId($name);

            ClassDefinition\Service::importClassDefinitionFromJson($class, $json, true);

            $class->save();

            $this->debug(sprintf('[CLASSMANAGER] Setting up class %s DONE', $name));

            $class = ClassDefinition::getById($class->getId());

            $class->setUserModification(1);
            $class->setModificationDate(time());

            $class->save();
        }

        $class = $this->getClass($name);

        $this->assertNotNull($class, sprintf('Test class %s does not exist and could not be created', $name));
        $this->assertInstanceOf(ClassDefinition::class, $class);

        $classFile = PIMCORE_CLASS_DIRECTORY . '/DataObject/' . ucfirst($class->getName()) . '.php';
        $this->assertFileExists($classFile, sprintf('Test class file %s does not exist', $classFile));

        $fullClassName = 'Pimcore\\Model\\DataObject\\' . ucfirst($class->getName());
        $this->assertTrue(class_exists($fullClassName), sprintf('Class %s cannot be found/loaded', $fullClassName));

        return $class;
    }

    /**
     * @throws Exception
     */
    public function getFieldcollection(string $name): ?FieldcollectionDefinition
    {
        $fc = FieldcollectionDefinition::getByKey($name);

        return $fc;
    }

    /**
     *
     * @throws Exception
     */
    public function hasFieldCollection(string $name): bool
    {
        return null !== $this->getFieldcollection($name);
    }

    /**
     * Create or load a fieldcollection
     *
     * @throws Exception
     */
    public function setupFieldcollection(string $name, string $filename): ?FieldcollectionDefinition
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

    public function getObjectbrick(string $name): ?ObjectbrickDefinition
    {
        $ob = ObjectbrickDefinition::getByKey($name);

        return $ob;
    }

    public function hasObjectbrick(string $name): bool
    {
        return null !== $this->getObjectbrick($name);
    }

    /**
     * Create or load a fieldcollection. Needs an array of class IDs which are mapped to the classDefinitions
     * field in the export file.
     *
     */
    public function setupObjectbrick(string $name, string $filename): ObjectbrickDefinition
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
     */
    protected function loadJson(string $filename): string
    {
        $path = $this->resolveFilePath($filename);
        $json = file_get_contents($path);

        $this->assertNotEmpty($json);

        return $json;
    }

    /**
     * Saves JSON to file
     */
    public function saveJson(string $filename, string $json): string
    {
        $this->assertNotEmpty($json);

        $path = $this->resolveFilePath($filename, false);
        $dir = dirname($path);

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($path, $json);

        return $path;
    }

    /**
     * Resolve filename to resource path
     */
    protected function resolveFilePath(string $filename, bool $assert = true): string
    {
        $fs = new Filesystem();
        $path = $filename;

        // prepend standard resources dir if relative path
        if (!$fs->isAbsolutePath($filename)) {
            $path = __DIR__ . '/../Resources/objects/' . $filename;
        }

        if ($assert) {
            $this->assertFileExists($path);
        }

        return $path;
    }
}
