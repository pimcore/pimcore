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


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\OfferTool;

/**
 * Abstract base class for offer item pimcore objects
 */
class AbstractOfferItem extends \Pimcore\Model\Object\Concrete {

    /**
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @return AbstractOfferToolProduct
     */
    public function getProduct() {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("getProduct is not implemented for " . get_class($this));
    }

    /**
     * @param \OnlineShop\Framework\Model\ICheckoutable $product
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     */
    public function setProduct($product) {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("setProduct is not implemented for " . get_class($this));
    }


    /**
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @return string
     */
    public function getProductNumber() {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("getProductNumber is not implemented for " . get_class($this));
    }

    /**
     * @param string $productNumber
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     */
    public function setProductNumber($productNumber) {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("setProductNumber is not implemented for " . get_class($this));
    }


    /**
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @return string
     */
    public function getProductName() {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("getProductName is not implemented for " . get_class($this));
    }

    /**
     * @param string $productName
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     */
    public function setProductName($productName) {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("setProductName is not implemented for " . get_class($this));
    }

    /**
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @return float
     */
    public function getAmount() {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("getAmount is not implemented for " . get_class($this));
    }

    /**
     * @param float $amount
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     */
    public function setAmount($amount) {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("setAmount is not implemented for " . get_class($this));
    }


    /**
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @return float
     */
    public function getOriginalTotalPrice() {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("getOriginalTotalPrice is not implemented for " . get_class($this));
    }

    /**
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @param float $originalTotalPrice
     */
    public function setOriginalTotalPrice($originalTotalPrice) {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("setOriginalTotalPrice is not implemented for " . get_class($this));
    }

    /**
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @return float
     */
    public function getFinalTotalPrice() {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("getFinalTotalPrice is not implemented for " . get_class($this));
    }

    /**
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @param float $finalTotalPrice
     */
    public function setFinalTotalPrice($finalTotalPrice) {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("setFinalTotalPrice is not implemented for " . get_class($this));
    }


    /**
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @return float
     */
    public function getDiscount() {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("getDiscount is not implemented for " . get_class($this));
    }

    /**
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @param float $discount
     */
    public function setDiscount($discount) {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("setDiscount is not implemented for " . get_class($this));
    }

    /**
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @return string
     */
    public function getDiscountType() {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("getDiscountType is not implemented for " . get_class($this));
    }

    /**
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @param string $discountType
     */
    public function setDiscountType($discountType) {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("setDiscountType is not implemented for " . get_class($this));
    }


    /**
     * @return AbstractOrderItem[]
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     */
    public function getSubItems() {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("getSubItems is not implemented for " . get_class($this));
    }

    /**
     * @param AbstractOrderItem[] $subItems
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     */
    public function setSubItems($subItems) {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("setSubItems is not implemented for " . get_class($this));
    }



    /**
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @return string
     */
    public function getComment() {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("getComment is not implemented for " . get_class($this));
    }

    /**
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @param string $comment
     */
    public function setComment($comment) {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("getComment is not implemented for " . get_class($this));
    }


    /**
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @return string
     */
    public function getCartItemKey() {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("getCartItemKey is not implemented for " . get_class($this));
    }

    /**
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @param string $cartItemKey
     */
    public function setCartItemKey($cartItemKey) {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("setCartItemKey is not implemented for " . get_class($this));
    }

}
