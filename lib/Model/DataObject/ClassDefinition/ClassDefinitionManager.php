<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ClassDefinition;

use Pimcore\Db;
use Pimcore\Model\DataObject\ClassDefinition;

class ClassDefinitionManager
{
    public const SAVED = 'saved';
    public const CREATED = 'created';
    public const DELETED = 'deleted';

    /**
     * Delete all classes from db
     */
    public function cleanUpDeletedClassDefinitions(): array
    {
        $db = \Pimcore\Db::get();
        $classes = $db->fetchAll('SELECT * FROM classes');
        $deleted = [];

        foreach ($classes as $class) {
            $id = $class['id'];
            $name = $class['name'];

            $cls = new ClassDefinition();
            $cls->setId($id);
            $definitionFile = $cls->getDefinitionFile($name);

            if (!file_exists($definitionFile)) {
                $deleted[] = [$name, $id];

                //ClassDefinition doesn't exist anymore, therefore we delete it
                $cls->delete();
            }
        }

        return $deleted;
    }

    /**
     * Updates all classes from PIMCORE_CLASS_DIRECTORY
     */
    public function createOrUpdateClassDefinitions(): array
    {
        $objectClassesFolder = PIMCORE_CLASS_DIRECTORY;
        $files = glob($objectClassesFolder.'/*.php');

        $changes = [];

        foreach ($files as $file) {
            $class = include $file;

            if ($class instanceof ClassDefinition) {
                $existingClass = ClassDefinition::getByName($class->getName());

                if ($existingClass instanceof ClassDefinition) {
                    $changes[] = [$class->getName(), $class->getId(), self::SAVED];
                    $existingClass->save(false);
                } else {
                    $changes[] = [$class->getName(), $class->getId(), self::CREATED];
                    $class->save(false);
                }
            }
        }

        return $changes;
    }
}
