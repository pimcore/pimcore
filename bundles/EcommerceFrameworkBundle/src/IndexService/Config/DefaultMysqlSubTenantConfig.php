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

use Doctrine\DBAL\Connection;
use Pimcore\Bundle\EcommerceFrameworkBundle\EnvironmentInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\IndexableInterface;

/**
 * Sample implementation for sub-tenants based on mysql.
 *
 * NOTE: this works only with a single-column primary key
 */
class DefaultMysqlSubTenantConfig extends DefaultMysql
{
    protected EnvironmentInterface $environment;

    protected Connection $db;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        AttributeFactory $attributeFactory,
        string $tenantName,
        array $attributes,
        array $searchAttributes,
        array $filterTypes,
        array $options,
        EnvironmentInterface $environment,
        Connection $db
    ) {
        $this->environment = $environment;
        $this->db = $db;

        parent::__construct($attributeFactory, $tenantName, $attributes, $searchAttributes, $filterTypes, $options);
    }

    /**
     * returns table name of product index
     *
     * @return string
     */
    public function getTablename(): string
    {
        return 'ecommerceframework_productindex_with_subtenants';
    }

    /**
     * returns table name of product index reations
     *
     * @return string
     */
    public function getRelationTablename(): string
    {
        return 'ecommerceframework_productindex_with_subtenants_relations';
    }

    /**
     * return table name of product index tenant relations for subtenants
     *
     * @return string
     */
    public function getTenantRelationTablename(): string
    {
        return 'ecommerceframework_productindex_with_subtenants_tenant_relations';
    }

    /**
     * checks, if product should be in index for current tenant (not subtenant)
     *
     * @param IndexableInterface $object
     *
     * @return bool
     */
    public function inIndex(IndexableInterface $object): bool
    {
        $tenants = null;
        if (method_exists($object, 'getTenants')) {
            $tenants = $object->getTenants();
        }

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
    public function getJoins(): string
    {
        $currentSubTenant = $this->environment->getCurrentAssortmentSubTenant();
        if ($currentSubTenant) {
            return ' INNER JOIN ' . $this->getTenantRelationTablename() . ' b ON a.id = b.id ';
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
    public function getCondition(): string
    {
        $currentSubTenant = $this->environment->getCurrentAssortmentSubTenant();
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
     * @param IndexableInterface $object
     * @param int|null $subObjectId
     *
     * @return array $subTenantData
     */
    public function prepareSubTenantEntries(IndexableInterface $object, int $subObjectId = null): array
    {
        $subTenantData = [];
        if ($this->inIndex($object)) {
            //implementation specific tenant get logic
            $tenants = [];
            if (method_exists($object, 'getTenants')) {
                $tenants = $object->getTenants();
            }

            foreach ($tenants as $tenant) {
                $subTenantData[] = ['id' => $object->getId(), 'subtenant_id' => $tenant->getId()];
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
    public function updateSubTenantEntries(mixed $objectId, mixed $subTenantData, mixed $subObjectId = null): void
    {
        $this->db->delete($this->getTenantRelationTablename(), ['id' => $subObjectId ?: $objectId]);

        if ($subTenantData) {
            //implementation specific tenant get logic
            foreach ($subTenantData as $data) {
                $this->db->insert($this->getTenantRelationTablename(), $data);
            }
        }
    }
}
