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


/**
 * Class OnlineShop_Framework_IndexService_Tenant_Config_DefaultMysqlSubTenantConfig
 *
 * Sample implementation for sub-tenants based on mysql.
 */
class OnlineShop_Framework_IndexService_Tenant_Config_DefaultMysqlSubTenantConfig extends OnlineShop_Framework_IndexService_Tenant_Config_DefaultMysql {

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

    public function inIndex(OnlineShop_Framework_ProductInterfaces_IIndexable $object) {
        $tenants = $object->getTenants();
        return !empty($tenants);
    }

    /**
     * in case of subtenants returns a data structure containing all sub tenants
     *
     * @param OnlineShop_Framework_ProductInterfaces_IIndexable $object
     * @param null $subObjectId
     * @return mixed $subTenantData
     */
    public function prepareSubTenantEntries(OnlineShop_Framework_ProductInterfaces_IIndexable $object, $subObjectId = null)
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
        $db = \Pimcore\Resource::get();
        $db->delete($this->getTenantRelationTablename(), "o_id = " . $db->quote($subObjectId ? $subObjectId : $objectId));

        if($subTenantData) {
            //implementation specific tenant get logic
            foreach($subTenantData as $data) {
                $db->insert($this->getTenantRelationTablename(), $data);
            }
        }
    }
}