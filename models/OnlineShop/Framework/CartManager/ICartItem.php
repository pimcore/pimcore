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

namespace OnlineShop\Framework\CartManager;

/**
 * interface for cart item implementations of online shop framework
 */
interface ICartItem {

    /**
     * @abstract
     * @return \OnlineShop_Framework_ProductInterfaces_ICheckoutable
     */
    public function getProduct();

    /**
     * @abstract
     * @return int
     */
    public function getCount();

    /**
     * @abstract
     * @return string
     */
    public function getItemKey();

    /**
     * @abstract
     * @param \OnlineShop_Framework_ProductInterfaces_ICheckoutable $product
     * @return void
     */
    public function setProduct(\OnlineShop_Framework_ProductInterfaces_ICheckoutable $product);

    /**
     * @abstract
     * @param int $count
     * @return void
     */
    public function setCount($count);

    /**
     * @abstract
     * @param ICart $cart
     * @return void
     */
    public function setCart(ICart $cart);

    /**
     * @abstract
     * @return ICart
     */
    public function getCart();

    /**
     * @abstract
     * @return ICartItem[]
     */
    public function getSubItems();

    /**
     * @abstract
     * @param  ICartItem[] $subItems
     * @return void
     */
    public function setSubItems($subItems);

    /**
     * @abstract
     * @return \OnlineShop\Framework\PriceSystem\IPrice
     */
    public function getPrice();

    /**
     * @abstract
     * @return \OnlineShop\Framework\PriceSystem\IPrice
     */
    public function getTotalPrice();

    /**
     * @abstract
     * @return \OnlineShop\Framework\PriceSystem\IPriceInfo
     */
    public function getPriceInfo();

    /**
     * @param string $comment
     * @return void
     */
    public function setComment($comment);

    /**
     * @abstract
     * @return string
     */
    public function getComment();

    /**
     * @return \OnlineShop_Framework_AbstractSetProductEntry[]
     */
    public function getSetEntries();

    /**
     * @abstract
     * @return \OnlineShop_Framework_IAvailability
     */
    public function getAvailabilityInfo();


    /**
     * @static
     * @abstract
     * @param $cartId
     * @param $itemKey
     * @param string $parentKey
     * @return ICartItem
     */
    public static function getByCartIdItemKey($cartId, $itemKey, $parentKey = "");

    /**
     * @static
     * @abstract
     * @param $cartId
     * @return void
     */
    public static function removeAllFromCart($cartId);

    /**
     * @abstract
     * @return void
     */
    public function save();

    /**
     * @param \Zend_Date $date
     * @return void
     */
    public function setAddedDate(\Zend_Date $date = null);

    /**
     * @return \Zend_Date
     */
    public function getAddedDate();

    /**
     * @return int unix timestamp
     */
    public function getAddedDateTimestamp();

    /**
     * @param int $time
     * @return void
     */
    public function setAddedDateTimestamp($time);

    /**
     * get item name
     * @return string
     */
    public function getName();
}
