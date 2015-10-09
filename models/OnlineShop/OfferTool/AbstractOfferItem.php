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
 * Abstract base class for offer item pimcore objects
 */
class OnlineShop_OfferTool_AbstractOfferItem extends \Pimcore\Model\Object\Concrete {

    /**
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     * @return OnlineShop_OfferTool_AbstractOfferToolProduct
     */
    public function getProduct() {
        throw new OnlineShop_Framework_Exception_UnsupportedException("getProduct is not implemented for " . get_class($this));
    }

    /**
     * @param OnlineShop_OfferTool_AbstractOfferToolProduct $product
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     */
    public function setProduct($product) {
        throw new OnlineShop_Framework_Exception_UnsupportedException("setProduct is not implemented for " . get_class($this));
    }


    /**
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     * @return string
     */
    public function getProductNumber() {
        throw new OnlineShop_Framework_Exception_UnsupportedException("getProductNumber is not implemented for " . get_class($this));
    }

    /**
     * @param string $productNumber
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     */
    public function setProductNumber($productNumber) {
        throw new OnlineShop_Framework_Exception_UnsupportedException("setProductNumber is not implemented for " . get_class($this));
    }


    /**
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     * @return string
     */
    public function getProductName() {
        throw new OnlineShop_Framework_Exception_UnsupportedException("getProductName is not implemented for " . get_class($this));
    }

    /**
     * @param string $productName
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     */
    public function setProductName($productName) {
        throw new OnlineShop_Framework_Exception_UnsupportedException("setProductName is not implemented for " . get_class($this));
    }

    /**
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     * @return float
     */
    public function getAmount() {
        throw new OnlineShop_Framework_Exception_UnsupportedException("getAmount is not implemented for " . get_class($this));
    }

    /**
     * @param float $amount
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     */
    public function setAmount($amount) {
        throw new OnlineShop_Framework_Exception_UnsupportedException("setAmount is not implemented for " . get_class($this));
    }


    /**
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     * @return float
     */
    public function getOriginalTotalPrice() {
        throw new OnlineShop_Framework_Exception_UnsupportedException("getOriginalTotalPrice is not implemented for " . get_class($this));
    }

    /**
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     * @param float $originalTotalPrice
     */
    public function setOriginalTotalPrice($originalTotalPrice) {
        throw new OnlineShop_Framework_Exception_UnsupportedException("setOriginalTotalPrice is not implemented for " . get_class($this));
    }

    /**
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     * @return float
     */
    public function getFinalTotalPrice() {
        throw new OnlineShop_Framework_Exception_UnsupportedException("getFinalTotalPrice is not implemented for " . get_class($this));
    }

    /**
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     * @param float $finalTotalPrice
     */
    public function setFinalTotalPrice($finalTotalPrice) {
        throw new OnlineShop_Framework_Exception_UnsupportedException("setFinalTotalPrice is not implemented for " . get_class($this));
    }


    /**
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     * @return float
     */
    public function getDiscount() {
        throw new OnlineShop_Framework_Exception_UnsupportedException("getDiscount is not implemented for " . get_class($this));
    }

    /**
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     * @param float $discount
     */
    public function setDiscount($discount) {
        throw new OnlineShop_Framework_Exception_UnsupportedException("setDiscount is not implemented for " . get_class($this));
    }

    /**
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     * @return string
     */
    public function getDiscountType() {
        throw new OnlineShop_Framework_Exception_UnsupportedException("getDiscountType is not implemented for " . get_class($this));
    }

    /**
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     * @param string $discountType
     */
    public function setDiscountType($discountType) {
        throw new OnlineShop_Framework_Exception_UnsupportedException("setDiscountType is not implemented for " . get_class($this));
    }


    /**
     * @return OnlineShop_Framework_AbstractOrderItem[]
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     */
    public function getSubItems() {
        throw new OnlineShop_Framework_Exception_UnsupportedException("getSubItems is not implemented for " . get_class($this));
    }

    /**
     * @param OnlineShop_Framework_AbstractOrderItem[] $subItems
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     */
    public function setSubItems($subItems) {
        throw new OnlineShop_Framework_Exception_UnsupportedException("setSubItems is not implemented for " . get_class($this));
    }



    /**
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     * @return string
     */
    public function getComment() {
        throw new OnlineShop_Framework_Exception_UnsupportedException("getComment is not implemented for " . get_class($this));
    }

    /**
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     * @param string $comment
     */
    public function setComment($comment) {
        throw new OnlineShop_Framework_Exception_UnsupportedException("getComment is not implemented for " . get_class($this));
    }


    /**
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     * @return string
     */
    public function getCartItemKey() {
        throw new OnlineShop_Framework_Exception_UnsupportedException("getCartItemKey is not implemented for " . get_class($this));
    }

    /**
     * @throws OnlineShop_Framework_Exception_UnsupportedException
     * @param string $cartItemKey
     */
    public function setCartItemKey($cartItemKey) {
        throw new OnlineShop_Framework_Exception_UnsupportedException("setCartItemKey is not implemented for " . get_class($this));
    }

}
