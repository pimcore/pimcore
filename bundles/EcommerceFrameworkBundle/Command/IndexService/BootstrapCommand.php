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
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\IndexService;
use Pimcore\Console\AbstractIndexServiceCommand;
use Pimcore\Console\Traits\Parallelization;
use Pimcore\Console\Traits\Timeout;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Listing;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ParallelProcessPreparationQueueCommand
 * @package Pimcore\Bundle\EcommerceFrameworkBundle\Command\IndexService
 */
class BootstrapCommand extends AbstractIndexServiceCommand
{
    use Parallelization;
    use LockableTrait;
    use Timeout;

    use Parallelization
    {
        Parallelization::runBeforeFirstCommand as parentRunBeforeFirstCommand;
        Parallelization::runAfterBatch as parentRunAfterBatch;
    }

    protected function configure()
    {
        parent::configure();
        self::configureParallelization($this);
        self::configureTimeout($this);
        $this
            ->setName('ecommerce:indexservice:bootstrap')
            ->setDescription('Bootstrap tasks creating/updating index (for all tenants), use one of the options --create-or-update-index-structure or --update-index')
            ->addOption('create-or-update-index-structure', null, InputOption::VALUE_NONE, 'Use to create or update the index structure')
            ->addOption('update-index', null, InputOption::VALUE_NONE, 'Use to rebuild the index data')
            ->addOption('object-list-class', null, InputOption::VALUE_REQUIRED, 'The object list class to use', '\\Pimcore\\Model\\DataObject\\Product\\Listing')
            ->addOption('list-condition', null, InputOption::VALUE_OPTIONAL, 'An optional condition for object list', '')
            ->addOption('tenant', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Tenant to perform action on (defaults to all)')
        ;
    }

    protected function runBeforeFirstCommand(InputInterface $input, OutputInterface $output): void
    {
        $this->parentRunBeforeFirstCommand($input, $output);
        $this->initTimeout($input);
    }


    protected function fetchItems(InputInterface $input): array
    {
        $updateIndex = $input->getOption('update-index');
        $createOrUpdateIndexStructure = $input->getOption('create-or-update-index-structure');

        $indexService = $this->initIndexService($input);

        if (!$createOrUpdateIndexStructure && !$updateIndex) {
            throw new \Exception('At least one option (--create-or-update-index-structure or --update-index) needs to be given');
        }

        if ($createOrUpdateIndexStructure) {
            $indexService->createOrUpdateIndexStructures();
        }


        $fullIdList = [];
        if ($updateIndex) {

            $objectListClass = $input->getOption('object-list-class');
            $listCondition = $input->getOption('list-condition');

            /** @var Listing $products */
            $products = new $objectListClass();
            $products->setUnpublished(true);
            $products->setObjectTypes(['object', 'folder', 'variant']);
            $products->setIgnoreLocalizedFields(true);
            $products->setCondition($listCondition);

            $fullIdList = $products->loadIdList();
        }

        return $fullIdList;
    }


    protected function runSingleCommand(string $productId, InputInterface $input, OutputInterface $output): void
    {
        $productId = (int)$productId;

        $indexService = $this->initIndexService($input);

        if ($output->isVerbose()) {
            $activeTenantNameList =$indexService->getTenants();
            $output->writeln(sprintf('Process product ID="%d" for %d tenants (%s).', $productId,
                    count($activeTenantNameList), implode(",",$activeTenantNameList))
            );
        }

        if ($object = AbstractObject::getById($productId)) {
            $indexService->updateIndex($object);
        } else {
            $output->writeln('<error>'.$this->getItemName(1).' object not existing anymore.</error>');
        }
    }

    protected function runAfterBatch(InputInterface $input, OutputInterface $output, array $items): void
    {
        $this->parentRunAfterBatch($input);
        $this->handleTimeout(function(string $abortMessage) use ($output) {
            $output->writeln($abortMessage);
            exit(0); //exit with success
        });
    }

    private function initIndexService(InputInterface $input) : IndexService {
        $indexService = Factory::getInstance()->getIndexService();

        //set active tenant workers.
        $tenants = count($input->getOption('tenant')) ? $input->getOption('tenant') : null;
        if (!empty($tenants)) {
            $tenantWorkerList = [];
            foreach ($tenants as $tenantName) {
                $tenantWorkerList[] = $indexService->getTenantWorker($tenantName);
            }
            $indexService->setTenantWorkers($tenantWorkerList);
        }
        return $indexService;
    }


    protected function getItemName(int $count): string
    {
        return $count <= 1 ? 'Product' : 'Products';
    }

    protected function getSegmentSize(): int
    {
        return 50; // products per child process
    }

}
