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


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\CartManager;

/**
 * interface for cart item implementations of online shop framework
 */
interface ICartItem {

    /**
     * @abstract
     * @return \OnlineShop\Framework\Model\ICheckoutable
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
     * @param \OnlineShop\Framework\Model\ICheckoutable $product
     * @return void
     */
    public function setProduct(\OnlineShop\Framework\Model\ICheckoutable $product);

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
     * @return \OnlineShop\Framework\Model\AbstractSetProductEntry[]
     */
    public function getSetEntries();

    /**
     * @abstract
     * @return \OnlineShop\Framework\AvailabilitySystem\IAvailability
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
     * @param \DateTime $date
     * @return void
     */
    public function setAddedDate(\DateTime $date = null);

    /**
     * @return \DateTime
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
