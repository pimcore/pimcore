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

namespace OnlineShop\Framework\PricingManager\Action;

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
