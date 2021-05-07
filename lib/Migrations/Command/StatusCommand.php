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

namespace Pimcore\Migrations\Command;

use Doctrine\Bundle\MigrationsBundle\Command\DoctrineCommand;
use Doctrine\Bundle\MigrationsBundle\Command\Helper\DoctrineCommandHelper;
use Doctrine\Bundle\MigrationsBundle\Command\MigrationsStatusDoctrineCommand;
use Pimcore\Migrations\Command\Traits\PimcoreMigrationsConfiguration;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @deprecated will be removed in Pimcore 10, please use Doctrine Migrations commands directly
 */
class StatusCommand extends MigrationsStatusDoctrineCommand
{
    use PimcoreMigrationsConfiguration;

    protected function configure()
    {
        parent::configure();

        $this->configureCommand('status');

        $this->addOption(
            'only',
            'o',
            InputOption::VALUE_OPTIONAL,
            'Retrieve only a specific value, possible values: current_version, next_version,
            number_new_migrations, number_available_migrations, number_executed_migrations, prev_version, latest_version'
        );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('only')) {
            DoctrineCommandHelper::setApplicationHelper($this->getApplication(), $input);
            $configuration = $this->getMigrationConfiguration($input, $output);
            DoctrineCommand::configureMigrations($this->getApplication()->getKernel()->getContainer(), $configuration);

            $configuration = $this->getMigrationConfiguration($input, $output);
            if ($input->getOption('only') == 'current_version') {
                $output->write($configuration->getCurrentVersion());
            } elseif ($input->getOption('only') == 'next_version') {
                $output->write($configuration->getNextVersion());
            } elseif ($input->getOption('only') == 'number_new_migrations') {
                $output->write($configuration->getNumberOfNewMigrations());
            } elseif ($input->getOption('only') == 'number_available_migrations') {
                $output->write($configuration->getNumberOfAvailableMigrations());
            } elseif ($input->getOption('only') == 'number_executed_migrations') {
                $output->write($configuration->getNumberOfExecutedMigrations());
            } elseif ($input->getOption('only') == 'prev_version') {
                $output->write($configuration->getPrevVersion());
            } elseif ($input->getOption('only') == 'latest_version') {
                $output->write($configuration->getLatestVersion());
            } else {
                throw new \InvalidArgumentException('Unsupported option `' . $input->getOption('only') . '` for option --only');
            }

            return 0;
        }

        return parent::execute($input, $output);
    }
}
