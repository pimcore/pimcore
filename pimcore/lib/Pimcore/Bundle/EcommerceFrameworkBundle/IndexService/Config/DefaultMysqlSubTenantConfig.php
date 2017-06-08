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

    /**
     * returns table name of product index
     *
     * @return string
     */
    public function getTablename()
    {
        return 'ecommerceframework_productindex_with_subtenants';
    }

    /**
     * returns table name of product index reations
     *
     * @return string
     */
    public function getRelationTablename()
    {
        return 'ecommerceframework_productindex_with_subtenants_relations';
    }

    /**
     * return table name of product index tenant relations for subtenants
     *
     * @return string
     */
    public function getTenantRelationTablename()
    {
        return 'ecommerceframework_productindex_with_subtenants_tenant_relations';
    }

    /**
     * checks, if product should be in index for current tenant (not subtenant)
     *
     * @param IIndexable $object
     *
     * @return bool
     */
    public function inIndex(IIndexable $object)
    {
        $tenants = $object->getTenants();

        return !empty($tenants);
    }

    /**
     * return join statement in case of subtenants
     *
     * In this case adds join statement to tenant relation table. But in theory any needed join statement can be
     * added here.
     *
     * @return string
     */
    public function getJoins()
    {
        $currentSubTenant = Factory::getInstance()->getEnvironment()->getCurrentAssortmentSubTenant();
        if ($currentSubTenant) {
            return ' INNER JOIN ' . $this->getTenantRelationTablename() . ' b ON a.o_id = b.o_id ';
        } else {
            return '';
        }
    }

    /**
     * returns additional condition in case of subtenants
     *
     * In this case just adds the condition that subtenant_id equals the current subtenant
     *
     * @return string
     */
    public function getCondition()
    {
        $currentSubTenant = Factory::getInstance()->getEnvironment()->getCurrentAssortmentSubTenant();
        if ($currentSubTenant) {
            return 'b.subtenant_id = ' . $currentSubTenant;
        } else {
            return '';
        }
    }

    /**
     * in case of subtenants returns a data structure containing all sub tenants
     *
     * In this case tenants are also Pimcore objects and are assigned to product objects.
     * This method extracts assigned tenants and returns an array of [object-ID, subtenant-ID]
     *
     * @param IIndexable $object
     * @param null $subObjectId
     *
     * @return mixed $subTenantData
     */
    public function prepareSubTenantEntries(IIndexable $object, $subObjectId = null)
    {
        $subTenantData = [];
        if ($this->inIndex($object)) {
            //implementation specific tenant get logic
            foreach ($object->getTenants() as $tenant) {
                $subTenantData[] = ['o_id' => $object->getId(), 'subtenant_id' => $tenant->getId()];
            }
        }

        return $subTenantData;
    }

    /**
     * populates index for tenant relations based on given data
     *
     * In this case deletes all entries of given object from tenant relation table and adds the new ones.
     *
     * @param mixed $objectId
     * @param mixed $subTenantData
     * @param mixed $subObjectId
     *
     * @return void
     */
    public function updateSubTenantEntries($objectId, $subTenantData, $subObjectId = null)
    {
        $db = \Pimcore\Db::get();
        $db->deleteWhere($this->getTenantRelationTablename(), 'o_id = ' . $db->quote($subObjectId ? $subObjectId : $objectId));

        if ($subTenantData) {
            //implementation specific tenant get logic
            foreach ($subTenantData as $data) {
                $db->insert($this->getTenantRelationTablename(), $data);
            }
        }
    }
}
