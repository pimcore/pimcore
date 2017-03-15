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
 * @category   Pimcore
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\Config;

/**
 * Class \OnlineShop\Framework\IndexService\Config\DefaultMysqlSubTenantConfig
 *
 * Sample implementation for sub-tenants based on mysql.
 */
class DefaultMysqlSubTenantConfig extends DefaultMysql {

    // NOTE: this works only with a single-column primary key

    public function getTablename() {
        return "plugin_onlineshop_productindex2";
    }

    public function getRelationTablename() {
        return "plugin_onlineshop_productindex_relations2";
    }

    public function getTenantRelationTablename() {
        return "plugin_onlineshop_productindex_tenant_relations";
    }



    public function getJoins() {
        $currentSubTenant = \OnlineShop\Framework\Factory::getInstance()->getEnvironment()->getCurrentAssortmentSubTenant();
        if($currentSubTenant) {
            return " INNER JOIN " . $this->getTenantRelationTablename() . " b ON a.o_id = b.o_id ";
        } else {
            return "";
        }

    }

    public function getCondition() {
        $currentSubTenant = \OnlineShop\Framework\Factory::getInstance()->getEnvironment()->getCurrentAssortmentSubTenant();
        if($currentSubTenant) {
            return "b.subtenant_id = " . $currentSubTenant;
        } else {
            return "";
        }
    }

    public function inIndex(\OnlineShop\Framework\Model\IIndexable $object) {
        $tenants = $object->getTenants();
        return !empty($tenants);
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
        $subTenantData = array();
        if($this->inIndex($object)) {
            //implementation specific tenant get logic
            foreach($object->getTenants() as $tenant) {
                $subTenantData[] = array("o_id" => $object->getId(), "subtenant_id" => $tenant->getId());
            }
        }
        return $subTenantData;
    }

    public function updateSubTenantEntries($objectId, $subTenantData, $subObjectId = null) {
        $db = \Pimcore\Db::get();
        $db->delete($this->getTenantRelationTablename(), "o_id = " . $db->quote($subObjectId ? $subObjectId : $objectId));

        if($subTenantData) {
            //implementation specific tenant get logic
            foreach($subTenantData as $data) {
                $db->insert($this->getTenantRelationTablename(), $data);
            }
        }
    }
}