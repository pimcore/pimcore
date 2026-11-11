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

namespace Pimcore\Tests\Model\DataObject;

use Pimcore;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Db;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\ClassDefinitionManager;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @group model.dataobject.object
 */
class ClassDefinitionManagerTest extends ModelTestCase
{
    private const CLASS_NAME = 'managerDbOnly';

    protected function tearDown(): void
    {
        RuntimeCache::clear();

        if ($class = ClassDefinition::getByName(self::CLASS_NAME)) {
            $class->delete();
        } else {
            $class = new ClassDefinition();
            $class->setName(self::CLASS_NAME);

            $filesystem = new Filesystem();
            $filesystem->remove([
                $class->getDefinitionFile(),
                $class->getPhpClassFile(),
                PIMCORE_CLASS_DIRECTORY . '/DataObject/' . ucfirst(self::CLASS_NAME),
            ]);
        }

        parent::tearDown();
    }

    public function testDumpClassDefinitionsCreatesMissingClassWithoutDumpingPhpClasses(): void
    {
        $class = $this->createTestClassDefinition();
        $phpClassFile = $class->getPhpClassFile();

        $this->assertFileExists($class->getDefinitionFile());
        $this->assertFileExists($phpClassFile);

        $this->simulateEmptyDatabase($class);

        $changes = $this->getClassDefinitionManager()->dumpClassDefinitions(dumpPHPClasses: false);

        $this->assertContains(
            [self::CLASS_NAME, self::CLASS_NAME, ClassDefinitionManager::CREATED],
            $changes,
            'Class must be created from its definition file when missing in the database'
        );

        RuntimeCache::clear();
        $this->assertInstanceOf(ClassDefinition::class, ClassDefinition::getByName(self::CLASS_NAME));
        $this->assertFileDoesNotExist($phpClassFile, 'PHP classes must not be dumped when $dumpPHPClasses is false');
    }

    public function testDumpClassDefinitionsRestoresMissingPhpClassesWithoutForce(): void
    {
        $class = $this->createTestClassDefinition();
        $phpClassFile = $class->getPhpClassFile();
        $phpListingClassFile = $class->getPhpListingClassFile();

        $this->simulateEmptyDatabase($class);

        $this->getClassDefinitionManager()->dumpClassDefinitions(dumpPHPClasses: false);
        $this->assertFileDoesNotExist($phpClassFile);
        $this->assertFileDoesNotExist($phpListingClassFile);

        // even without force, missing PHP classes must be restored: the database being up-to-date
        // (e.g. because another node sharing the same database already ran the rebuild)
        // says nothing about the state of the generated files on this node
        RuntimeCache::clear();
        $changes = $this->getClassDefinitionManager()->dumpClassDefinitions();

        $this->assertContains(
            [self::CLASS_NAME, self::CLASS_NAME, ClassDefinitionManager::SAVED],
            $changes,
            'Class must be reported as saved when its PHP classes were regenerated'
        );
        $this->assertFileExists($phpClassFile, 'PHP classes must be restored even if the database did not change');
        $this->assertFileExists($phpListingClassFile, 'PHP listing class must be restored even if the database did not change');
    }

