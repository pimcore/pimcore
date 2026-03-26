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

use Pimcore\Console\AbstractCommand;
use Pimcore\Maintenance\ExecutorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:maintenance',
    description: 'Asynchronous maintenance jobs of pimcore (needs to be set up as cron job)',
    aliases: ['maintenance']
)]
class MaintenanceCommand extends AbstractCommand
{
    public function __construct(private ExecutorInterface $maintenanceExecutor, private LoggerInterface $logger)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $description = 'Asynchronous maintenance jobs of pimcore (needs to be set up as cron job)';

        $help = $description.'. Valid jobs are: '."\n\n";
        $help .= '  <comment>*</comment> any bundle class name handling maintenance (e.g. <comment>PimcoreEcommerceFrameworkBundle</comment>)'."\n";

        foreach ($this->maintenanceExecutor->getTaskNames() as $taskName) {
            $help .= '  <comment>*</comment> '.$taskName."\n";
        }

        $this
            ->setHelp($help)
            ->addOption(
                'job',
                'j',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Call just a specific job(s) (see <comment>--help</comment> for a list of valid jobs)'
            )
            ->addOption(
                'excludedJobs',
                'J',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Exclude specific job(s) (see <comment>--help</comment> for a list of valid jobs)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $validJobs = $this->getArrayOptionValue($input, 'job');
        $excludedJobs = $this->getArrayOptionValue($input, 'excludedJobs');

        $this->maintenanceExecutor->executeMaintenance(
            $validJobs,
            $excludedJobs
        );

        $this->logger->info('All maintenance-jobs finished!');

        return 0;
    }

    /**
     * Get an array option value, but still support the value being comma-separated for backwards compatibility
     */
    private function getArrayOptionValue(InputInterface $input, string $name): array
    {
        $value = $input->getOption($name);
        $result = [];

        if (!empty($value)) {
            foreach ($value as $val) {
                foreach (explode(',', $val) as $part) {
                    $part = trim($part);
                    if (!empty($part)) {
                        $result[] = $part;
                    }
                }
            }
        }

        $result = array_unique($result);

        return $result;
    }
}
