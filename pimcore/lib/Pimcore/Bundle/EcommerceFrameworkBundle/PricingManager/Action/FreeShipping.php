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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Action;

class FreeShipping implements \Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IAction
{
    /**
     * @param \Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IEnvironment $environment
     *
     * @return \Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IAction
     */
    public function executeOnProduct(\Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IEnvironment $environment)
    {
        return $this;
    }

    /**
     * @param \Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IEnvironment $environment
     *
     * @return \Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IAction
     */
    public function executeOnCart(\Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IEnvironment $environment)
    {
        $priceCalculator = $environment->getCart()->getPriceCalculator();

        $list = $priceCalculator->getModificators();
        foreach ($list as &$modificator) {
            /* @var \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartPriceModificator\ICartPriceModificator $modificator_ */

            // remove shipping charge
            if ($modificator instanceof \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartPriceModificator\IShipping) {
                $modificator->setCharge(0);
                $priceCalculator->reset();
            }
        }

        return $this;
    }

    /**
     * @return string
     */
    public function toJSON()
    {
        return json_encode([
                                'type' => 'FreeShipping'
                           ]);
    }

    /**
     * @param string $string
     *
     * @return \Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\ICondition
     */
    public function fromJSON($string)
    {
        return $this;
    }
}
