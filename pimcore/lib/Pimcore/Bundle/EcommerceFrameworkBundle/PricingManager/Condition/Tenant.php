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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition;

use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;

class Tenant implements \Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\ICondition
{
    /**
     * @var string[]
     */
    protected $tenant;

    /**
     * @param \Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IEnvironment $environment
     *
     * @return bool
     */
    public function check(\Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IEnvironment $environment)
    {
        $currentTenant = Factory::getInstance()->getEnvironment()->getCurrentAssortmentTenant();

        return in_array($currentTenant, $this->getTenant());
    }

    /**
     * @return string
     */
    public function toJSON()
    {
        // basic
        $json = [
            'type' => 'Tenant', 'tenant' => implode(',', $this->getTenant())
        ];

        return json_encode($json);
    }

    /**
     * @param string $string
     *
     * @return \Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\ICondition
     */
    public function fromJSON($string)
    {
        $json = json_decode($string);

        $this->setTenant(explode(',', $json->tenant));

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
