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

    public function testDumpClassDefinitionsWithForceRestoresPhpClasses(): void
    {
        $class = $this->createTestClassDefinition();
        $phpClassFile = $class->getPhpClassFile();

        $this->simulateEmptyDatabase($class);

        $this->getClassDefinitionManager()->dumpClassDefinitions(dumpPHPClasses: false);
        $this->assertFileDoesNotExist($phpClassFile);

        // a subsequent run with PHP class dumping enabled needs force, as the definition did not change
        $this->getClassDefinitionManager()->dumpClassDefinitions(force: true);

        $this->assertFileExists($phpClassFile, 'PHP classes must be dumped again when $dumpPHPClasses is true');
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
        (new Filesystem())->remove($class->getPhpClassFile());
        RuntimeCache::clear();

        $this->assertNull(ClassDefinition::getByName(self::CLASS_NAME));
    }

    private function getClassDefinitionManager(): ClassDefinitionManager
    {
        return \Pimcore::getContainer()->get(ClassDefinitionManager::class);
    }
}
