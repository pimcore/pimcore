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
 * Class OnlineShop_Framework_IndexService_Tenant_Config_ElasticSearch
 *
 * Default configuration for elastic search as product index implementation.
 */
class OnlineShop_Framework_IndexService_Tenant_Config_ElasticSearch extends OnlineShop_Framework_IndexService_Tenant_Config_AbstractConfig implements OnlineShop_Framework_IndexService_Tenant_IMockupConfig, OnlineShop_Framework_IndexService_Tenant_IElasticSearchConfig {

    /**
     *
     */
    protected $generalSettings = [];

    /**
     * @var array
     */
    protected $indexSettings = null;

    /**
     * @var array
     */
    protected $elasticSearchClientParams = null;

    /**
     * @param string $tenantName
     * @param $tenantConfigXml
     * @param null $totalConfigXml
     */
    public function __construct($tenantName, $tenantConfigXml, $totalConfigXml = null) {
        parent::__construct($tenantName, $tenantConfigXml, $totalConfigXml);

        if($tenantConfigXml->generalSettings){
            $this->setGeneralSettings($tenantConfigXml->generalSettings->toArray());
        }

        $this->indexSettings = json_decode($tenantConfigXml->indexSettingsJson, true);
        $this->elasticSearchClientParams = json_decode($tenantConfigXml->elasticSearchClientParamsJson, true);
    }

    /**
     * @return mixed
     */
    public function getGeneralSettings()
    {
        return $this->generalSettings;
    }

    /**
     * @param mixed $generalSettings
     *
     * @return $this
     */
    public function setGeneralSettings($generalSettings)
    {
        $this->generalSettings = $generalSettings;
        return $this;
    }



    /**
     * @return array
     */
    public function getIndexSettings() {
        return $this->indexSettings;
    }

    /**
     * @return array
     */
    public function getElasticSearchClientParams() {
        return $this->elasticSearchClientParams;
    }


    /**
     * checks, if product should be in index for current tenant
     *
     * @param \OnlineShop\Framework\Model\IIndexable $object
     * @return bool
     */
    public function inIndex(\OnlineShop\Framework\Model\IIndexable $object) {
        return true;
    }

    /**
     * in case of subtenants returns a data structure containing all sub tenants
     *
     * @param \OnlineShop\Framework\Model\IIndexable $object
     * @param null $subObjectId
     * @return mixed $subTenantData
     */
    public function prepareSubTenantEntries(\OnlineShop\Framework\Model\IIndexable $object, $subObjectId = null) {
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
    public function updateSubTenantEntries($objectId, $subTenantData, $subObjectId = null) {
        // nothing to do
        return;
    }

    /**
     * returns condition for current subtenant
     *
     * @return array
     */
    public function getSubTenantCondition() {
        return;
    }


    /**
     * @var OnlineShop_Framework_IndexService_Tenant_Worker_ElasticSearch
     */
    protected $tenantWorker;

    /**
     * creates and returns tenant worker suitable for this tenant configuration
     *
     * @return OnlineShop_Framework_IndexService_Tenant_Worker_ElasticSearch
     */
    public function getTenantWorker() {
        if(empty($this->tenantWorker)) {
            $this->tenantWorker = new OnlineShop_Framework_IndexService_Tenant_Worker_ElasticSearch($this);
        }
        return $this->tenantWorker;
    }

    /**
     * creates object mockup for given data
     *
     * @param $objectId
     * @param $data
     * @param $relations
     * @return mixed
     */
    public function createMockupObject($objectId, $data, $relations) {
        return new OnlineShop_Framework_ProductList_DefaultMockup($objectId, $data, $relations);
    }

    /**
     * Gets object mockup by id, can consider subIds and therefore return e.g. an array of values
     * always returns a object mockup if available
     *
     * @param $objectId
     * @return \OnlineShop\Framework\Model\IIndexable | array
     */
    public function getObjectMockupById($objectId) {
        return $this->getTenantWorker()->getMockupFromCache($objectId);
    }
}
