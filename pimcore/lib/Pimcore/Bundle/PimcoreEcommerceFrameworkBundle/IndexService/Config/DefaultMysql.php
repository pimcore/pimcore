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

namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\Config;

use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\Worker\IWorker;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Model\IIndexable;

/**
 * Class \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\Config\DefaultMysql
 *
 * Tenant configuration for a simple mysql product index implementation. It is used by the default tenant.
 */
class DefaultMysql extends AbstractConfig implements IMysqlConfig
{

    /**
     * @return string
     */
    public function getTablename()
    {
        return "ecommerceframework_productindex";
    }

    /**
     * @return string
     */
    public function getRelationTablename()
    {
        return "ecommerceframework_productindex_relations";
    }

    /**
     * @return string
     */
    public function getTenantRelationTablename()
    {
        return "";
    }

    /**
     * @return string
     */
    public function getJoins()
    {
        return "";
    }

    /**
     * @return string
     */
    public function getCondition()
    {
        return "";
    }

    /**
     * @param IIndexable $object
     * @return bool
     */
    public function inIndex(IIndexable $object)
    {
        return true;
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
        if ($isPrimary) {
            return "int(11) NOT NULL default '0'";
        } else {
            return "int(11) NOT NULL";
        }
    }


    /**
     * @var \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\Worker\DefaultMysql
     */
    protected $tenantWorker;

    /**
     * creates and returns tenant worker suitable for this tenant configuration
     *
     * @return IWorker
     */
    public function getTenantWorker()
    {
        if (empty($this->tenantWorker)) {
            $this->tenantWorker = new \Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\Worker\DefaultMysql($this);
        }

        return $this->tenantWorker;
    }
}
