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

use Pimcore\Bundle\EcommerceFrameworkBundle\EnvironmentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\AbstractBatchProcessingWorker;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\WorkerInterface;
use Pimcore\Db;
use Pimcore\Logger;

class IndexUpdateService
{
    /**
     * @var EnvironmentInterface
     */
    private $environment;

    /**
     * @var IndexService
     */
    private $indexService;

    /**
     * @param EnvironmentInterface $environment
     * @param WorkerInterface[] $tenantWorkers
     * @param string $defaultTenant
     */
    public function __construct(EnvironmentInterface $environment, IndexService $indexService = null)
    {
        $this->environment = $environment;
        $this->indexService = $indexService;
    }

    /**
     * Fetch productIds and tenant information for all products that require product index preparation,
     * and for all tenants with store table support.
     * @param array $tenantNameFilterList
     *      - an optional list of tenants that should be used for filtering.
     *      - by default, all tenants will be used.
     * @return array
     *      - each item contains a single product.
     *      - each product ID can exist max. once.
     *      - each item contains the list of open tenants as another array
     */
    public function fetchProductIdsForPreparation(array $tenantNameFilterList=[]) : array {

        $storeTableList = $this->getStoreTableList($tenantNameFilterList);

        $combinedRows = [];

        foreach ($storeTableList as $storeTableName) {

            $rows = $this->fetchProductIdsForPreparationForSingleStoreTable($storeTableName, $tenantNameFilterList);

            //combine rows of different store tables into one joint assoc. array
            foreach ($rows as $row) {
                $id = $row['id'];
                $combinedRow = $row;
                if (array_key_exists($id, $combinedRows)) {
                    $openTenantsExisting = $combinedRow[$id]['tenants'];
                    $openTenantsNew = $row['tenants'];
                    $mergedTenantNameList = array_unique(array_merge($openTenantsExisting, $openTenantsNew));
                    $combinedRow['tenants'] = $mergedTenantNameList;
                    $combinedRow['numTenants'] = count($mergedTenantNameList);
                }
                $combinedRows[$id] = $combinedRow;
            }

            Logger::debug('Total product IDs after adding the store table: '.count($combinedRows));
        }

        return $combinedRows;
    }

    /**
     * Fetch productIds and tenant information for all products that require product index preparation,
     * and that are part of a specific store table.
     * @param string $storeTableName the name of the store table.
     * @param array $tenantNameFilterList
     *      - an optional list of tenants that should be used for filtering.
     *      - by default, all tenants will be used.
     * @return array
     *      - each item contains a single product.
     *      - each product ID can exist max. once.
     *      - each item contains the list of open tenants as another array
     */
    protected function fetchProductIdsForPreparationForSingleStoreTable(string $storeTableName,array $tenantNameFilterList) : array {

        $optionalTenantCondition = "";
        if (!empty($tenantNameFilterList)) {
            $optionalTenantCondition = sprintf(
                ' AND tenant in(%s)',
                implode(',', array_map(function($str) { return sprintf("'%s'", $str);},$tenantNameFilterList))
            );

        }

        $sql = '('.
            'SELECT SQL_CALC_FOUND_ROWS o_id as id, group_concat(tenant) as tenants, count(tenant) as numTenants '.
            'FROM '.$storeTableName.' '.
            'WHERE in_preparation_queue = 1 '.
            $optionalTenantCondition.
            ' group by o_id order by numTenants desc'.
            ')'
        ;

        Logger::debug('Store table SQL:'.$sql);

        $rows = Db::get()->fetchAll($sql, $tenantNameFilterList ? [$tenantNameFilterList]: []);

        $result = [];
        foreach ($rows as $row) {
            $tenantNameList = explode_and_trim(",", $row['tenants']);
            $row['tenants'] = $tenantNameList;
            $result[] = $row;
        }

        return $result;
    }

