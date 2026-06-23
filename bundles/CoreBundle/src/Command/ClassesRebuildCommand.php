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

namespace Pimcore\Bundle\CoreBundle\Command;

use Exception;
use Pimcore\Console\AbstractCommand;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\ClassDefinitionManager;
use Pimcore\Model\DataObject\Exception\DefinitionWriteException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:deployment:classes-rebuild',
    description: 'rebuilds db structure for classes, field collections and object bricks
    based on updated var/classes/definition_*.php files',
    aliases: ['deployment:classes-rebuild']
)]
class ClassesRebuildCommand extends AbstractCommand
{
    protected ClassDefinitionManager $classDefinitionManager;

    protected function configure(): void
    {
        $this
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
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force rebuild of all classes (ignoring the last modification date of the class definition files)'
            )
            ->addOption(
                'db-only',
                'o',
                InputOption::VALUE_NONE,
                'Applies only the changes to the database, but does not generate any PHP classes'
            );

    }

    #[Required]
    public function setClassDefinitionManager(ClassDefinitionManager $classDefinitionManager): void
    {
        $this->classDefinitionManager = $classDefinitionManager;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     * @throws DefinitionWriteException
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dumpPHPClasses = !$input->getOption('db-only');

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

        if ($output->isVerbose()) {
            $output->writeln('---------------------');
            $output->writeln('Saving all classes');
        }

        $force = (bool)$input->getOption('force');

        if ($input->getOption('create-classes')) {
            foreach ($this->classDefinitionManager->createOrUpdateClassDefinitions($force) as $changes) {
                if ($output->isVerbose()) {
                    [$class, $id, $action] = $changes;
                    $output->writeln(sprintf('%s [%s] %s', $class, $id, $action));
                }
            }
        } else {
            $list = new ClassDefinition\Listing();
            foreach ($list->getData() as $class) {
                if ($class instanceof DataObject\ClassDefinition) {
                    $classSaved = $this->classDefinitionManager->dumpClass(
                        $class,
                        false,
                        $dumpPHPClasses,
                        $force
                    );
                    if ($output->isVerbose()) {
                        $output->writeln(
                            sprintf(
                                '%s [%s] %s',
                                $class->getName(),
                                $class->getId(),
                                $classSaved ? ClassDefinitionManager::SAVED : ClassDefinitionManager::SKIPPED
                            )
                        );
                    }
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
                $brickDefinition->dump(false, $dumpPHPClasses);
            } catch (Exception $e) {
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

            $fc->dump(false, $dumpPHPClasses);
        }

        if ($output->isVerbose()) {
            $output->writeln('---------------------');
            $output->writeln('Saving all select options');
        }

        if ($dumpPHPClasses) {
            $selectOptionConfigurations = new DataObject\SelectOptions\Config\Listing();
            foreach ($selectOptionConfigurations as $selectOptionConfiguration) {
                if ($output->isVerbose()) {
                    $output->writeln(sprintf('%s saved', $selectOptionConfiguration->getId()));
                }

                $selectOptionConfiguration->generateEnumFiles();
            }
        } else {
            $output->writeln('<comment>Skipping select options generation, because --db-only option is set.</comment>');
        }

        return 0;
    }
}
