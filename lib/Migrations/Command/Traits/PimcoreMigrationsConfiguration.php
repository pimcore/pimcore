<?php

declare(strict_types=1);

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

namespace Pimcore\Migrations\Command\Traits;

use Doctrine\DBAL\Migrations\Configuration\Connection\Loader\ConnectionHelperLoader;
use Doctrine\DBAL\Migrations\OutputWriter;
use Doctrine\DBAL\Migrations\Tools\Console\Command\AbstractCommand;
use Pimcore\Db\Connection;
use Pimcore\Migrations\Configuration\Configuration;
use Pimcore\Migrations\Configuration\ConfigurationFactory;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @method Application getApplication()
 */
trait PimcoreMigrationsConfiguration
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var Configuration
     */
    protected $migrationConfiguration;

    /**
     * @var OutputWriter
     */
    protected $outputWriter;

    protected function configureCommand(string $name)
    {
        /** @var $this AbstractCommand */
        $this
            ->setName(sprintf('pimcore:migrations:%s', $name))
            ->addOption(
                'bundle',
                'b',
                InputOption::VALUE_REQUIRED,
                sprintf(
                    'The bundle to migrate. If no bundle is set it will use the <comment>app</comment> set from <comment>%s</comment>.',
                    'app/Resources/migrations'
                )
            )
            ->addOption(
                'set',
                's',
                InputOption::VALUE_REQUIRED,
                sprintf(
                    'The migration set to use (will be overridden by bundle if <comment>--bundle</comment> option is set).' . PHP_EOL . 'Defaults to <comment>app</comment> which loads migrations from <comment>%s</comment>.',
                    'app/Resources/migrations'
                ),
                'app'
            );
    }

    protected function getMigrationConfiguration(InputInterface $input, OutputInterface $output): Configuration
    {
        /** @var $this AbstractCommand */
        if (!$this->migrationConfiguration) {
            $factory = $this->getApplication()->getKernel()->getContainer()->get(ConfigurationFactory::class);

            $bundle       = $this->getBundle($input);
            $connection   = $this->getConnection($input);
            $outputWriter = $this->getOutputWriter($output);

            if ($bundle) {
                $this->migrationConfiguration = $factory->getForBundle($bundle, $connection, $outputWriter);
            } else {
                $this->migrationConfiguration = $factory->getForSet($input->getOption('set'), $connection, $outputWriter);
            }

            // the migration configuration might use another connection than the one resolved in getConnection
            // e.g. when the migration set defines a dedicate connection
            if ($this->migrationConfiguration->getConnection() !== $this->connection) {
                $this->connection = $this->migrationConfiguration->getConnection();
            }
        }

        return $this->migrationConfiguration;
    }

    protected function getBundle(InputInterface $input)
    {
        $bundleName = $input->getOption('bundle');

        if (!empty($bundleName)) {
            return $this->getApplication()->getKernel()->getBundle($bundleName);
        }
    }

    protected function getConnection(InputInterface $input): Connection
    {
        if ($this->connection) {
            return $this->connection;
        }

        $loader = new ConnectionHelperLoader($this->getHelperSet(), 'connection');

        /** @var Connection $connection */
        $connection = $loader->chosen();
        if ($connection) {
            return $this->connection = $connection;
        }

        throw new \InvalidArgumentException(
            'You have to specify a --db-configuration file or pass a Database Connection as a dependency to the Migrations.'
        );
    }

    /**
     * @param OutputInterface $output
     *
     * @return OutputWriter
     */
    protected function getOutputWriter(OutputInterface $output)
    {
        if (!$this->outputWriter) {
            $this->outputWriter = new OutputWriter(function ($message) use ($output) {
                return $output->writeln($message);
            });
        }

        return $this->outputWriter;
    }
}
