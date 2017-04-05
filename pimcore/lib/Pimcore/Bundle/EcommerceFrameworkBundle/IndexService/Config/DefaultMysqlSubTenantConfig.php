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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config;

use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\IIndexable;

/**
 * Class \Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\DefaultMysqlSubTenantConfig
 *
 * Sample implementation for sub-tenants based on mysql.
 */
class DefaultMysqlSubTenantConfig extends DefaultMysql
{

    // NOTE: this works only with a single-column primary key

    public function getTablename()
    {
        return "ecommerceframework_productindex2";
    }

    public function getRelationTablename()
    {
        return "ecommerceframework_productindex_relations2";
    }

    public function getTenantRelationTablename()
    {
        return "ecommerceframework_productindex_tenant_relations";
    }



    public function getJoins()
    {
        $currentSubTenant = Factory::getInstance()->getEnvironment()->getCurrentAssortmentSubTenant();
        if ($currentSubTenant) {
            return " INNER JOIN " . $this->getTenantRelationTablename() . " b ON a.o_id = b.o_id ";
        } else {
            return "";
        }
    }

    public function getCondition()
    {
        $currentSubTenant = Factory::getInstance()->getEnvironment()->getCurrentAssortmentSubTenant();
        if ($currentSubTenant) {
            return "b.subtenant_id = " . $currentSubTenant;
        } else {
            return "";
        }
    }

    public function inIndex(IIndexable $object)
    {
        $tenants = $object->getTenants();

        return !empty($tenants);
    }

    /**
     * in case of subtenants returns a data structure containing all sub tenants
     *
     * @param IIndexable $object
     * @param null $subObjectId
     * @return mixed $subTenantData
     */
    public function prepareSubTenantEntries(IIndexable $object, $subObjectId = null)
    {
        $subTenantData = [];
        if ($this->inIndex($object)) {
            //implementation specific tenant get logic
            foreach ($object->getTenants() as $tenant) {
                $subTenantData[] = ["o_id" => $object->getId(), "subtenant_id" => $tenant->getId()];
            }
        }

        return $subTenantData;
    }

    public function updateSubTenantEntries($objectId, $subTenantData, $subObjectId = null)
    {
        $db = \Pimcore\Db::get();
        $db->deleteWhere($this->getTenantRelationTablename(), "o_id = " . $db->quote($subObjectId ? $subObjectId : $objectId));

        if ($subTenantData) {
            //implementation specific tenant get logic
            foreach ($subTenantData as $data) {
                $db->insert($this->getTenantRelationTablename(), $data);
            }
        }
    }
}
