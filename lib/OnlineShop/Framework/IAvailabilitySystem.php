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
 * Interface OnlineShop_Framework_IAvailabilitySystem
 */
interface OnlineShop_Framework_IAvailabilitySystem {


    /**
     * @abstract
     * @param OnlineShop_Framework_ProductInterfaces_ICheckoutable $abstractProduct
     * @param int $quantityScale
     * @param null $products
     * @return OnlineShop_Framework_IAvailability
     */
    public function getAvailabilityInfo(OnlineShop_Framework_ProductInterfaces_ICheckoutable $abstractProduct, $quantityScale = 1, $products = null);




}
