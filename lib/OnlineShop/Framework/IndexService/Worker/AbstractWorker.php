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

namespace OnlineShop\Framework\IndexService\Worker;

abstract class AbstractWorker implements IWorker {
    protected $name;
    protected $columnConfig;
    protected $searchColumnConfig;

    protected $indexColumns;
    protected $filterGroups;


    /**
     * @var \OnlineShop\Framework\IndexService\Config\IConfig
     */
    protected $tenantConfig;

    public function __construct(\OnlineShop\Framework\IndexService\Config\IConfig $tenantConfig) {
        $this->name = $tenantConfig->getTenantName();
        $this->tenantConfig = $tenantConfig;
        $this->columnConfig = $tenantConfig->getAttributeConfig();
        $this->searchColumnConfig = $tenantConfig->getSearchAttributeConfig();
        $this->db = \Pimcore\Db::get();
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
     * @param \OnlineShop\Framework\Model\IIndexable $object
     * @param array $subObjectIds
     */
    protected function doCleanupOldZombieData(\OnlineShop\Framework\Model\IIndexable $object, array $subObjectIds) {
        $cleanupIds = $this->tenantConfig->getSubIdsToCleanup($object, $subObjectIds);
        foreach($cleanupIds as $idToCleanup) {
            $this->doDeleteFromIndex($idToCleanup, $object);
        }
    }

    /**
     * actually deletes all sub entries from index. original object is delivered too, but keep in mind, that this might be empty.
     *
     * @param $subObjectId
     * @param \OnlineShop\Framework\Model\IIndexable $object - might be empty (when object doesn't exist any more in pimcore
     * @return mixed
     */
    abstract protected function doDeleteFromIndex($subObjectId, \OnlineShop\Framework\Model\IIndexable $object = null);

}