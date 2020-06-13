<?php

declare(strict_types=1);

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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\IndexService;

use Doctrine\DBAL\Query\QueryBuilder;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\ProductCentricBatchProcessingWorker;
use Pimcore\Db;
use Pimcore\Logger;

class IndexUpdateService
{
    /**
     * @var IndexService
     */
    protected $indexService;

    /**
     * @param IndexService $indexService
     */
    public function __construct(IndexService $indexService)
    {
        $this->indexService = $indexService;
    }

    /**
     * Fetch productIds and tenant information for all products that require product index preparation,
     * and for all tenants with store table support.
     *
     * @param array $tenantNameFilterList
     *      - an optional list of tenants that should be used for filtering.
     *      - by default, all tenants will be used.
     *
     * @return array
     *      - each item contains a single product.
     *      - each product ID can exist max. once.
     *      - each item contains the list of open tenants as another array
     */
    public function fetchProductIdsForPreparation(array $tenantNameFilterList = []): array
    {
        $storeTableList = $this->getStoreTableList($tenantNameFilterList);
        $combinedRows = [];
        foreach ($storeTableList as $storeTableName) {
            $rows = $this->fetchProductIdsForPreparationForSingleStoreTable($storeTableName, $tenantNameFilterList);
            $combinedRows = $this->combineRowsAndTenants($combinedRows, $rows);
        }

        return $combinedRows;
    }

    /**
     * Fetch productIds and tenant information for all products that require product index preparation,
     * and that are part of a specific store table.
     *
     * @param string $storeTableName the name of the store table.
     * @param array $tenantNameFilterList
     *      - an optional list of tenants that should be used for filtering.
     *      - by default, all tenants will be used.
     *
     * @return array
     *      - each item contains a single product.
     *      - each product ID can exist max. once.
     *      - each item contains the list of open tenants as another array
     */
    protected function fetchProductIdsForPreparationForSingleStoreTable(string $storeTableName, array $tenantNameFilterList): array
    {
        $qb = $this->createBasicStoreTableSelectQuery($storeTableName, $tenantNameFilterList);
        $qb->andWhere('in_preparation_queue = 1');
        $rows = $qb->execute()->fetchAll();

        $result = [];

        foreach ($rows as $row) {
            $tenantNameList = explode_and_trim(',', $row['tenants']);
            $row['tenants'] = $tenantNameList;
            $result[] = $row;
        }

        return $result;
    }

    /**
     * Fetch productIds and tenant information for all products that require an index update, and that support store tables.
     *
     * @param array $tenantNameFilterList
     *      - an optional list of tenants that should be used for filtering.
     *      - by default, all tenants will be used.
     *
     * @return array
     *      - each item contains a single product.
     *      - each product ID exists max. once
     *      - each item contains the list of open tenants as another array
     */
    public function fetchProductIdsForIndexUpdate(array $tenantNameFilterList = []): array
    {
        $storeTableList = $this->getStoreTableList($tenantNameFilterList);

        $combinedRows = [];
        foreach ($storeTableList as $storeTableName) {
            $rows = $this->fetchProductIdsIndexUpdateForSingleStoreTable($storeTableName, $tenantNameFilterList);
            $combinedRows = $this->combineRowsAndTenants($combinedRows, $rows);
        }

        return $combinedRows;
    }

    /**
     * Fetch productIds and tenant information for all products that require a product index update based on a specific store table.
     *
     * @param string $storeTableName the name of the store table.
     * @param array $tenantNameFilterList
     *      - an optional list of tenants that should be used for filtering.
     *      - by default, all tenants will be used.
     *
     * @return array
     *      - each item contains a single product.
     *      - each product ID can exist max. once.
     *      - each item contains the list of open tenants as another array
     */
    protected function fetchProductIdsIndexUpdateForSingleStoreTable(string $storeTableName, array $tenantNameFilterList): array
    {
        $qb = $this->createBasicStoreTableSelectQuery($storeTableName, $tenantNameFilterList);
        $qb->andWhere('crc_current != crc_index OR ISNULL(crc_index)');
        $rows = $qb->execute()->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $tenantNameList = explode_and_trim(',', $row['tenants']);
            $row['tenants'] = $tenantNameList;
            $result[] = $row;
        }

