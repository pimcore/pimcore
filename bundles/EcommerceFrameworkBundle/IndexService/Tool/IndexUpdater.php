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

/**
 * @deprecated - use commands instead
 * @TODO Pimcore 7 - remove this
 */
class IndexUpdater
{
    use CliTrait;

    /**
     * Checks if a session has to be started to accommodate the needs of the pricing system before sending output.
     *
     * Stop-Gap solution until later refactoring:
     *
     * @TODO Pimcore 7 - check if this is necessary when having monolog logging
     */
    private static function startSession()
    {
        // Only necessary if this instance runs in CLI and doesn't have a session yet.
        if (self::isCli() && session_status() == PHP_SESSION_NONE) {
            // Start a session to ensure that code relying on sessions keep working despite running on cli. One example is
            // \Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\PricingManager which uses the session to store its
            // pricing environment.
            /** @var \Symfony\Component\HttpFoundation\Session\SessionInterface $session */
            $session = \Pimcore::getKernel()->getContainer()->get('session');
            $session->start();
        }
    }

    /**
     * @deprecated will be removed in Pimcore 7. Use ecommerce:indexservice:bootstrap command instead.
     *
     * Runs update index for all tenants
     *  - but does not run processPreparationQueue or processUpdateIndexQueue
     *
     * @param string $objectListClass
     * @param string $condition
     * @param bool $updateIndexStructures
     * @param string $loggername
     */
    public static function updateIndex($objectListClass, $condition = '', $updateIndexStructures = false, $loggername = 'indexupdater')
    {
        @trigger_error(
            'Method IndexUpdater::updateIndex is deprecated since version 6.7.0 and will be removed in 7.0.0. ' .
            'Use ecommerce:indexservice:bootstrap command instead.',
            E_USER_DEPRECATED
        );

        $updater = Factory::getInstance()->getIndexService();
        if ($updateIndexStructures) {
            $updater->createOrUpdateIndexStructures();
        }

        self::startSession();

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
                self::log($loggername, 'Updating ' . $p->getClass()->getName() . ': '.$p->getId());
                $updater->updateIndex($p);
            }
            $page++;

            $count = $products->getCount();

            \Pimcore::collectGarbage();
        }
    }

    /**
     * @deprecated will be removed in Pimcore 7. Use ecommerce:indexservice:process-preparation-queue command instead.
     *
     * @param array $tenants
     * @param int $maxRounds - max rounds after process returns. null for infinite run until no work is left
     * @param string $loggername
     * @param int $preparationItemsPerRound - number of items to prepare per round
     * @param int $timeout - timeout in seconds
     *
     * @throws InvalidConfigException
     * @throws \Exception
     */
    public static function processPreparationQueue($tenants = null, $maxRounds = null, $loggername = 'indexupdater', $preparationItemsPerRound = 200, $timeout = -1)
    {
        @trigger_error(
            'Method IndexUpdater::processPreparationQueue is deprecated since version 6.7.0 and will be removed in 7.0.0. ' .
            'Use ecommerce:indexservice:process-preparation-queue command instead.',
            E_USER_DEPRECATED
        );

        $startTime = microtime(true);

        if ($tenants == null) {
            $tenants = Factory::getInstance()->getAllTenants();
        }

        if (!is_array($tenants)) {
            $tenants = [$tenants];
        }

        self::startSession();

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
                    self::checkTimeout($timeout, $startTime);

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
     * @deprecated will be removed in Pimcore 7. Use ecommerce:indexservice:process-update-queue command instead.
     *
     * Runs processUpdateIndexQueue for given tenants or for all tenants
     *
     * @param array|null $tenants
     * @param int $maxRounds - max rounds after process returns. null for infinite run until no work is left
     * @param string $loggername
     * @param int $indexItemsPerRound - number of items to index per round
     * @param int $timeout - timeout in seconds
     *
     * @throws InvalidConfigException
     * @throws \Exception
     */
    public static function processUpdateIndexQueue($tenants = null, $maxRounds = null, $loggername = 'indexupdater', $indexItemsPerRound = 200, $timeout = -1)
    {
        @trigger_error(
            'Method IndexUpdater::processPreparationQueue is deprecated since version 6.7.0 and will be removed in 7.0.0. ' .
            'Use ecommerce:indexservice:process-update-queue command instead.',
            E_USER_DEPRECATED
        );

        $startTime = microtime(true);

        if ($tenants == null) {
            $tenants = Factory::getInstance()->getAllTenants();
        }

        if (!is_array($tenants)) {
            $tenants = [$tenants];
        }

        self::startSession();

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
                    self::checkTimeout($timeout, $startTime);

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

    /**
     * @param int $timeout
     * @param int $startTime
     *
     * @throws \Exception
     */
    private static function checkTimeout($timeout, $startTime): void
    {
        if ($timeout > 0) {
            $timeSinceStart = microtime(true) - $startTime;
            if ($timeout <= $timeSinceStart) {
                throw new \Exception(sprintf('Timeout "%d minutes" has been reached. Aborted.',
                    $timeout / 60
                ));
            }
        }
    }
}
