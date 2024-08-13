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

namespace Pimcore\Bundle\CoreBundle\Command;

use Pimcore\Cache;
use Pimcore\Console\AbstractCommand;
use Pimcore\DataObject\ClassBuilder\PHPClassDumperInterface;
use Pimcore\DataObject\ClassBuilder\PHPFieldCollectionClassDumperInterface;
use Pimcore\DataObject\ClassBuilder\PHPObjectBrickClassDumperInterface;
use Pimcore\DataObject\ClassBuilder\PHPObjectBrickContainerClassDumperInterface;
use Pimcore\DataObject\ClassBuilder\PHPSelectOptionsEnumDumperInterface;
use Pimcore\Model\DataObject;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:build:classes',
    description: 'rebuilds php files for classes, field collections and object bricks
    based on updated var/classes/definition_*.php files'
)]
class ClassesDefinitionsBuildCommand extends AbstractCommand
{
    public function __construct(
        protected PHPClassDumperInterface $classDumper,
        protected PHPFieldCollectionClassDumperInterface $collectionClassDumper,
        protected PHPObjectBrickClassDumperInterface $brickClassDumper,
        protected PHPObjectBrickContainerClassDumperInterface $brickContainerClassDumper,
        protected PHPSelectOptionsEnumDumperInterface $selectOptionsEnumDumper,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cacheStatus = Cache::isEnabled();
        Cache::disable();
        $objectClassesFolders = array_unique([PIMCORE_CLASS_DEFINITION_DIRECTORY, PIMCORE_CUSTOM_CONFIGURATION_CLASS_DEFINITION_DIRECTORY]);

        foreach ($objectClassesFolders as $objectClassesFolder) {
            $files = glob($objectClassesFolder.'/*.php');

            foreach ($files as $file) {
                $class = include $file;

                $this->classDumper->dumpPHPClasses($class);
            }
        }

        $list = new DataObject\Objectbrick\Definition\Listing();
        $list = $list->load();
        foreach ($list as $brickDefinition) {
            $this->brickClassDumper->dumpPHPClasses($brickDefinition);
            $this->brickContainerClassDumper->dumpContainerClasses($brickDefinition);
        }

        $list = new DataObject\Fieldcollection\Definition\Listing();
        $list = $list->load();
        foreach ($list as $fcDefinition) {
            $this->collectionClassDumper->dumpPHPClass($fcDefinition);
        }

        $selectOptionConfigurations = new DataObject\SelectOptions\Config\Listing();
        foreach ($selectOptionConfigurations as $selectOptionConfiguration) {
            $this->selectOptionsEnumDumper->dumpPHPEnum($selectOptionConfiguration);
        }

        if ($cacheStatus) {
            Cache::enable();
        }

        return 0;
    }
}
