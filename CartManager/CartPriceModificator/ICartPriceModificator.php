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


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager\CartPriceModificator;
use OnlineShop\Framework\CartManager\ICart;
use OnlineShop\Framework\PriceSystem\IModificatedPrice;

/**
 * Interface ICartPriceModificator
 */
interface ICartPriceModificator {

    /**
     * @return string
     */
    public function getName();

    /**
     * function which modifies the current sub total price
     *
     * @param \OnlineShop\Framework\PriceSystem\IPrice $currentSubTotal - current sub total which is modified and returned
     * @param ICart $cart - cart
     * @return IModificatedPrice
     */
    public function modify(\OnlineShop\Framework\PriceSystem\IPrice $currentSubTotal, \OnlineShop\Framework\CartManager\ICart $cart);

}
