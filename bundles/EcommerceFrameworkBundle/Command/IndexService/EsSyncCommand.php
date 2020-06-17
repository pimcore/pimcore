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
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\ElasticSearch\AbstractElasticSearch;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class EsSyncCommand extends AbstractIndexServiceCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('ecommerce:indexservice:elasticsearch-sync')
            ->setDescription(
                'Refresh elastic search (ES) index settings, mappings via native ES-API.'
            )
            ->addArgument('mode', InputArgument::REQUIRED,
                'reindex: Reindexes ES indices based on the their native reindexing API. Might be necessary when mapping has changed.'.PHP_EOL.
                'update-synonyms: Activate changes in synonym files, by closing and reopening the ES index.'
            )
            ->addOption('tenant', null, InputOption::VALUE_OPTIONAL,
                'If a tenant name is provided (e.g. assortment_de), then only that specific tenant will be synced. '.
                'Otherwise all tenants will be synced.'
            )
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $mode = $input->getArgument('mode');
        $tenantName = $input->getOption('tenant');

        $indexService = Factory::getInstance()->getIndexService();
        $tenantList = $tenantName ? [$tenantName] : $indexService->getTenants();

        if (!in_array($mode, ['reindex', 'update-synonyms'])) {
            $output->writeln("<error>Unknown mode \"{$mode}\")...</error>");
            exit(1);
        }

        $bar = new ProgressBar($output, count($tenantList));

        foreach ($tenantList as $tenantName) {
            $elasticWorker = $indexService->getTenantWorker($tenantName); //e.g., 'AT_de_elastic'

            if (!$elasticWorker instanceof AbstractElasticSearch) {
                $output->writeln("<info>Skipping tenant \"{$tenantName}\" as it's not an elasticsearch tenant.</info>");
                continue;
            }

            $output->writeln("<info>Process tenant \"{$tenantName}\" (mode \"{$mode}\")...</info>");

            if ('reindex' == $mode) {
                $elasticWorker->startReindexMode();
            } elseif ('update-synonyms' == $mode) {
                $elasticWorker->updateSynonyms();
            }

            $bar->advance(1);
        }

        $bar->finish();

        return 0;
    }
}
