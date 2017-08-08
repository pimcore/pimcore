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

namespace Pimcore\Extension\Bundle\Migrations\Command\Traits;

use Doctrine\DBAL\Migrations\Configuration\Connection\Loader\ConnectionHelperLoader;
use Doctrine\DBAL\Migrations\OutputWriter;
use Doctrine\DBAL\Migrations\Tools\Console\Command\AbstractCommand;
use Pimcore\Db\Connection;
use Pimcore\Extension\Bundle\Migrations\Configuration\Configuration;
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
                'bundle', 'b',
                InputOption::VALUE_REQUIRED,
                sprintf(
                    'The bundle to migrate. If no bundle is set it will handle app migrations from <comment>%s</comment>',
                    'app/Resources/migrations'
                )
            );
    }

    protected function getMigrationConfiguration(InputInterface $input, OutputInterface $output): Configuration
    {
        /** @var $this AbstractCommand */
        if (!$this->migrationConfiguration) {
            $bundle = $this->getBundle($input);

            $migrationSet = '_app';
            if ($bundle) {
                $migrationSet = $bundle->getName();
            }

            $configuration = new Configuration(
                $migrationSet,
                $this->getConnection($input),
                $this->getOutputWriter($output)
            );

            if ($bundle) {
                $configuration->setName($bundle->getName() . ' Migrations');
                $configuration->setMigrationsNamespace($bundle->getNamespace() . '\\Migrations');
                $configuration->setMigrationsDirectory($bundle->getPath() . '/Resources/migrations');
            } else {
                $kernel = $this->getApplication()->getKernel();

                $configuration->setName('Migrations');
                $configuration->setMigrationsNamespace('App\\Migrations');
                $configuration->setMigrationsDirectory($kernel->getRootDir() . '/Resources/migrations');
            }

            $this->migrationConfiguration = $configuration;
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
