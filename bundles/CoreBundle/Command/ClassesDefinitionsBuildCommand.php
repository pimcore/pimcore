<?php

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

use Pimcore\Console\AbstractCommand;
use Pimcore\DataObject\ClassBuilder\PHPClassDumperInterface;
use Pimcore\DataObject\ClassBuilder\PHPFieldCollectionClassDumperInterface;
use Pimcore\DataObject\ClassBuilder\PHPObjectBrickClassDumperInterface;
use Pimcore\DataObject\ClassBuilder\PHPObjectBrickContainerClassDumperInterface;
use Pimcore\Model\DataObject;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
class ClassesDefinitionsBuildCommand extends AbstractCommand
{
    public function __construct(
        protected PHPClassDumperInterface $classDumper,
        protected PHPFieldCollectionClassDumperInterface $collectionClassDumper,
        protected PHPObjectBrickClassDumperInterface $brickClassDumper,
        protected PHPObjectBrickContainerClassDumperInterface $brickContainerClassDumper,
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('pimcore:build:classes')
            ->setDescription(
                'rebuilds php files for classes, field collections and object bricks based on updated var/classes/definition_*.php files'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $objectClassesFolder = PIMCORE_CLASS_DEFINITION_DIRECTORY;
        $files = glob($objectClassesFolder.'/*.php');

        $changes = [];

        foreach ($files as $file) {
            $class = include $file;

            $this->classDumper->dumpPHPClasses($class);
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

        return 0;
    }
}
