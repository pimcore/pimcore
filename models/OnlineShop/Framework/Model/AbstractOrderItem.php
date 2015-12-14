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

namespace OnlineShop\Framework\Model;

/**
 * Abstract base class for order item pimcore objects
 */
class AbstractOrderItem extends \Pimcore\Model\Object\Concrete {

    /**
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @return \OnlineShop\Framework\Model\ICheckoutable
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
    public function getTotalPrice() {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("getTotalPrice is not implemented for " . get_class($this));
    }

    /**
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @param float $totalPrice
     */
    public function setTotalPrice($totalPrice) {
        throw new \OnlineShop\Framework\Exception\UnsupportedException("setTotalPrice is not implemented for " . get_class($this));
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
     * @return \Pimcore\Model\Object\Fieldcollection
     */
    public function getPricingRules() {
        throw new \OnlineShop\Framework\Exception\UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @param \Pimcore\Model\Object\Fieldcollection $pricingRules
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @return $this
     */
    public function setPricingRules ($pricingRules) {
        throw new \OnlineShop\Framework\Exception\UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @return string
     */
    public function getOrderState() {
        throw new \OnlineShop\Framework\Exception\UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }

    /**
     * @param string $orderState
     * @throws \OnlineShop\Framework\Exception\UnsupportedException
     * @return $this
     */
    public function setOrderState ($orderState) {
        throw new \OnlineShop\Framework\Exception\UnsupportedException(__FUNCTION__ . " is not implemented for " . get_class($this));
    }


    /**
     * is the order item cancel able
     * @return bool
     */
    public function isCancelAble()
    {
        return true && !$this->isCanceled();
    }

    /**
     * is the order item edit able
     * @return bool
     */
    public function isEditAble()
    {
        return true && !$this->isCanceled();
    }


    /**
     * ist eine rückerstattung erlaubt
     * @return bool
     */
    public function isComplaintAble()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function isCanceled()
    {
        return $this->getOrderState() == AbstractOrder::ORDER_STATE_CANCELLED;
    }

    /**
     * @return AbstractOrder
     */
    public function getOrder()
    {
        $parent = $this;
        while(!$parent instanceof AbstractOrder)
        {
            $parent = $parent->getParent();
        }

        return $parent;
    }
}
