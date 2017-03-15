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


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PricingManager\Action;

class FreeShipping implements \OnlineShop\Framework\PricingManager\IAction
{
    /**
     * @param \OnlineShop\Framework\PricingManager\IEnvironment $environment
     *
     * @return \OnlineShop\Framework\PricingManager\IAction
     */
    public function executeOnProduct(\OnlineShop\Framework\PricingManager\IEnvironment $environment)
    {
        return $this;
    }

    /**
     * @param \OnlineShop\Framework\PricingManager\IEnvironment $environment
     *
     * @return \OnlineShop\Framework\PricingManager\IAction
     */
    public function executeOnCart(\OnlineShop\Framework\PricingManager\IEnvironment $environment)
    {
        $priceCalculator = $environment->getCart()->getPriceCalculator();

        $list = $priceCalculator->getModificators();
        foreach($list as &$modificator)
        {
            /* @var \OnlineShop\Framework\CartManager\CartPriceModificator\ICartPriceModificator $modificator_ */

            // remove shipping charge
            if($modificator instanceof \OnlineShop\Framework\CartManager\CartPriceModificator\IShipping) {
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
        return json_encode(array(
                                'type' => 'FreeShipping'
                           ));
    }

    /**
     * @param string $string
     *
     * @return \OnlineShop\Framework\PricingManager\ICondition
     */
    public function fromJSON($string)
    {
        return $this;
    }
}
