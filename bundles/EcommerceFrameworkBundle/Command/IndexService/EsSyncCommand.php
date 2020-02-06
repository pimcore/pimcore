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
use Pimcore\Maintenance\ExecutorInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Psr\Log\LoggerInterface;

class EsSyncCommand extends AbstractIndexServiceCommand
{

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('ecommerce:indexservice:elasticsearch-sync')
            ->setDescription(
                'Reindexes elastic search indices based on the ES native reindexing API.'.
                    'This is particulary useful when index mappings change, or the synonym lexica has been updated.'
            )
            ->addOption('tenant', null, InputOption::VALUE_OPTIONAL,
                'If a tenant is provided, then a testindex will be built for it. Example: AT_de_elastic'
            )
            ->addOption('only-copy-synonyms', null, InputOption::VALUE_OPTIONAL,
                'Skip rebuilding index, just copy synonym files.'
            )
            ->addOption('force-reindexing', null, InputOption::VALUE_OPTIONAL,
                'If "true", then index rebuilt will be forced.'
            )
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $tenantName = $input->getOption('tenant');
        $onlyCopy = filter_var($input->getOption("only-copy-synonyms"), FILTER_VALIDATE_BOOLEAN);
        $forceReindexing = filter_var($input->getOption("force-reindexing"), FILTER_VALIDATE_BOOLEAN);
    }

    private function reindex(string $tenantName = null, bool $forceReindexing)
    {

        $indexService = Factory::getInstance()->getIndexService();
        $tenantList = $this->tenantName ? [$this->tenantName] : $indexService->getTenants();

        foreach ($tenantList as $tenantName) {

            $elasticWorker = $indexService->getTenantWorker($tenantName); //e.g., 'AT_de_elastic'

            if (!$elasticWorker instanceof AbstractElasticSearch) {
                $this->getLogger()->info("Skipping tenant [{$tenantName}] as it's not an elasticsearch tenant.");
                continue;
            }


            /*
            $tenantNameWithoutElastic = str_replace('_elastic', '', $tenantName);
            $syonynmFileName = sprintf('/System/Webshop/Search/synonyms_%s.txt', $tenantNameWithoutElastic);

            $synonymAsset = \Pimcore\Model\Asset::getByPath($syonynmFileName);
            if (!$synonymAsset) {
                throw new ErrorException(sprintf('Synonym file "%s" does not exist.', $syonynmFileName));
            }
            */


            if ($forceReindexing || $synonymAsset->getMetadata(self::ASSET_SYNCED_PROPERTY_NAME) === true) {

                //update syonyms by performaing a native index rebuild
                //will interrupt current queue processing.
                $elasticWorker->performNativeReindexing();

                $this->getLogger()->info(sprintf('Created and rebuild test index for tenant "%s".', $tenantName));

                //find asset and update state...
                AbstractListener::disableEventListeners([SynonymAssetListener::class]);

                $synonymAsset->setProperty(self::ASSET_SYNCED_PROPERTY_NAME, 'bool', true);
                $synonymAsset->save(['versionNote' => 'Saved in ' . self::class]);

            } else {
                $this->getLogger()->info(sprintf('Did not reindex for tenant "%s", as synonym file did not change.', $tenantName));
            }

        }
    }
}
