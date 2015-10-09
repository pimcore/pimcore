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
 * Class OnlineShop_Framework_Config_HelperContainer
 *
 * Helper class for online shop config in combination with tenants
 *
 * tries to use config for current checkout tenant, uses default config if corresponding root attribute is not set
 *
 */
class OnlineShop_Framework_Config_HelperContainer {

    /**
     * @var Zend_Config
     */
    protected $defaultConfig;

    /**
     * @var Zend_Config[]
     */
    protected $tenantConfigs;

    /**
     * @param Zend_Config $config     -> configuration to contain
     * @param string      $identifier -> cache identifier for caching sub files
     */
    public function __construct(Zend_Config $config, $identifier) {
        $this->defaultConfig = $config;

        foreach($config->tenants->toArray() as $tenantName => $tenantConfig) {

            $tenantConfig = $config->tenants->{$tenantName};
            if($tenantConfig instanceof Zend_Config) {
                if($tenantConfig->file) {

                    $cacheKey = "onlineshop_config_" . $identifier . "_checkout_tenant_" . $tenantName;

                    if(!$tenantConfigFile =  \Pimcore\Model\Cache::load($cacheKey)) {
                        $tenantConfigFile = new Zend_Config_Xml(PIMCORE_DOCUMENT_ROOT . ((string)$tenantConfig->file), null, true);
                        $tenantConfigFile = $tenantConfigFile->tenant;
                        \Pimcore\Model\Cache::save($tenantConfigFile, $cacheKey, array("ecommerceconfig"), 9999);
                    }

                    $this->tenantConfigs[$tenantName] = $tenantConfigFile;
                } else {
                    $this->tenantConfigs[$tenantName] = $tenantConfig;
                }
            }
        }
    }



    public function __get($name) {
        $currentCheckoutTenant = OnlineShop_Framework_Factory::getInstance()->getEnvironment()->getCurrentCheckoutTenant();

        if($currentCheckoutTenant && $this->tenantConfigs[$currentCheckoutTenant]) {
            $option = $this->tenantConfigs[$currentCheckoutTenant]->$name;
            if($option) {
                return $option;
            }
        }

        return $this->defaultConfig->$name;
    }





}