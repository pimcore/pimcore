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
 * Class \OnlineShop\Framework\IndexService\Config\DefaultFindologic
 *
 * Default implementation for FINDOLOGIC as product index backend
 */
class DefaultFindologic extends AbstractConfig implements IFindologicConfig, IMockupConfig
{
    protected $clientConfig;

    /**
     * @param string $tenantName
     * @param $tenantConfig
     * @param null $totalConfig
     */
    public function __construct($tenantName, $tenantConfig, $totalConfig = null)
    {
        parent::__construct($tenantName, $tenantConfig, $totalConfig);

        $this->clientConfig = $tenantConfig->clientConfig->toArray();
    }


    /**
     * @param string $property
     *
     * @return array|string
     */
    public function getClientConfig($property = null)
    {
        return $property
            ? $this->clientConfig[$property]
            : $this->clientConfig
        ;
    }


    /**
     * checks, if product should be in index for current tenant
     *
     * @param \OnlineShop\Framework\Model\IIndexable $object
     *
     * @return bool
     */
    public function inIndex(\OnlineShop\Framework\Model\IIndexable $object)
    {
        return true;
    }

    /**
     * in case of subtenants returns a data structure containing all sub tenants
     *
     * @param \OnlineShop\Framework\Model\IIndexable $object
     * @param null                                              $subObjectId
     *
     * @return mixed $subTenantData
     */
    public function prepareSubTenantEntries(\OnlineShop\Framework\Model\IIndexable $object, $subObjectId = null)
    {

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

    }

    /**
     * creates and returns tenant worker suitable for this tenant configuration
     *
     * @return \OnlineShop\Framework\IndexService\Worker\IWorker
     */
    public function getTenantWorker()
    {
        if(empty($this->tenantWorker))
        {
            $this->tenantWorker = new \OnlineShop\Framework\IndexService\Worker\DefaultFindologic($this);
        }

        return $this->tenantWorker;
    }

    /**
     * returns condition for current subtenant
     *
     * @return array
     */
    public function getSubTenantCondition()
    {
        return [];
    }


    /**
     * creates object mockup for given data
     *
     * @param $objectId
     * @param $data
     * @param $relations
     * @return mixed
     */
    public function createMockupObject($objectId, $data, $relations)
    {
        return new \OnlineShop\Framework\Model\DefaultMockup($objectId, $data, $relations);
    }
}
