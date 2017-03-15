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


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PricingManager\Condition;

class Tenant implements \OnlineShop\Framework\PricingManager\ICondition
{
    /**
     * @var string[]
     */
    protected $tenant;


    /**
     * @param \OnlineShop\Framework\PricingManager\IEnvironment $environment
     *
     * @return boolean
     */
    public function check(\OnlineShop\Framework\PricingManager\IEnvironment $environment)
    {
        $currentTenant = \OnlineShop\Framework\Factory::getInstance()->getEnvironment()->getCurrentAssortmentTenant();
        return in_array($currentTenant, $this->getTenant());
    }

    /**
     * @return string
     */
    public function toJSON()
    {
        // basic
        $json = [
            'type' => 'Tenant'
            , 'tenant' => implode(',', $this->getTenant())
        ];

        return json_encode($json);
    }

    /**
     * @param string $string
     *
     * @return \OnlineShop\Framework\PricingManager\ICondition
     */
    public function fromJSON($string)
    {
        $json = json_decode($string);

        $this->setTenant( explode(',', $json->tenant) );

        return $this;
    }

    /**
     * @return string[]
     */
    public function getTenant()
    {
        return $this->tenant;
    }

    /**
     * @param string[] $tenant
     *
     * @return $this
     */
    public function setTenant(array $tenant)
    {
        $this->tenant = $tenant;
        return $this;
    }
}