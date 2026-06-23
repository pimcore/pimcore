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
use Pimcore\Tool\Admin;
use Pimcore\Tool\MaintenanceModeHelperInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:maintenance-mode',
    description: 'Enable or disable maintenance mode'
)]
class MaintenanceModeCommand extends AbstractCommand
{
    public function __construct(protected MaintenanceModeHelperInterface $maintenanceModeHelper)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('enable', null, InputOption::VALUE_NONE, 'Enable maintenance mode (default)')
            ->addOption('disable', null, InputOption::VALUE_NONE, 'Disable maintenance mode')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $input->setOption('ignore-maintenance-mode', true);
        parent::initialize($input, $output);
    }

    /**
     *
     *
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        //Default behavior is 'enable'
        $disable = ($input->getOption('disable') ?? false);

        if ($disable) {
            //BC Layer for Admin::activateMaintenanceMode, if the maintenance file already exists
            if (Admin::isInMaintenanceMode()) {
                Admin::deactivateMaintenanceMode();
            }
            $this->maintenanceModeHelper->deactivate();
            if ($output->isVerbose()) {
                $output->writeln('Maintenance mode has been disabled');
            }
        } else {
            $this->maintenanceModeHelper->activate('command-line-dummy-session-id');
            if ($output->isVerbose()) {
                $output->writeln('Maintenance mode is now enabled');
                $output->writeln('You can run commands only with the --ignore-maintenance-mode option');
            }
        }

        return 0;
    }
}
