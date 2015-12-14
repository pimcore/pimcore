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

namespace OnlineShop\Framework\IndexService\Config;

/**
 * Class \OnlineShop\Framework\IndexService\Config\DefaultMysql
 *
 * Tenant configuration for a simple mysql product index implementation. It is used by the default tenant.
 */
class DefaultMysql extends AbstractConfig implements IMysqlConfig {

    /**
     * @return string
     */
    public function getTablename() {
        return "plugin_onlineshop_productindex";
    }

    /**
     * @return string
     */
    public function getRelationTablename() {
        return "plugin_onlineshop_productindex_relations";
    }

    /**
     * @return string
     */
    public function getTenantRelationTablename() {
        return "";
    }

    /**
     * @return string
     */
    public function getJoins() {
        return "";
    }

    /**
     * @return string
     */
    public function getCondition() {
        return "";
    }

    /**
     * @param \OnlineShop\Framework\Model\IIndexable $object
     * @return bool
     */
    public function inIndex(\OnlineShop\Framework\Model\IIndexable $object) {
        return true;
    }


    /**
     * in case of subtenants returns a data structure containing all sub tenants
     *
     * @param \OnlineShop\Framework\Model\IIndexable $object
     * @param null $subObjectId
     * @return mixed $subTenantData
     */
    public function prepareSubTenantEntries(\OnlineShop\Framework\Model\IIndexable $object, $subObjectId = null)
    {
        return null;
    }

    /**
     * populates index for tenant relations based on gived data
     *
     * @param mixed $objectId
     * @param mixed $subTenantData
     * @param mixed $subObjectId
     * @return void
     */
    public function updateSubTenantEntries($objectId, $subTenantData, $subObjectId = null)
    {
        return;
    }

    /**
     * returns column type for id
     *
     * @param $isPrimary
     * @return string
     */
    public function getIdColumnType($isPrimary)
    {
        if($isPrimary) {
            return "int(11) NOT NULL default '0'";
        } else {
            return "int(11) NOT NULL";
        }
    }


    /**
     * @var \OnlineShop\Framework\IndexService\Worker\DefaultMysql
     */
    protected $tenantWorker;

    /**
     * creates and returns tenant worker suitable for this tenant configuration
     *
     * @return \OnlineShop\Framework\IndexService\Worker\IWorker
     */
    public function getTenantWorker() {
        if(empty($this->tenantWorker)) {
            $this->tenantWorker = new \OnlineShop\Framework\IndexService\Worker\DefaultMysql($this);
        }
        return $this->tenantWorker;
    }
}