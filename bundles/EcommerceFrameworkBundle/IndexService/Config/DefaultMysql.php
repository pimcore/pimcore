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

use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\DefaultMysql as DefaultMysqlWorker;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\IWorker;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\IIndexable;

/**
 * Tenant configuration for a simple mysql product index implementation. It is used by the default tenant.
 *
 * @method DefaultMysqlWorker getTenantWorker()
 */
class DefaultMysql extends AbstractConfig implements IMysqlConfig
{
    /**
     * @return string
     */
    public function getTablename()
    {
        return 'ecommerceframework_productindex';
    }

    /**
     * @return string
     */
    public function getRelationTablename()
    {
        return 'ecommerceframework_productindex_relations';
    }

    /**
     * @return string
     */
    public function getTenantRelationTablename()
    {
        return '';
    }

    /**
     * @return string
     */
    public function getJoins()
    {
        return '';
    }

    /**
     * @return string
     */
    public function getCondition()
    {
        return '';
    }

    /**
     * @param IIndexable $object
     *
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
     *
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
     *
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
     *
     * @return string
     */
    public function getIdColumnType($isPrimary)
    {
        if ($isPrimary) {
            return "int(11) NOT NULL default '0'";
        } else {
            return 'int(11) NOT NULL';
        }
    }

    /**
     * @inheritDoc
     */
    public function setTenantWorker(IWorker $tenantWorker)
    {
        if (!$tenantWorker instanceof DefaultMysqlWorker) {
            throw new \InvalidArgumentException(sprintf(
                'Worker must be an instance of %s',
                DefaultMysqlWorker::class
            ));
        }

        parent::setTenantWorker($tenantWorker);
    }
}