        return $result;
    }

    /**
     * Reset a set of IDs from anyhwhere, in order to rebuild the preparation queue.
     *
     * @param int[] $idList the ID list to update
     * @param string $triggerInfo optional text with info what triggered the update that will be saved in the store table.
     *        useful for diagnosis. Recommended to set it!
     * @param string[]|null $tenantNameList optional list of tenant names for which the update should happen. If null, then the parameter
     *        will be ignored. If the array is empty, then no update will take place.
     */
    public function resetIdsInPreparation(array $idList, string $triggerInfo, array $tenantNameList = null)
    {
        return $this->resetIds($idList, $triggerInfo, false, $tenantNameList);
    }

    /**
     * Reset a set of IDs from anyhwhere, in order to rebuild the (product) index.
     *
     * @param int[] $idList the ID list to update
     * @param string $triggerInfo optional text with info what triggered the update that will be saved in the store table.
     *        useful for diagnosis. Recommended to set it!
     * @param string[]|null $tenantNameList optional list of tenant names for which the update should happen. If null, then the parameter
     *        will be ignored. If the array is empty, then no update will take place.
     */
    public function resetIdsUpdateIndex(array $idList, string $triggerInfo, array $tenantNameList = null)
    {
        return $this->resetIds($idList, $triggerInfo, true, $tenantNameList);
    }

    /**
     * resets the store table by marking all items as "in preparation" or "update-index", so items in store will be regenerated
     *
     * @param int[] $idList
     * @param string $triggerInfo optional text with info what triggered the update that will be saved in the store table.
     *        useful for diagnosis.
     * @param bool $onlyResetUpdateIndex if set to true then only the update-index flag will be reset, but the preparation status
     *        won't be reset.
     * @param string[]|null $tenantNameList optional list of tenant names for which the update should happen. If null, then the parameter
     *        will be ignored. If the array is empty, then no update will take place.
     */
    protected function resetIds(array $idList, string $triggerInfo, bool $onlyResetUpdateIndex = false, array $tenantNameList = null)
    {
        if (count($idList) <= 0) {
            return;
        }

        if ($tenantNameList == null) {
            $tenantNameList = $this->indexService->getTenants();
        } elseif (count($tenantNameList) <= 0) {
            return;
        }

        $storeTableNames = $this->getStoreTableList($tenantNameList);

        foreach ($storeTableNames as $storeTableName) {
            $idChunks = array_chunk($idList, 20000);
            foreach ($idChunks as $idList) {
                $qb = $this->createBasicStoreTableUpdateQuery($storeTableName, $tenantNameList);

                if ($onlyResetUpdateIndex) {
                    $qb->set('crc_index', 0)
                        ->set('trigger_info', ':triggerInfo')
                    ;
                } else {
                    $qb
                        ->set('in_preparation_queue', (int)true)
                        ->set('preparation_error', 'null')
                        ->set('crc_current', 0)
                        ->set('crc_index', 0)
                        ->set('preparation_status', 0)
                        ->set('trigger_info', ':triggerInfo')
                    ;
                }

                $qb->setParameter('triggerInfo', $triggerInfo);
                $ids = implode(',', $idList);
                $qb->where(sprintf('o_id in (%s)', $ids));

                $qb->execute();
            }
        }
    }

    /**
     * Create a query builder with the common precondition for store table queries.
     *
     * @param string[] $tenantNameFilterList
     *      - an optional list of tenants that should be used for filtering.
     *      - by default, all tenants will be used.
     *
     * @return QueryBuilder
     */
    protected function createBasicStoreTableSelectQuery(string $storeTableName, array $tenantNameFilterList): QueryBuilder
    {
        /** @var QueryBuilder $qb */
        $qb = Db::get()->createQueryBuilder();
        $qb
            ->select('o_id as id')
            ->addSelect('group_concat(tenant) as tenants')
            ->addSelect('count(tenant) as numTenants')
            ->from($storeTableName, 'storeTable')
        ;

        if (!empty($tenantNameFilterList)) {
            $qb->andWhere(sprintf('tenant in(%s)', implode(',', array_map(function ($str) {
                return sprintf("'%s'", $str);
            },
                    $tenantNameFilterList))
            ));
        }

        $qb->groupBy('o_id')
            ->orderBy('numTenants', 'desc')
        ;

        return $qb;
    }

    /**
     * Create a query builder with the common precondition for store table queries.
     *
     * @param string[] $tenantNameFilterList
     *      - an optional list of tenants that should be used for filtering.
     *      - by default, all tenants will be used.
     *
     * @return QueryBuilder
     */
    protected function createBasicStoreTableUpdateQuery(string $storeTableName, array $tenantNameFilterList): QueryBuilder
    {
        /** @var QueryBuilder $qb */
        $qb = Db::get()->createQueryBuilder();
        $qb->update($storeTableName);

        if (!empty($tenantNameFilterList)) {
            $qb->andWhere(sprintf('tenant in(%s)', implode(',', array_map(function ($str) {
                return sprintf("'%s'", $str);
            },
                    $tenantNameFilterList))
            ));
        }

        return $qb;
    }

    /**
     * Get a list of store tables depending on a list of tenants.
     *
     * @param string[] $tenantNameFilterList
     *      - an optional list of tenants that should be used for filtering.
     *      - by default, all tenants will be used.
     *
     * @return string[] a list of store table names.
     */
    public function getStoreTableList(array $tenantNameFilterList = []): array
    {
        //get all store tables and join the results...
        $tenants = $this->indexService->getTenants();
        $storeTableList = [];
        foreach ($tenants as $tenantName) {
            if (!empty($tenantNameFilterList) && !in_array($tenantName, $tenantNameFilterList)) {
                continue;
            }

            $worker = $this->indexService->getTenantWorker($tenantName);
            if ($worker instanceof ProductCentricBatchProcessingWorker) {
                $storeTableName = $worker->getBatchProcessingStoreTableName();
                $storeTableList[$storeTableName] = true;
            }
        }

        return array_keys($storeTableList);
    }

    /**
     * Combine the results of a fetchProductIdsForPreparation call, or a alike and merge tenants
     * when the ID equals.
     *
     * @param array $combinedRows the combined rows.
     * @param array $rows the rows that have to be added, typically coming from the DB:
     *
     * @return array the merged combined rows.
     */
    protected function combineRowsAndTenants(array $combinedRows, array $rows): array
    {
        //combine rows of different store tables into one joint assoc. array
        foreach ($rows as $row) {
            $id = $row['id'];
            $combinedRow = $row;
            if (array_key_exists($id, $combinedRows)) {
                $openTenantsExisting = $combinedRows[$id]['tenants'];
                $openTenantsNew = $row['tenants'];
                $mergedTenantNameList = array_unique(array_merge($openTenantsExisting, $openTenantsNew));
                $combinedRow['tenants'] = $mergedTenantNameList;
                $combinedRow['numTenants'] = count($mergedTenantNameList);
            }
            $combinedRows[$id] = $combinedRow;
        }
        Logger::debug('Total product IDs after adding the store table: '.count($combinedRows));

        return $combinedRows;
    }
}