    public function testHasStalePhpClassFilesDetectsMissingAndOutdatedFiles(): void
    {
        $class = $this->createTestClassDefinition();
        $manager = $this->getClassDefinitionManager();

        $definitionFile = $class->getDefinitionFile();
        $phpClassFile = $class->getPhpClassFile();
        $phpListingClassFile = $class->getPhpListingClassFile();
        $modificationDate = $class->getModificationDate();

        // all files in sync with the definition -> up-to-date
        touch($definitionFile, $modificationDate);
        touch($phpClassFile, $modificationDate);
        touch($phpListingClassFile, $modificationDate);
        clearstatcache();
        $this->assertFalse($manager->hasStalePhpClassFiles($class), 'Files in sync with the definition must not be considered stale');

        // generated class file older than the modification date embedded in the definition -> stale
        touch($phpClassFile, $modificationDate - 1);
        clearstatcache();
        $this->assertTrue($manager->hasStalePhpClassFiles($class), 'A class file older than the definition modification date must be considered stale');

        // generated listing class file older than the modification date embedded in the definition -> stale
        touch($phpClassFile, $modificationDate);
        touch($phpListingClassFile, $modificationDate - 1);
        clearstatcache();
        $this->assertTrue($manager->hasStalePhpClassFiles($class), 'A listing class file older than the definition modification date must be considered stale');

        // generated files older than the definition file on disk (e.g. freshly deployed) -> stale
        touch($phpListingClassFile, $modificationDate);
        touch($definitionFile, $modificationDate + 1);
        clearstatcache();
        $this->assertTrue($manager->hasStalePhpClassFiles($class), 'Class files older than the definition file on disk must be considered stale');

        // missing listing class file, while the class file itself exists -> stale
        touch($definitionFile, $modificationDate);
        (new Filesystem())->remove($phpListingClassFile);
        clearstatcache();
        $this->assertTrue($manager->hasStalePhpClassFiles($class), 'A missing listing class file must be considered stale');
    }

    public function testDumpClassDefinitionsRegeneratesOutdatedPhpClassesWithoutForce(): void
    {
        $class = $this->createTestClassDefinition();
        $phpClassFile = $class->getPhpClassFile();

        // settle the generated files, so only the outdated class file triggers the regeneration below
        RuntimeCache::clear();
        $this->getClassDefinitionManager()->dumpClassDefinitions();

        touch($phpClassFile, $class->getModificationDate() - 1);
        clearstatcache();

        RuntimeCache::clear();
        $changes = $this->getClassDefinitionManager()->dumpClassDefinitions();

        $this->assertContains(
            [self::CLASS_NAME, self::CLASS_NAME, ClassDefinitionManager::SAVED],
            $changes,
            'Class must be reported as saved when its outdated PHP classes were regenerated'
        );

        clearstatcache();
        $this->assertGreaterThanOrEqual(
            $class->getModificationDate(),
            filemtime($phpClassFile),
            'An outdated PHP class file must be regenerated even if the database did not change'
        );
    }

    public function testDumpClassDefinitionsSkipsClassesWithUpToDatePhpClasses(): void
    {
        $this->createTestClassDefinition();

        // a first run may regenerate the PHP classes in case their modification time
        // is older than the one of the definition file
        RuntimeCache::clear();
        $this->getClassDefinitionManager()->dumpClassDefinitions();

        RuntimeCache::clear();
        $changes = $this->getClassDefinitionManager()->dumpClassDefinitions();

        $this->assertContains(
            [self::CLASS_NAME, self::CLASS_NAME, ClassDefinitionManager::SKIPPED],
            $changes,
            'Class must be skipped when neither the database nor the generated PHP classes are outdated'
        );
    }

    private function createTestClassDefinition(): ClassDefinition
    {
        $class = new ClassDefinition();
        $class->setName(self::CLASS_NAME);
        $class->setId(self::CLASS_NAME);
        $class->setUserOwner(1);
        $class->save();

        return $class;
    }

    /**
     * Simulates a fresh installation (e.g. PaaS) where the definition files exist,
     * but the classes table is still empty and no PHP classes were generated.
     */
    private function simulateEmptyDatabase(ClassDefinition $class): void
    {
        Db::get()->executeStatement('DELETE FROM classes WHERE id = ?', [$class->getId()]);
        (new Filesystem())->remove([$class->getPhpClassFile(), $class->getPhpListingClassFile()]);
        RuntimeCache::clear();

        $this->assertNull(ClassDefinition::getByName(self::CLASS_NAME));
    }

    private function getClassDefinitionManager(): ClassDefinitionManager
    {
        return Pimcore::getContainer()->get(ClassDefinitionManager::class);
    }
}
