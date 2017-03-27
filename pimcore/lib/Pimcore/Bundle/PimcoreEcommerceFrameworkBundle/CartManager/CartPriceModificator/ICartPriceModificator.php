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

namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\CartPriceModificator;

use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\ICart;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PriceSystem\IModificatedPrice;
use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\PriceSystem\IPrice;

/**
 * Interface ICartPriceModificator
 */
interface ICartPriceModificator
{

    /**
     * @return string
     */
    public function getName();

    /**
     * function which modifies the current sub total price
     *
     * @param IPrice $currentSubTotal - current sub total which is modified and returned
     * @param ICart $cart - cart
     * @return IModificatedPrice
     */
    public function modify(IPrice $currentSubTotal, ICart $cart);
}
