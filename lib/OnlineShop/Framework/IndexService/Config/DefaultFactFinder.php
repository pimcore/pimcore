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

namespace OnlineShop\Framework\IndexService\Config;

/**
 * Class \OnlineShop\Framework\IndexService\Config\DefaultFactFinder
 *
 * Default implementation for fact finder as product index backend
 */
class DefaultFactFinder extends AbstractConfig implements IFactFinderConfig, IMockupConfig
{
    protected $clientConfig;

    /**
     * @param string $tenantName
     * @param $tenantConfigXml
     * @param null $totalConfigXml
     */
    public function __construct($tenantName, $tenantConfigXml, $totalConfigXml = null)
    {
        parent::__construct($tenantName, $tenantConfigXml, $totalConfigXml);

        $this->clientConfig = $tenantConfigXml->clientConfig->toArray();
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
            $this->tenantWorker = new \OnlineShop\Framework\IndexService\Worker\DefaultFactFinder($this);
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
