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

use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\AbstractBatchProcessingWorker;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ResetQueueCommand extends AbstractIndexServiceCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('ecommerce:indexservice:reset-queue')
            ->setDescription('Resets the preparation or index-update queue (ONLY NEEDED if store table is out of sync)')
            ->addArgument('queue', InputArgument::REQUIRED, 'Queue to reset (preparation|update-index)')
            ->addOption('tenant', null, InputOption::VALUE_REQUIRED, 'Tenant to perform action on. "*" means all tenants.');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!($tenant = $input->getOption('tenant'))) {
            throw new \Exception('No tenant given');
        }

        $queue = $input->getArgument('queue');
        if (!in_array($queue, ['preparation', 'update-index'])) {
            throw new \Exception("Invalid queue {$queue}");
        }

        $updater = Factory::getInstance()->getIndexService();

        if ($tenant == '*') {
            $tenants = $updater->getTenants();
        } else {
            $tenants = [$tenant];
        }

        foreach ($tenants as $tenant) {

            /** @var AbstractBatchProcessingWorker $worker */
            $worker = $updater->getTenantWorker($tenant);

            $output->writeln("<info>Process tenant {$tenant}...</info>");

            if (!$worker instanceof AbstractBatchProcessingWorker) {
                throw new \Exception('Tenant is not of type AbstractBatchProcessingWorker');
            }

            if ($queue == 'preparation') {
                $worker->resetPreparationQueue();
            } elseif ($queue == 'update-index') {
                $worker->resetIndexingQueue();
            }
        }

        return 0;
    }
}
