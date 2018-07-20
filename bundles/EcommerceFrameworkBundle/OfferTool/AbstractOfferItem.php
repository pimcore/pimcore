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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\OfferTool;

use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\UnsupportedException;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrderItem;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\ICheckoutable;
use Pimcore\Model\DataObject\Concrete;

/**
 * Abstract base class for offer item pimcore objects
 */
class AbstractOfferItem extends Concrete
{
    /**
     * @throws UnsupportedException
     *
     * @return AbstractOfferToolProduct
     */
    public function getProduct()
    {
        throw new UnsupportedException('getProduct is not implemented for ' . get_class($this));
    }

    /**
     * @param ICheckoutable $product
     *
     * @throws UnsupportedException
     */
    public function setProduct($product)
    {
        throw new UnsupportedException('setProduct is not implemented for ' . get_class($this));
    }

    /**
     * @throws UnsupportedException
     *
     * @return string
     */
    public function getProductNumber()
    {
        throw new UnsupportedException('getProductNumber is not implemented for ' . get_class($this));
    }

    /**
     * @param string $productNumber
     *
     * @throws UnsupportedException
     */
    public function setProductNumber($productNumber)
    {
        throw new UnsupportedException('setProductNumber is not implemented for ' . get_class($this));
    }

    /**
     * @throws UnsupportedException
     *
     * @return string
     */
    public function getProductName()
    {
        throw new UnsupportedException('getProductName is not implemented for ' . get_class($this));
    }

    /**
     * @param string $productName
     *
     * @throws UnsupportedException
     */
    public function setProductName($productName)
    {
        throw new UnsupportedException('setProductName is not implemented for ' . get_class($this));
    }

    /**
     * @throws UnsupportedException
     *
     * @return float
     */
    public function getAmount()
    {
        throw new UnsupportedException('getAmount is not implemented for ' . get_class($this));
    }

    /**
     * @param float $amount
     *
     * @throws UnsupportedException
     */
    public function setAmount($amount)
    {
        throw new UnsupportedException('setAmount is not implemented for ' . get_class($this));
    }

    /**
     * @throws UnsupportedException
     *
     * @return string|float|int
     */
    public function getOriginalTotalPrice()
    {
        throw new UnsupportedException('getOriginalTotalPrice is not implemented for ' . get_class($this));
    }

    /**
     * @throws UnsupportedException
     *
     * @param string|float|int $originalTotalPrice
     */
    public function setOriginalTotalPrice($originalTotalPrice)
    {
        throw new UnsupportedException('setOriginalTotalPrice is not implemented for ' . get_class($this));
    }

    /**
     * @throws UnsupportedException
     *
     * @return string|float|int
     */
    public function getFinalTotalPrice()
    {
        throw new UnsupportedException('getFinalTotalPrice is not implemented for ' . get_class($this));
    }

    /**
     * @throws UnsupportedException
     *
     * @param string|float|int $finalTotalPrice
     */
    public function setFinalTotalPrice($finalTotalPrice)
    {
        throw new UnsupportedException('setFinalTotalPrice is not implemented for ' . get_class($this));
    }

    /**
     * @throws UnsupportedException
     *
     * @return float
     */
    public function getDiscount()
    {
        throw new UnsupportedException('getDiscount is not implemented for ' . get_class($this));
    }

    /**
     * @throws UnsupportedException
     *
     * @param float $discount
     */
    public function setDiscount($discount)
    {
        throw new UnsupportedException('setDiscount is not implemented for ' . get_class($this));
    }

    /**
     * @throws UnsupportedException
     *
     * @return string
     */
    public function getDiscountType()
    {
        throw new UnsupportedException('getDiscountType is not implemented for ' . get_class($this));
    }

    /**
     * @throws UnsupportedException
     *
     * @param string $discountType
     */
    public function setDiscountType($discountType)
    {
        throw new UnsupportedException('setDiscountType is not implemented for ' . get_class($this));
    }

    /**
     * @return AbstractOrderItem[]
     *
     * @throws UnsupportedException
     */
    public function getSubItems()
    {
        throw new UnsupportedException('getSubItems is not implemented for ' . get_class($this));
    }

    /**
     * @param AbstractOrderItem[] $subItems
     *
     * @throws UnsupportedException
     */
    public function setSubItems($subItems)
    {
        throw new UnsupportedException('setSubItems is not implemented for ' . get_class($this));
    }

    /**
     * @throws UnsupportedException
     *
     * @return string
     */
    public function getComment()
    {
        throw new UnsupportedException('getComment is not implemented for ' . get_class($this));
    }

    /**
     * @throws UnsupportedException
     *
     * @param string $comment
     */
    public function setComment($comment)
    {
        throw new UnsupportedException('getComment is not implemented for ' . get_class($this));
    }

    /**
     * @throws UnsupportedException
     *
     * @return string
     */
    public function getCartItemKey()
    {
        throw new UnsupportedException('getCartItemKey is not implemented for ' . get_class($this));
    }

    /**
     * @throws UnsupportedException
     *
     * @param string $cartItemKey
     */
    public function setCartItemKey($cartItemKey)
    {
        throw new UnsupportedException('setCartItemKey is not implemented for ' . get_class($this));
    }
}
