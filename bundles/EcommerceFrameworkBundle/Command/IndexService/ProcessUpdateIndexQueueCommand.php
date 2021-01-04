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

use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\IndexService;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\IndexUpdateService;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\ProductCentricBatchProcessingWorker;
use Pimcore\Console\Traits\Parallelization;
use Pimcore\Console\Traits\Timeout;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessUpdateIndexQueueCommand extends AbstractIndexServiceCommand
{
    use Timeout;
    use Parallelization
    {
        Parallelization::runBeforeFirstCommand as parentRunBeforeFirstCommand;
        Parallelization::runAfterBatch as parentRunAfterBatch;
    }

    /**
     * @var IndexUpdateService
     */
    protected $indexUpdateService;

    /**
     * @var IndexService
     */
    protected $indexService;

    /**
     * @var ProductCentricBatchProcessingWorker[] | null
     */
    protected $childWorkerList = null;

    /**
     * @param IndexUpdateService $indexUpdateService
     * @param IndexService $indexService
     * @param string|null $name
     */
    public function __construct(IndexUpdateService $indexUpdateService, IndexService $indexService, string $name = null)
    {
        parent::__construct($name);
        $this->indexUpdateService = $indexUpdateService;
        $this->indexService = $indexService;
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        parent::configure();

        self::configureParallelization($this);
        self::configureTimeout($this);

        $this
            ->setName('ecommerce:indexservice:process-update-queue')
            ->setDescription('Processes the ecommerce queue / store table and updates the (search) index.')
            ->addOption('tenant', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Tenant to perform action on (defaults to all)')
        ;
    }

    /**
     * @inheritDoc
     */
    protected function runBeforeFirstCommand(InputInterface $input, OutputInterface $output): void
    {
        $this->parentRunBeforeFirstCommand($input, $output);
        $this->initTimeout($input);
    }

    /**
     * @inheritDoc
     */
    protected function fetchItems(InputInterface $input): array
    {
        $tenantNameFilterList = $input->getOption('tenant');
        $combinedRows = $this->indexUpdateService->fetchProductIdsForIndexUpdate($tenantNameFilterList);
        $rowsWithSerializedItems = array_map(function ($row) {
            return serialize($row);
        }, $combinedRows);

        return $rowsWithSerializedItems;
    }

    /**
     * @inheritDoc
     */
    protected function runSingleCommand(string $serializedRow, InputInterface $input, OutputInterface $output): void
    {
        $row = unserialize($serializedRow);
        $id = $row['id'];
        $openTenants = $row['tenants'];

        if ($output->isVeryVerbose()) {
            $output->writeln(sprintf('Process ID="%s" for %d tenants (%s).', $id, count($openTenants), implode(',', $row['tenants'])));
        }

        $workerList = $this->getTenantWorkers($openTenants);
        foreach ($workerList as $worker) {
            //if the data object remains the same, it will be loaded from cache
            $worker->updateItemInIndex($id);
        }
    }

    /**
     * @inheritDoc
     */
    protected function runAfterBatch(InputInterface $input, OutputInterface $output, array $items): void
    {
        if ($this->childWorkerList) {
            foreach ($this->childWorkerList as $worker) {
                if ($output->isVerbose()) {
                    $output->writeln('<info>Commit index update for worker '.get_class($worker).'.</info>');
                }
                $worker->commitBatchToIndex();
            }
        }

        $this->parentRunAfterBatch($input, $output, $items);
        $this->handleTimeout(function (string $abortMessage) use ($output) {
            $output->writeln($abortMessage);
            exit(0); //exit with success
        });
    }

    /**
     * @param string[] $openTenantList a list of tenants for which the workers should be retrieved
     *
     * @return ProductCentricBatchProcessingWorker[]
     */
    protected function getTenantWorkers(array $openTenantList): array
    {
        $workerList = [];

        $tenants = $this->indexService->getTenants();
        foreach ($tenants as $tenant) {
            if (in_array($tenant, $openTenantList)) {
                $worker = $this->indexService->getTenantWorker($tenant);
                if ($worker instanceof ProductCentricBatchProcessingWorker) {
                    $workerList[] = $worker;
                }
            }
        }

        // collect all workers from all processed tenants
        $this->childWorkerList = $this->childWorkerList ?? [];
        $childWorkerList = [];
        foreach (array_merge($this->childWorkerList, $workerList) as $worker) {
            $childWorkerList[$worker->getTenantConfig()->getTenantName()] = $worker;
        }
        $this->childWorkerList = array_values($childWorkerList);

        return $workerList;
    }

    /**
     * @inheritDoc
     */
    protected function getItemName(int $count): string
    {
        return 'combined product ID rows in store table index';
    }

    /**
     * @inheritDoc
     */
    protected function getSegmentSize(): int
    {
        return 500; // index updates per child process
    }
}
