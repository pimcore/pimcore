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
            ->addOption('items-per-round', null, InputOption::VALUE_REQUIRED, 'Items per round to process', 200);
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

        if (!$processPreparationQueue && !$processUpdateIndexQueue) {
            throw new \Exception('No queue to process');
        }

        if ($processPreparationQueue) {
            IndexUpdater::processPreparationQueue($tenants, $input->getOption('max-rounds'), self::LOGGER_NAME, $input->getOption('items-per-round'));
        }

        if ($processUpdateIndexQueue) {
            IndexUpdater::processUpdateIndexQueue($tenants, $input->getOption('max-rounds'), self::LOGGER_NAME, $input->getOption('items-per-round'));
        }
    }
}
