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

namespace OnlineShop\Framework\PriceSystem;

/**
 * Interface for PriceInfo implementations of online shop framework
 */
interface IPriceInfo {
    const MIN_PRICE = "min";

    /**
     * returns single price
     *
     * @abstract
     * @return IPrice
     */
    public function getPrice();

    /**
     * returns total price (single price * quantity)
     *
     * @abstract
     * @return IPrice
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
     * numeric quantity or constant IPriceInfo::MIN_PRICE
     */
    public function setQuantity($quantity);

    /**
     * relation to price system
     *
     * @abstract
     * @param \OnlineShop\Framework\PriceSystem\IPriceSystem $priceSystem
     * @return IPriceInfo
     */
    public function setPriceSystem($priceSystem);

    /**
     * relation to product
     *
     * @param \OnlineShop\Framework\Model\ICheckoutable $product
     *
     * @return IPriceInfo
     */
    public function setProduct(\OnlineShop\Framework\Model\ICheckoutable $product);

    /**
     * returns product
     *
     * @return \OnlineShop\Framework\Model\ICheckoutable
     */
    public function getProduct();
}