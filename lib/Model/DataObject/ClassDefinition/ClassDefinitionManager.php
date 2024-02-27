<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\DataObject\ClassDefinition;

use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinitionInterface;

class ClassDefinitionManager
{
    public const SAVED = 'saved';

    public const CREATED = 'created';

    public const SKIPPED = 'skipped';

    public const DELETED = 'deleted';

    /**
     * Delete all classes from db
     *
     * @return list<array{string, string, string}>
     */
    public function cleanUpDeletedClassDefinitions(): array
    {
        $db = \Pimcore\Db::get();
        $classes = $db->fetchAllAssociative('SELECT * FROM classes');
        $deleted = [];

        foreach ($classes as $class) {
            $id = $class['id'];
            $name = $class['name'];

            $cls = new ClassDefinition();
            $cls->setId($id);
            $cls->setName($name);
            $definitionFile = $cls->getDefinitionFile();

            if (!file_exists($definitionFile)) {
                $deleted[] = [$name, $id, self::DELETED];

                //ClassDefinition doesn't exist anymore, therefore we delete it
                $cls->delete();
            }
        }

        return $deleted;
    }

    /**
     * Updates all classes from PIMCORE_CLASS_DEFINITION_DIRECTORY
     *
     * @param bool $force whether to always update no matter if the model definition changed or not
     *
     * @return list<array{string, string, string}>
     */
    public function createOrUpdateClassDefinitions(bool $force = false): array
    {
        $objectClassesFolders = array_unique([PIMCORE_CLASS_DEFINITION_DIRECTORY, PIMCORE_CUSTOM_CONFIGURATION_CLASS_DEFINITION_DIRECTORY]);

        $changes = [];

        foreach ($objectClassesFolders as $objectClassesFolder) {
            $files = glob($objectClassesFolder . '/*.php');
            foreach ($files as $file) {
                $class = include $file;

                if ($class instanceof ClassDefinitionInterface) {
                    $existingClass = ClassDefinition::getByName($class->getName());

                    if ($existingClass instanceof ClassDefinitionInterface) {
                        $classSaved = $this->saveClass($existingClass, false, $force);
                        $changes[] = [$existingClass->getName(), $existingClass->getId(), $classSaved ? self::SAVED : self::SKIPPED];
                    } else {
                        $classSaved = $this->saveClass($class, false, $force);
                        $changes[] = [$class->getName(), $class->getId(), $classSaved ? self::CREATED : self::SKIPPED];
                    }
                }
            }
        }

        return $changes;
    }

    /**
     * @return bool whether the class was saved or not
     */
    public function saveClass(ClassDefinitionInterface $class, bool $saveDefinitionFile, bool $force = false): bool
    {
        $shouldSave = $force;

        if (!$force) {
            $db = \Pimcore\Db::get();

            $definitionModificationDate = null;

            if ($classId = $class->getId()) {
                $definitionModificationDate = $db->fetchOne('SELECT definitionModificationDate FROM classes WHERE id = ?;', [$classId]);
            }

            if (!$definitionModificationDate || $definitionModificationDate !== $class->getModificationDate()) {
                $shouldSave = true;
            }
        }

        if ($shouldSave) {
            $class->save($saveDefinitionFile);
        }

        return $shouldSave;
    }
}
