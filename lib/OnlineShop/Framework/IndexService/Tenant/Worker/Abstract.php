<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */


abstract class OnlineShop_Framework_IndexService_Tenant_Worker_Abstract implements OnlineShop_Framework_IndexService_Tenant_IWorker {
    protected $name;
    protected $columnConfig;
    protected $searchColumnConfig;

    protected $indexColumns;
    protected $filterGroups;


    /**
     * @var OnlineShop_Framework_IndexService_Tenant_IConfig
     */
    protected $tenantConfig;

    public function __construct(OnlineShop_Framework_IndexService_Tenant_IConfig $tenantConfig) {
        $this->name = $tenantConfig->getTenantName();
        $this->tenantConfig = $tenantConfig;
        $this->columnConfig = $tenantConfig->getAttributeConfig();
        $this->searchColumnConfig = $tenantConfig->getSearchAttributeConfig();
        $this->db = \Pimcore\Resource::get();
    }

    public function getTenantConfig() {
        return $this->tenantConfig;
    }

    public function getGeneralSearchAttributes() {
        return $this->searchColumnConfig;
    }

    public function getIndexAttributes($considerHideInFieldList = false) {
        if(empty($this->indexColumns)) {
            $this->indexColumns = array();

            $this->indexColumns["categoryIds"] = "categoryIds";

            foreach($this->columnConfig->column as $column) {
                if(!$considerHideInFieldList || ($considerHideInFieldList && $column->hideInFieldlistDatatype != "true")) {
                    $this->indexColumns[$column->name] = $column->name;
                }
            }
            $this->indexColumns = array_values($this->indexColumns);
        }

        return $this->indexColumns;
    }

    public function getIndexAttributesByFilterGroup($filterGroup) {
        $this->getAllFilterGroups();
        return $this->filterGroups[$filterGroup] ? $this->filterGroups[$filterGroup] : [];
    }

    public function getAllFilterGroups() {
        if(empty($this->filterGroups)) {
            $this->filterGroups = array();
            $this->filterGroups['system'] = array_diff($this->getSystemAttributes(), array("categoryIds"));
            $this->filterGroups['category'] = array("categoryIds");


            if($this->columnConfig) {
                foreach($this->columnConfig->column as $column) {
                    if($column->filtergroup) {
                        $this->filterGroups[(string)$column->filtergroup][] = (string)$column->name;
                    }
                }
            }
        }

        return array_keys($this->filterGroups);
    }

    protected function getSystemAttributes() {
        return array();
    }

    /**
     * cleans up all old zombie data
     *
     * @param OnlineShop_Framework_ProductInterfaces_IIndexable $object
     * @param array $subObjectIds
     */
    protected function doCleanupOldZombieData(OnlineShop_Framework_ProductInterfaces_IIndexable $object, array $subObjectIds) {
        $cleanupIds = $this->tenantConfig->getSubIdsToCleanup($object, $subObjectIds);
        foreach($cleanupIds as $idToCleanup) {
            $this->doDeleteFromIndex($idToCleanup);
        }
    }

    abstract protected function doDeleteFromIndex($subObjectId);

}