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
 * Interface OnlineShop_Framework_ICartPriceModificator
 */
interface OnlineShop_Framework_ICartPriceModificator {

    /**
     * @return string
     */
    public function getName();

    /**
     * function which modifies the current sub total price
     *
     * @param OnlineShop_Framework_IPrice  $currentSubTotal  - current sub total which is modified and returned
     * @param OnlineShop_Framework_ICart   $cart             - cart
     * @return OnlineShop_Framework_IModificatedPrice
     */
    public function modify(OnlineShop_Framework_IPrice $currentSubTotal, OnlineShop_Framework_ICart $cart);

}
