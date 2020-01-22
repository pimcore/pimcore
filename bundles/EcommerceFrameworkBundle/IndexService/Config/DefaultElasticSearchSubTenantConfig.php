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

use Pimcore\Bundle\EcommerceFrameworkBundle\Model\IndexableInterface;

/**
 * Sample implementation for sub-tenants based on elastic search.
 */
class DefaultElasticSearchSubTenantConfig extends ElasticSearch
{
    /**
     * checks, if product should be in index for current tenant (not subtenant)
     *
     * @param IndexableInterface $object
     *
     * @return bool
     */
    public function inIndex(IndexableInterface $object)
    {
        $tenants = $object->getTenants();

        return !empty($tenants);
    }

    /**
     * in case of subtenants returns an array containing all sub tenants
     *
     * In this case tenants are also Pimcore objects and are assigned to product objects.
     * This method extracts assigned tenants and returns an array of subtenant-IDs
     *
     * @param IndexableInterface $object
     * @param int|null $subObjectId
     *
     * @return array $subTenantData
     */
    public function prepareSubTenantEntries(IndexableInterface $object, $subObjectId = null)
    {
        $subTenantData = [];
        if ($this->inIndex($object)) {
            //implementation specific tenant get logic
            foreach ($object->getTenants() as $tenant) {
                $subTenantData[] = $tenant->getId();
            }
        }

        return ['ids' => $subTenantData];
    }
}
