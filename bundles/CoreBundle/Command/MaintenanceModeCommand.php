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

use Exception;
use Pimcore\Console\AbstractCommand;
use Pimcore\Tool\Admin;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MaintenanceModeCommand extends AbstractCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            ->setName('pimcore:maintenance-mode')
            ->setDescription('Enable or disable maintenance mode')
            ->addOption('enable', null, InputOption::VALUE_NONE, 'Enable maintenance mode (default)')
            ->addOption('disable', null, InputOption::VALUE_NONE, 'Disable maintenance mode')
        ;
    }

    /**
     * @inheritdoc
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $input->setOption('ignore-maintenance-mode', true);
        parent::initialize($input, $output);
    }

    /**
     * @inheritDoc
     *
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //Default behavior is 'enable'
        $disable = ($input->getOption('disable') ?? false);

        if ($disable) {
            Admin::deactivateMaintenanceMode();
            if ($output->isVerbose()) {
                $output->writeln('Maintenance mode has been disabled');
            }
        } else {
            Admin::activateMaintenanceMode('command-line-dummy-session-id');
            if ($output->isVerbose()) {
                $output->writeln('Maintenance mode is now enabled');
                $output->writeln('You can run commands only with the --ignore-maintenance-mode option');
            }
        }

        return 0;
    }
}
