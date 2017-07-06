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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\CartManager;

use Pimcore\Bundle\EcommerceFrameworkBundle\AvailabilitySystem\IAvailability;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractSetProductEntry;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\ICheckoutable;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IPrice;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IPriceInfo;

/**
 * Interface for cart item implementations of online shop framework
 */
interface ICartItem
{
    /**
     * @return ICheckoutable
     */
    public function getProduct();

    /**
     * @return int
     */
    public function getCount();

    /**
     * @return string
     */
    public function getItemKey();

    /**
     * @param ICheckoutable $product
     *
     * @return void
     */
    public function setProduct(ICheckoutable $product);

    /**
     * @param int $count
     *
     * @return void
     */
    public function setCount($count);

    /**
     * @param ICart $cart
     *
     * @return void
     */
    public function setCart(ICart $cart);

    /**
     * @return ICart
     */
    public function getCart();

    /**
     * @return ICartItem[]
     */
    public function getSubItems();

    /**
     * @param ICartItem[] $subItems
     *
     * @return void
     */
    public function setSubItems($subItems);

    /**
     * @return IPrice
     */
    public function getPrice(): IPrice;

    /**
     * @return IPrice
     */
    public function getTotalPrice(): IPrice;

    /**
     * @return IPriceInfo
     */
    public function getPriceInfo(): IPriceInfo;

    /**
     * @param string $comment
     *
     * @return void
     */
    public function setComment($comment);

    /**
     * @return string
     */
    public function getComment();

    /**
     * @return AbstractSetProductEntry[]
     */
    public function getSetEntries();

    /**
     * @return IAvailability
     */
    public function getAvailabilityInfo();

    /**
     * @static
     *
     * @param $cartId
     * @param $itemKey
     * @param string $parentKey
     *
     * @return ICartItem
     */
    public static function getByCartIdItemKey($cartId, $itemKey, $parentKey = '');

    /**
     * @static
     *
     * @param $cartId
     *
     * @return void
     */
    public static function removeAllFromCart($cartId);

    /**
     * @return void
     */
    public function save();

    /**
     * @param \DateTime $date
     *
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
     *
     * @return void
     */
    public function setAddedDateTimestamp($time);

    /**
     * get item name
     *
     * @return string
     */
    public function getName();
}
