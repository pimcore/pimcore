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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Tool;

use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\InvalidConfigException;
use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\BatchProcessingWorkerInterface;
use Pimcore\Console\CliTrait;
use Pimcore\Log\Simple;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Listing\Concrete;

class IndexUpdater
{
    use CliTrait;

    /**
     * Runs update index for all tenants
     *  - but does not run processPreparationQueue or processUpdateIndexQueue
     *
     * @param $objectListClass
     * @param string $condition
     * @param bool $updateIndexStructures
     * @param string $loggername
     */
    public static function updateIndex($objectListClass, $condition = '', $updateIndexStructures = false, $loggername = 'indexupdater')
    {
        $updater = Factory::getInstance()->getIndexService();
        if ($updateIndexStructures) {
            $updater->createOrUpdateIndexStructures();
        }

        // Check if this was triggered in cli. If so do some preparation to properly work.
        // TODO Pimcore 7 - check if this is necessary when having monolog logging
        if (self::isCli() && session_status() == PHP_SESSION_NONE) {
            // Start a session to ensure that code relying on sessions keep working despite running on cli. One example is
            // \Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\PricingManager which uses the session to store its
            // pricing environment.
            /** @var \Symfony\Component\HttpFoundation\Session\SessionInterface $session */
            $session = \Pimcore::getKernel()->getContainer()->get('session');
            $session->start();
        }

        $page = 0;
        $pageSize = 100;
        $count = $pageSize;

        /** @var Concrete $products */
        $products = new $objectListClass();
        $products->setUnpublished(true);
        $products->setObjectTypes([AbstractObject::OBJECT_TYPE_OBJECT, AbstractObject::OBJECT_TYPE_VARIANT]);
        $products->setIgnoreLocalizedFields(true);
        $products->setCondition($condition);

        $totalCount = $products->getTotalCount();
        $totalPages = ceil($totalCount / $pageSize);

        while ($count > 0) {
            $products->setOffset($page * $pageSize);
            $products->setLimit($pageSize);
            $products->load();

            self::log($loggername, '=========================');
            self::log($loggername, sprintf('Update Index Page: %d (%d/%d - %.2f %%)', $page, $page, $totalPages, ($page / $totalPages * 100)));
            self::log($loggername, '=========================');

            foreach ($products as $p) {
                self::log($loggername, 'Updating product ' . $p->getId());
                $updater->updateIndex($p);
            }
            $page++;

            $count = $products->getCount();

            \Pimcore::collectGarbage();
        }
    }

    /**
     * Runs processPreparationQueue for given tenants or for all tenants
     *
     * @param array $tenants
     * @param int $maxRounds - max rounds after process returns. null for infinite run until no work is left
     * @param string $loggername
     * @param int $preparationItemsPerRound - number of items to prepare per round
     *
     * @throws InvalidConfigException
     */
    public static function processPreparationQueue($tenants = null, $maxRounds = null, $loggername = 'indexupdater', $preparationItemsPerRound = 200)
    {
        if ($tenants == null) {
            $tenants = Factory::getInstance()->getAllTenants();
        }

        if (!is_array($tenants)) {
            $tenants = [$tenants];
        }

        foreach ($tenants as $tenant) {
            self::log($loggername, '=========================');
            self::log($loggername, 'Processing preparation queue for tenant: ' . $tenant);
            self::log($loggername, '=========================');

            $env = Factory::getInstance()->getEnvironment();
            $env->setCurrentAssortmentTenant($tenant);

            $indexService = Factory::getInstance()->getIndexService();
            $worker = $indexService->getCurrentTenantWorker();

            if ($worker instanceof BatchProcessingWorkerInterface) {
                $round = 0;
                $result = true;
                while ($result) {
                    $round++;
                    self::log($loggername, 'Starting round: ' . $round);

                    $result = $worker->processPreparationQueue($preparationItemsPerRound);
                    self::log($loggername, 'processed preparation queue elements: ' . $result);

                    \Pimcore::collectGarbage();

                    if ($maxRounds && $maxRounds == $round) {
                        self::log($loggername, "skipping process after $round rounds.");

                        return;
                    }
                }
            }
        }
    }

    /**
     * Runs processUpdateIndexQueue for given tenants or for all tenants
     *
     * @param null $tenants
     * @param int $maxRounds - max rounds after process returns. null for infinite run until no work is left
     * @param string $loggername
     * @param int $indexItemsPerRound - number of items to index per round
     *
     * @throws InvalidConfigException
     */
    public static function processUpdateIndexQueue($tenants = null, $maxRounds = null, $loggername = 'indexupdater', $indexItemsPerRound = 200)
    {
        if ($tenants == null) {
            $tenants = Factory::getInstance()->getAllTenants();
        }

        if (!is_array($tenants)) {
            $tenants = [$tenants];
        }

        foreach ($tenants as $tenant) {
            self::log($loggername, '=========================');
            self::log($loggername, 'Processing update index elements for tenant: ' . $tenant);
            self::log($loggername, '=========================');

            $env = Factory::getInstance()->getEnvironment();
            $env->setCurrentAssortmentTenant($tenant);

            $indexService = Factory::getInstance()->getIndexService();
            $worker = $indexService->getCurrentTenantWorker();

            if ($worker instanceof BatchProcessingWorkerInterface) {
                $result = true;
                $round = 0;
                while ($result) {
                    $round++;
                    self::log($loggername, 'Starting round: ' . $round);

                    $result = $worker->processUpdateIndexQueue($indexItemsPerRound);
                    self::log($loggername, 'processed update index elements: ' . $result);

                    \Pimcore::collectGarbage();

                    if ($maxRounds && $maxRounds == $round) {
                        self::log($loggername, "skipping process after $round rounds.");

                        return;
                    }
                }
            }
        }
    }

    private static function log($loggername, $message)
    {
        Simple::log($loggername, $message);
        echo $message . "\n";
    }
}
