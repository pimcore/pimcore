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
use Pimcore\Model\Asset;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

class EsSyncCommand extends AbstractIndexServiceCommand
{
    const DEFAULT_ES_SYNONYM_CONFIG_DIR = '/etc/elasticsearch/pimcore_configs/';

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('ecommerce:indexservice:elasticsearch-sync')
            ->setDescription(
                'Refresh elastic search (ES) index settings, mappings and synonyms based on native API.'
            )
            ->addArgument('mode', InputArgument::REQUIRED,
                    'reindex: Reindexes ES indices based on the their native reindexing API.'.
                        'This is particularly useful when index mappings change while development, 
                        or when synonym settings / mappings have been updated.'.PHP_EOL.

                    'refresh-synonyms: Activate changes in synonym files, by closing and reopening the ES index.'
            )
            ->addOption('tenant', null, InputOption::VALUE_OPTIONAL,
                'If a tenant name is provided (e.g. assortment_de), then only that specific tenant will be synced. '.
                'Otherwise all tenants will be synced.'
            )
            ->addOption('synonymAssetSourceFolder', null, InputOption::VALUE_OPTIONAL,
                'If set, then in mode "refresh-synonyms", all of the files that are part of that asset folder,
                will be copied to a dedicated ES configuration directory, from where the synonyms can be used within your search indices. '.PHP_EOL.
                'Example: /System/Synonyms (=asset folder)'.PHP_EOL.
                'See https://www.elastic.co/guide/en/elasticsearch/reference/current/analysis-synonym-tokenfilter.html.'
            )
            ->addOption('synonymTargetFolder', null, InputOption::VALUE_OPTIONAL,
                'Absolute server path. If set, then in mode "refresh-synonyms" the content of the (Pimcore) synonym files will be copied to '.
                'that directory, which must reside within the Elastic search installation.'.
                'Default: '.self::DEFAULT_ES_SYNONYM_CONFIG_DIR.'.'
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

        $bar = new ProgressBar($output, count($tenantList));

        foreach ($tenantList as $tenantName) {

            $elasticWorker = $indexService->getTenantWorker($tenantName); //e.g., 'AT_de_elastic'

            if (!$elasticWorker instanceof AbstractElasticSearch) {
                $output->writeln("<info>Skipping tenant \"{$tenantName}\" as it's not an elasticsearch tenant.</info>");
                continue;
            }

            $output->writeln("<info>Process tenant \"{$tenantName}\" (mode \"{$mode}\")...</info>");

            if ('reindex' == $mode) {
                //update syonyms by performaing a native index rebuild
                //will interrupt current queue processing.
                $elasticWorker->performNativeReindexing();

            } elseif ('refresh-synonyms' == $mode) {

                $synonymAssetSourceFolderPath = $input->getOption('synonymAssetSourceFolder');
                $synonymTargetFolderPath = $input->getOption('synonymTargetFolder') ? : self::DEFAULT_ES_SYNONYM_CONFIG_DIR;

                if ($synonymAssetSourceFolderPath) {

                    $synonymAssetSourceFolder  = Asset::getByPath($synonymAssetSourceFolderPath);
                    if (!($synonymAssetSourceFolder instanceof Asset\Folder)) {
                        throw new \Exception(
                            "Synonym assset source folder \"{$synonymAssetSourceFolderPath}\" does not exist or is not a folder."
                        );
                    }

                    if (!is_dir($synonymTargetFolderPath)) {
                        throw new \Exception(
                            "The synonym target folder does not exist: \"{$synonymTargetFolderPath}\""
                        );
                    }

                    if (!is_writable($synonymTargetFolderPath)) {
                        throw new \Exception(
                            "The synonym target folder \"{$synonymTargetFolderPath}\" is not writable."
                        );
                    }

                    foreach ($synonymAssetSourceFolder->getChildren() as $synonymFile) {
                        if ($synonymFile instanceof Asset\Folder) {
                            $output->writeln("<error>Skip folder \"{$synonymFile}\".</error>");
                        } else {
                            $sourcePath = PIMCORE_PUBLIC_VAR.'/assets'.$synonymFile->getFullPath();
                            $targetPath = $synonymTargetFolderPath.$synonymFile->getFullPath();
                            $output->writeln("<info>Copy \"{$sourcePath}\" to \"{$targetPath}\".</info>");
                            copy($sourcePath, $synonymTargetFolderPath);
                        }
                    }
                }

                $elasticWorker->refreshSynonymsInIndex();
            }

            $bar->advance(1);
        }

        $bar->finish();
        return 0;
    }
}
