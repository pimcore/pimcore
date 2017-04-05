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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Tools\Config;

use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;
use Pimcore\Config\Config;

/**
 * Class \Pimcore\Bundle\EcommerceFrameworkBundle\Tools\Config\HelperContainer
 *
 * Helper class for online shop config in combination with tenants
 *
 * tries to use config for current checkout tenant, uses default config if corresponding root attribute is not set
 *
 */
class HelperContainer
{

    /**
     * @var Config
     */
    protected $defaultConfig;

    /**
     * @var Config[]
     */
    protected $tenantConfigs;

    /**
     * @param Config      $config     -> configuration to contain
     * @param string      $identifier -> cache identifier for caching sub files
     */
    public function __construct(Config $config, $identifier)
    {
        $this->defaultConfig = $config;

        if (!$config->tenants || empty($config->tenants)) {
            return;
        }

        foreach ($config->tenants->toArray() as $tenantName => $tenantConfig) {
            $tenantConfig = $config->tenants->{$tenantName};
            if ($tenantConfig instanceof Config) {
                if ($tenantConfig->file) {
                    $tenantConfigFile = new Config(require PIMCORE_CUSTOM_CONFIGURATION_DIRECTORY . ((string)$tenantConfig->file), true);
                    $this->tenantConfigs[$tenantName] = $tenantConfigFile->tenant;
                } else {
                    $this->tenantConfigs[$tenantName] = $tenantConfig;
                }
            }
        }
    }



    public function __get($name)
    {
        $currentCheckoutTenant = Factory::getInstance()->getEnvironment()->getCurrentCheckoutTenant();

        if ($currentCheckoutTenant && $this->tenantConfigs[$currentCheckoutTenant]) {
            $option = $this->tenantConfigs[$currentCheckoutTenant]->$name;
            if ($option) {
                return $option;
            }
        }

        return $this->defaultConfig->$name;
    }
}
