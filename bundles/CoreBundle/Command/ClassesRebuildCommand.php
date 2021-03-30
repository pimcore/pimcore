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
use Pimcore\Model\DataObject\ClassDefinition\ClassDefinitionManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ClassesRebuildCommand extends AbstractCommand
{
    /**
     * @var ClassDefinitionManager
     */
    protected $classDefinitionManager;

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
                'Delete missing Classes (Classes that don\'t exists in var/classes anymore but in the database)'
            );
    }

    /**
     * @param ClassDefinitionManager $classDefinitionManager
     * @required
     */
    public function setClassDefinitionManager(ClassDefinitionManager $classDefinitionManager)
    {
        $this->classDefinitionManager = $classDefinitionManager;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
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

                foreach ($this->classDefinitionManager->cleanUpDeletedClassDefinitions() as $deleted) {
                    if ($output->isVerbose()) {
                        [$class, $id, $action] = $deleted;
                        $output->writeln(sprintf('%s [%s] %s', $class, $id, $action));
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
            foreach ($this->classDefinitionManager->createOrUpdateClassDefinitions() as $changes) {
                if ($output->isVerbose()) {
                    [$class, $id, $action] = $changes;
                    $output->writeln(sprintf('%s [%s] %s', $class, $id, $action));
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

            try {
                $brickDefinition->save(false);
            } catch (\Exception $e) {
                $output->write((string)$e);
            }
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

            $fc->save(false);
        }

        return 0;
    }
}
