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

namespace Pimcore\Bundle\CoreBundle\Command;

use Pimcore\Console\AbstractCommand;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ClassesRebuildCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('pimcore:deployment:classes-rebuild')
            ->setAliases(['deployment:classes-rebuild'])
            ->setDescription('rebuilds db structure for classes, field collections and object bricks based on updated var/classes/definition_*.php files')
            ->addOption(
                'create-classes',
                'c',
                InputOption::VALUE_NONE,
                'Create missing Classes (Classes that exists in var/classes but not in the database)'
            )
            ->addOption(
                'delete-classes',
                'd',
                InputOption::VALUE_NONE,
                'Delete missing Classes (Classes that dont exists in var/classes anymore but in the database)'
            );
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $db = \Pimcore\Db::get();
        if ($input->getOption('delete-classes')) {
            $questionResult = true;

            if ($input->isInteractive()) {
                $questionResult = $this->io->confirm(
                    '<error>You are going to delete classes that don\'t have class-definitions anymore. This could lead to data loss! Do you want to continue?</error>',
                    false
                );
            }

            if ($questionResult) {
                if ($output->isVerbose()) {
                    $output->writeln('---------------------');
                    $output->writeln('Delete Classes that don\'t have class-definitions anymore.');
                }

                $classes = $db->fetchAll('SELECT * FROM classes');

                foreach ($classes as $class) {
                    $id = $class['id'];
                    $name = $class['name'];

                    $cls = new ClassDefinition();
                    $cls->setId((int)$id);
                    $definitionFile = $cls->getDefinitionFile($name);

                    if (!file_exists($definitionFile)) {
                        if ($output->isVerbose()) {
                            $output->writeln(sprintf('%s [%s] deleted', $name, $id));
                        }

                        //ClassDefinition doesn't exist anymore, therefore we delete it
                        $cls->delete();
                    }
                }
            }
        }

        $list = new ClassDefinition\Listing();
        $list->load();

        if ($output->isVerbose()) {
            $output->writeln('---------------------');
            $output->writeln('Saving all classes');
        }

        if ($input->getOption('create-classes')) {
            $objectClassesFolder = PIMCORE_CLASS_DIRECTORY;
            $files = glob($objectClassesFolder . '/*.php');

            foreach ($files as $file) {
                $class = include $file;

                if ($class instanceof ClassDefinition) {
                    $existingClass = ClassDefinition::getByName($class->getName());

                    if ($existingClass instanceof ClassDefinition) {
                        if ($output->isVerbose()) {
                            $output->writeln(sprintf('%s [%s] saved', $class->getName(), $class->getId()));
                        }

                        $existingClass->save(false);
                    } else {
                        if ($output->isVerbose()) {
                            $output->writeln(sprintf('%s [%s] created', $class->getName(), $class->getId()));
                        }

                        $class->save(false);
                    }
                }
            }
        } else {
            foreach ($list->getClasses() as $class) {
                if ($class instanceof ClassDefinition) {
                    if ($output->isVerbose()) {
                        $output->writeln(sprintf('%s [%s] created', $class->getName(), $class->getId()));
                    }

                    $class->save(false);
                }
            }
        }

        if ($output->isVerbose()) {
            $output->writeln('---------------------');
            $output->writeln('Saving all object bricks');
        }
        $list = new DataObject\Objectbrick\Definition\Listing();
        $list = $list->load();
        foreach ($list as $brickDefinition) {
            if ($output->isVerbose()) {
                $output->writeln(sprintf('%s saved', $brickDefinition->getKey()));
            }

            $brickDefinition->save();
        }

        if ($output->isVerbose()) {
            $output->writeln('---------------------');
            $output->writeln('Saving all field collections');
        }
        $list = new DataObject\Fieldcollection\Definition\Listing();
        $list = $list->load();
        foreach ($list as $fc) {
            if ($output->isVerbose()) {
                $output->writeln(sprintf('%s saved', $fc->getKey()));
            }

            $fc->save();
        }
    }
}
