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
 * Interface for PriceInfo implementations of online shop framework
 */
interface OnlineShop_Framework_IPriceInfo {
    const MIN_PRICE = "min";

    /**
     * returns single price
     *
     * @abstract
     * @return OnlineShop_Framework_IPrice
     */
    public function getPrice();

    /**
     * returns total price (single price * quantity)
     *
     * @abstract
     * @return OnlineShop_Framework_IPrice
     */
    public function getTotalPrice();

    /**
     * returns if price is a minimal price (e.g. when having many product variants they might have a from price)
     *
     * @abstract
     * @return bool
     */
    public function isMinPrice();

    /**
     * returns quantity
     *
     * @abstract
     * @return int
     */
    public function getQuantity();

    /**
     * @param int|string $quantity
     * numeric quantity or constant OnlineShop_Framework_IPriceInfo::MIN_PRICE
     */
    public function setQuantity($quantity);

    /**
     * relation to price system
     *
     * @abstract
     * @param OnlineShop_Framework_IPriceSystem $priceSystem
     * @return OnlineShop_Framework_Pricing_IPriceInfo
     */
    public function setPriceSystem($priceSystem);

    /**
     * relation to product
     *
     * @param OnlineShop_Framework_ProductInterfaces_ICheckoutable $product
     *
     * @return OnlineShop_Framework_Pricing_IPriceInfo
     */
    public function setProduct(OnlineShop_Framework_ProductInterfaces_ICheckoutable $product);

    /**
     * returns product
     *
     * @return OnlineShop_Framework_ProductInterfaces_ICheckoutable
     */
    public function getProduct();
}