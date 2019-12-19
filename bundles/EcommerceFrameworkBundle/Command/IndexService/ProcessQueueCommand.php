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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Command\IndexService;

use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Tool\IndexUpdater;
use Pimcore\Model\Tool\Lock;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessQueueCommand extends AbstractIndexServiceCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('ecommerce:indexservice:process-queue')
            ->setDescription('Processes the preparation and/or update-index queue')
            ->addArgument('queue', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'Queues to process (preparation|update-index)')
            ->addOption('tenant', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Tenant to perform action on (defaults to all)')
            ->addOption('max-rounds', null, InputOption::VALUE_REQUIRED, 'Maximum rounds to process', null)
            ->addOption('items-per-round', null, InputOption::VALUE_REQUIRED, 'Items per round to process', 200)
            ->addOption('unlock', null, InputOption::VALUE_NONE, 'Unlock a command that is currently locked.')
            ->addOption('ignore-lock', null, InputOption::VALUE_REQUIRED, 'Run a command and ignore lock.', 'true')
            ->addOption('lock-timeout', null, InputOption::VALUE_OPTIONAL, 'Timeout of command lock in minutes.', null)
            ->addOption('timeout', null, InputOption::VALUE_OPTIONAL, 'Max time for the command to run in minutes.');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $tenants = count($input->getOption('tenant')) ? $input->getOption('tenant') : null;

        $queues = $input->getArgument('queue');
        $processPreparationQueue = in_array('preparation', $queues);
        $processUpdateIndexQueue = in_array('update-index', $queues);
        $timeoutInSeconds = null;

        if ($timeoutInMinutes = (int)$input->getOption('timeout')) {
            $timeoutInSeconds = $timeoutInMinutes * 60;
        }

        if ($input->getOption('unlock')) {
            Lock::release($this->getLockName($input));
            $output->writeln(sprintf('<info>UNLOCKED "%s". Please start over again.</info>', $this->getLockname($input)));

            return;
        }

        $this->checkLock($input);

        if (!$processPreparationQueue && !$processUpdateIndexQueue) {
            throw new \Exception('No queue to process');
        }

        if ($processPreparationQueue) {
            IndexUpdater::processPreparationQueue($tenants, $input->getOption('max-rounds'), self::LOGGER_NAME, $input->getOption('items-per-round'), $timeoutInSeconds);
        }

        if ($processUpdateIndexQueue) {
            IndexUpdater::processUpdateIndexQueue($tenants, $input->getOption('max-rounds'), self::LOGGER_NAME, $input->getOption('items-per-round'), $timeoutInSeconds);
        }

        if (!filter_var($input->getOption('ignore-lock'), FILTER_VALIDATE_BOOLEAN)) {
            Lock::release($this->getLockname($input));
        }
    }

    /**
     * @param InputInterface $input
     *
     * @return string
     */
    protected function getLockname(InputInterface $input)
    {
        return $this->getName() . '_' . md5(implode('', [
                implode('', $input->getOption('tenant')),
                implode('', $input->getArgument('queue'))
            ]));
    }

    /**
     * @param InputInterface $input
     *
     * @throws \Exception
     */
    protected function checkLock(InputInterface $input)
    {
        $lockName = $this->getLockName($input);
        $ignoreLock = filter_var($input->getOption('ignore-lock'), FILTER_VALIDATE_BOOLEAN);
        $lockTimeoutInSeconds = null;
        if ($lockTimeoutInMinutes = (int)$input->getOption('lock-timeout')) {
            $lockTimeoutInSeconds = $lockTimeoutInMinutes * 60;
        }

        if (!$ignoreLock) {
            if (Lock::isLocked($lockName, $lockTimeoutInSeconds)) {
                throw new \Exception(sprintf('Could not lock command "%s" as another process is running.', $lockName));
            }
            Lock::lock($lockName);
        }
    }
}
