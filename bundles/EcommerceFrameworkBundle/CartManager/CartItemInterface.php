<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\CartManager;

use Pimcore\Bundle\EcommerceFrameworkBundle\AvailabilitySystem\AvailabilityInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractSetProductEntry;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\CheckoutableInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\PriceInfoInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\PriceInterface;

/**
 * Interface for cart item implementations of online shop framework
 */
interface CartItemInterface
{
    /**
     * @return CheckoutableInterface
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
     * @param CheckoutableInterface $product
     *
     * @return void
     */
    public function setProduct(CheckoutableInterface $product);

    /**
     * @param int $count
     *
     * @return void
     */
    public function setCount($count);

    /**
     * @param CartInterface $cart
     *
     * @return void
     */
    public function setCart(CartInterface $cart);

    /**
     * @return CartInterface|null
     */
    public function getCart();

    /**
     * @return CartItemInterface[]
     */
    public function getSubItems();

    /**
     * @param CartItemInterface[] $subItems
     *
     * @return void
     */
    public function setSubItems($subItems);

    /**
     * @return PriceInterface
     */
    public function getPrice(): PriceInterface;

    /**
     * @return PriceInterface
     */
    public function getTotalPrice(): PriceInterface;

    /**
     * @return PriceInfoInterface
     */
    public function getPriceInfo(): PriceInfoInterface;

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
     * @return AvailabilityInterface
     */
    public function getAvailabilityInfo();

    /**
     * @static
     *
     * @param int $cartId
     * @param string $itemKey
     * @param string $parentKey
     *
     * @return CartItemInterface|null
     */
    public static function getByCartIdItemKey($cartId, $itemKey, $parentKey = '');

    /**
     * @static
     *
     * @param int $cartId
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
