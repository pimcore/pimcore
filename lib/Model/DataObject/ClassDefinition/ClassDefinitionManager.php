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
     * @return list<array{string, string, string}>
     */
    public function createOrUpdateClassDefinitions(): array
    {
        $objectClassesFolders = array_unique([PIMCORE_CLASS_DEFINITION_DIRECTORY, PIMCORE_CUSTOM_CONFIGURATION_CLASS_DEFINITION_DIRECTORY]);
        $changes = [];

        foreach ($objectClassesFolders as $objectClassesFolder) {
            $files = glob($objectClassesFolder.'/*.php');
            foreach ($files as $file) {
                $class = include $file;

                if ($class instanceof ClassDefinitionInterface) {
                    $existingClass = ClassDefinition::getByName($class->getName());

                    if ($existingClass instanceof ClassDefinitionInterface) {
                        $changes[] = [$class->getName(), $class->getId(), self::SAVED];
                        $existingClass->save(false);
                    } else {
                        $changes[] = [$class->getName(), $class->getId(), self::CREATED];
                        $class->save(false);
                    }
                }
            }
        }

        return $changes;
    }
}