    /**
     * Fetch productIds and tenant information for all products that require an index update, and that support store tables.
     * @param array $tenantNameFilterList
     *      - an optional list of tenants that should be used for filtering.
     *      - by default, all tenants will be used.
     * @return array
     *      - each item contains a single product.
     *      - each product ID exists max. once
     *      - each item contains the list of open tenants as another array
     */
    public function fetchProductIdsForIndexUpdate(array $tenantNameFilterList=[]) : array {

        $storeTableList = $this->getStoreTableList($tenantNameFilterList);

        $combinedRows = [];

        foreach ($storeTableList as $storeTableName) {

            $rows = $this->fetchProductIdsIndexUpdateForSingleStoreTable($storeTableName, $tenantNameFilterList);

            //combine rows of different store tables into one joint assoc. array
            foreach ($rows as $row) {
                $id = $row['id'];
                $combinedRow = $row;
                if (array_key_exists($id, $combinedRows)) {
                    $openTenantsExisting = $combinedRow[$id]['tenants'];
                    $openTenantsNew = $row['tenants'];
                    $mergedTenantNameList = array_unique(array_merge($openTenantsExisting, $openTenantsNew));
                    $combinedRow['tenants'] = $mergedTenantNameList;
                    $combinedRow['numTenants'] = count($mergedTenantNameList);
                }
                $combinedRows[$id] = $combinedRow;
            }

            Logger::debug('Total product IDs after adding the store table: '.count($combinedRows));
        }

        return $combinedRows;
    }

    /**
     * Fetch productIds and tenant information for all products that require a product index update based on a specific store table.
     * @param string $storeTableName the name of the store table.
     * @param array $tenantNameFilterList
     *      - an optional list of tenants that should be used for filtering.
     *      - by default, all tenants will be used.
     * @return array
     *      - each item contains a single product.
     *      - each product ID can exist max. once.
     *      - each item contains the list of open tenants as another array
     */
    protected function fetchProductIdsIndexUpdateForSingleStoreTable(string $storeTableName,array $tenantNameFilterList) : array {

        $optionalTenantCondition = "";
        if (!empty($tenantNameFilterList)) {
            $optionalTenantCondition = sprintf(
                ' AND tenant in(%s)',
                implode(',', array_map(function($str) { return sprintf("'%s'", $str);},$tenantNameFilterList))
            );

        }

        $sql = '('.
            'SELECT SQL_CALC_FOUND_ROWS o_id as id, group_concat(tenant) as tenants, count(tenant) as numTenants '.
            'FROM '.$storeTableName.' '.
            'WHERE (crc_current != crc_index OR ISNULL(crc_index)) '.
            $optionalTenantCondition.
            ' group by o_id order by numTenants desc'.
            ')'
        ;

        Logger::debug('Update index SQL for store table:'.$sql);

        $rows = Db::get()->fetchAll($sql, $tenantNameFilterList ? [$tenantNameFilterList]: []);

        $result = [];
        foreach ($rows as $row) {
            $tenantNameList = explode_and_trim(",", $row['tenants']);
            $row['tenants'] = $tenantNameList;
            $result[] = $row;
        }

        return $result;
    }

    /**
     * Get a list of store tables depending on a list of tenants.
     * @param string[] $tenantNameFilterList
     *      - an optional list of tenants that should be used for filtering.
     *      - by default, all tenants will be used.
     * @return string[] a list of store table names.
     */
    public function getStoreTableList(array $tenantNameFilterList= []) : array {
        //get all store tables and join the results...
        $indexService = Factory::getInstance()->getIndexService();
        $tenants = $indexService->getTenants();
        $storeTableList = [];
        foreach ($tenants as $tenantName) {

            if(!empty($tenantNameFilterList) && !in_array($tenantName, $tenantNameFilterList)){
                continue;
            }

            $worker = $indexService->getTenantWorker($tenantName);
            if ($worker instanceof AbstractBatchProcessingWorker) {
                $storeTableName = $worker->getStoreTableName();
                if (!in_array($storeTableName, $storeTableList)) {
                    $storeTableList[] = $storeTableName;
                }
            }
        }
        return $storeTableList;
    }
}
