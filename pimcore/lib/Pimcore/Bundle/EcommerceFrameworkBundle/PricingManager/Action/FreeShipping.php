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

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartPriceModificator\ICartPriceModificator;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartPriceModificator\IShipping;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IAction;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\ICondition;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IEnvironment;

class FreeShipping implements IAction
{
    /**
     * @param IEnvironment $environment
     *
     * @return IAction
     */
    public function executeOnProduct(IEnvironment $environment)
    {
        return $this;
    }

    /**
     * @param IEnvironment $environment
     *
     * @return IAction
     */
    public function executeOnCart(IEnvironment $environment)
    {
        $priceCalculator = $environment->getCart()->getPriceCalculator();

        $list = $priceCalculator->getModificators();
        foreach ($list as &$modificator) {
            /* @var ICartPriceModificator $modificator_ */

            // remove shipping charge
            if ($modificator instanceof IShipping) {
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
     * @return ICondition
     */
    public function fromJSON($string)
    {
        return $this;
    }
}
