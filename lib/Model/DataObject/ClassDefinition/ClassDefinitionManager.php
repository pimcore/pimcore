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

namespace Pimcore\Model\DataObject\ClassDefinition;

use Doctrine\DBAL\Exception;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinitionInterface;
use Pimcore\Model\DataObject\Exception\DefinitionWriteException;

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
        return $this->createOrUpdateClassDefinitionsInternal($force, true);
    }

    /**
     * Updates all classes from PIMCORE_CLASS_DEFINITION_DIRECTORY with more control over the saving process.
     * Added as a separate method to avoid compatibility issues.
     * TODO: Should be refactored in Pimcore 13 to avoid duplication with createOrUpdateClassDefinitions.
     *
     * @param bool $force whether to always update no matter if the model definition changed or not
     * @param bool $dumpPHPClasses whether to write the PHP classes to the disk, if false, only the database will be updated
     *
     * @return list<array{string, string, string}>
     */
    public function dumpClassDefinitions(bool $force = false, bool $dumpPHPClasses = true): array
    {
        return $this->createOrUpdateClassDefinitionsInternal($force, $dumpPHPClasses);
    }

    /**
     * @return list<array{string, string, string}>
     */
    private function createOrUpdateClassDefinitionsInternal(bool $force, bool $dumpPHPClasses): array
    {
        $objectClassesFolders = array_filter(array_unique(array_map('realpath', [
            PIMCORE_CLASS_DEFINITION_DIRECTORY,
            PIMCORE_CUSTOM_CONFIGURATION_CLASS_DEFINITION_DIRECTORY,
        ])));

        $changes = [];
        $includedFiles = [];

        foreach ($objectClassesFolders as $objectClassesFolder) {
            $files = glob($objectClassesFolder . '/*.php');
            foreach ($files as $file) {
                $realFile = realpath($file);

                if (isset($includedFiles[$realFile])) {
                    continue;
                }

                $includedFiles[$realFile] = true;
                $class = include $file;

                if ($class instanceof ClassDefinitionInterface) {
                    $existingClass = ClassDefinition::getByName($class->getName());

                    if ($existingClass instanceof ClassDefinitionInterface) {
                        $classSaved = $this->dumpClass($existingClass, false, $dumpPHPClasses, $force);
                        $changes[] = [$existingClass->getName(), $existingClass->getId(), $classSaved ? self::SAVED : self::SKIPPED];
                    } else {
                        //when creating, it should always save like as forced
                        $classSaved = $this->dumpClass($class, false, $dumpPHPClasses, true);
                        $changes[] = [$class->getName(), $class->getId(), $classSaved ? self::CREATED : self::SKIPPED];
                    }
                }
            }
        }

        return $changes;
    }

    /**
     * @return bool whether the class was saved (or its PHP class files were regenerated) or not
     *
     * @throws DefinitionWriteException     *
     * @throws Exception
     */
    public function saveClass(ClassDefinitionInterface $class, bool $saveDefinitionFile, bool $force = false): bool
    {
        return $this->saveClassDefinition($class, $saveDefinitionFile, true, $force);
    }

    /**
     * Additional method that gives more control over the saving process. Added as a separate method to avoid compatibility issues.
     * TODO: Should be refactored in Pimcore 13 to avoid duplication with saveClass.
     *
     * @return bool whether the class was saved (or its PHP class files were regenerated) or not
     *
     * @throws Exception
     * @throws DefinitionWriteException
     */
    public function dumpClass(
        ClassDefinitionInterface $class,
        bool $saveDefinitionFile,
        bool $dumpPHPClasses,
        bool $force = false
    ): bool {
        return $this->saveClassDefinition($class, $saveDefinitionFile, $dumpPHPClasses, $force);
    }

    public function hasChanges(ClassDefinitionInterface $class): bool
    {
        $db = \Pimcore\Db::get();
        $definitionModificationDate = null;

        if ($classId = $class->getId()) {
            $definitionModificationDate = $db->fetchOne('SELECT definitionModificationDate FROM classes WHERE id = ?;', [$classId]);
        }

        return !$definitionModificationDate || $definitionModificationDate !== $class->getModificationDate();
    }

    /**
     * Checks whether the generated PHP class files of a class definition are missing or outdated
     * on the local filesystem. This can be the case even if the database is already up-to-date,
     * e.g. when another node sharing the same database has performed a rebuild, while this
     * node's filesystem was never updated.
     */
    public function hasStalePhpClassFiles(ClassDefinitionInterface $class): bool
    {
        $definitionModificationTime = (int) $class->getModificationDate();

        $definitionFile = $class->getDefinitionFile();
        if (is_file($definitionFile) && ($definitionFileModificationTime = filemtime($definitionFile)) !== false) {
            $definitionModificationTime = max($definitionModificationTime, $definitionFileModificationTime);
        }

        foreach ([$class->getPhpClassFile(), $class->getPhpListingClassFile()] as $phpClassFile) {
            if (!is_file($phpClassFile)) {
                return true;
            }

            $phpClassFileModificationTime = filemtime($phpClassFile);
            if ($phpClassFileModificationTime === false || $phpClassFileModificationTime < $definitionModificationTime) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws Exception
     * @throws DefinitionWriteException
     */
    private function saveClassDefinition(
        ClassDefinitionInterface|ClassDefinition $class,
        bool $saveDefinitionFile,
        bool $dumpPHPClasses = true,
        bool $force = false
    ): bool {
        $shouldSave = $force;

        if (!$force && $this->hasChanges($class)) {
            $shouldSave = true;
        }

        if ($shouldSave) {
            if ($class instanceof ClassDefinition) {
                $class->dumpClass($saveDefinitionFile, $dumpPHPClasses);
            } else {
                $class->save($saveDefinitionFile);
            }

            return true;
        }

        // the shared database may already be up-to-date (e.g. another node performed the rebuild first),
        // while the PHP class files on this node are still missing or outdated - regenerate them
        // locally without touching the database
        if ($dumpPHPClasses && $this->hasStalePhpClassFiles($class)) {
            $class->generateClassFiles(false);

            return true;
        }

        return false;
    }
}
