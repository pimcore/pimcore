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

use Doctrine\DBAL\Connection;
use Pimcore\Console\AbstractCommand;
use Pimcore\DataObject\ClassDefinition\Dbal\SchemaBuilderInterface;
use Pimcore\Model\DataObject\ClassDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateClassDefinitionSchemaCommand extends AbstractCommand
{
    private $schemaBuilder;
    private $connection;

    public function __construct(
        SchemaBuilderInterface $schemaBuilder,
        Connection $connection
    )
    {
        parent::__construct();

        $this->schemaBuilder = $schemaBuilder;
        $this->connection = $connection;
    }


    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pimcore:deployment:class-definition:schema:update')
            ->setDescription('Updates the DB Schema')
            ->addOption(
                'dump-sql',
                null,
                InputOption::VALUE_OPTIONAL,
                'Dump Generated SQL'
            )->addOption(
                'force',
                null,
                InputOption::VALUE_OPTIONAL,
                'Excutes Diff directly to Database'
            )
            ->addOption(
                'classes',
                'c',
                InputOption::VALUE_OPTIONAL,
                'Classes to Rebuild'
            )
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $classDefinitions = new ClassDefinition\Listing();

        if ($input->getOption('classes')) {
            $classDefinitions->setCondition('name IN (?)', explode(',', $input->getOption('classes')));
        }

        $classDefinitions->load();

        $diffs = [];

        /**
         * @var ClassDefinition $classDefinition
         */
        foreach ($classDefinitions->getClasses() as $classDefinition) {
            $diff = $this->schemaBuilder->getMigrateSchema($classDefinition);

            $output->writeln(sprintf('Schema Diff for %s', $classDefinition->getName()));
            $output->writeln($diff);
            $output->writeln('');

            $diffs[] = $diff;
        }

        if ($input->getOption('force')) {
            foreach ($diffs as $diff) {
                $this->connection->executeQuery($diff);
            }

            $output->writeln(sprintf('<success>Executed %s diffs</success>', count($diffs)));
        }

        return 0;
    }
}
