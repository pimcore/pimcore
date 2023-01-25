<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config;

use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\DefaultMysql as DefaultMysqlWorker;
use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\WorkerInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\IndexableInterface;

/**
 * Tenant configuration for a simple mysql product index implementation. It is used by the default tenant.
 *
 * @method DefaultMysqlWorker getTenantWorker()
 */
class DefaultMysql extends AbstractConfig implements MysqlConfigInterface
{
    public function getTablename(): string
    {
        return 'ecommerceframework_productindex';
    }

    public function getRelationTablename(): string
    {
        return 'ecommerceframework_productindex_relations';
    }

    public function getTenantRelationTablename(): string
    {
        return '';
    }

    public function getJoins(): string
    {
        return '';
    }

    public function getCondition(): string
    {
        return '';
    }

    public function inIndex(IndexableInterface $object): bool
    {
        return true;
    }

    /**
     * in case of subtenants returns a data structure containing all sub tenants
     *
     * @param IndexableInterface $object
     * @param int|null $subObjectId
     *
     * @return mixed $subTenantData
     */
    public function prepareSubTenantEntries(IndexableInterface $object, int $subObjectId = null): mixed
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
    public function updateSubTenantEntries(mixed $objectId, mixed $subTenantData, mixed $subObjectId = null): void
    {
        return;
    }

    /**
     * {@inheritdoc}
     */
    public function setTenantWorker(WorkerInterface $tenantWorker): void
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
